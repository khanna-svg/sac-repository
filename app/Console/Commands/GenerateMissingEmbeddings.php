<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMissingEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:generate-embeddings 
                            {--doc= : Specific document ID to process} 
                            {--batch-size=40 : Number of chunks per Gemini batch call} 
                            {--max= : Maximum chunks to embed in this execution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Gemini vector embeddings for document chunks with NULL embeddings';

    /**
     * Execute the console command.
     */
    public function handle(GeminiService $geminiService)
    {
        $docId = $this->option('doc');
        $batchSize = max(5, min(60, (int) $this->option('batch-size')));
        $maxChunks = $this->option('max') ? (int) $this->option('max') : null;

        $query = DB::table('document_chunks')
            ->whereNull('embedding')
            ->orderBy('document_id')
            ->orderBy('id');

        if ($docId) {
            $query->where('document_id', $docId);
            $this->info("Targeting single document ID: {$docId}");
        }

        $totalPending = $query->count();
        if ($totalPending === 0) {
            $this->info("All document chunks already have vector embeddings! Nothing to do.");
            return 0;
        }

        $toProcess = $maxChunks ? min($totalPending, $maxChunks) : $totalPending;
        $this->info("Found {$totalPending} pending chunks. Processing {$toProcess} chunks in batches of {$batchSize}...");

        $processed = 0;
        $bar = $this->output->createProgressBar($toProcess);
        $bar->start();

        while ($processed < $toProcess) {
            $fetchLimit = min($batchSize, $toProcess - $processed);
            
            $subQuery = DB::table('document_chunks')
                ->whereNull('embedding')
                ->orderBy('document_id')
                ->orderBy('id');

            if ($docId) {
                $subQuery->where('document_id', $docId);
            }

            $chunks = $subQuery->limit($fetchLimit)->get();

            if ($chunks->isEmpty()) {
                break;
            }

            $texts = [];
            foreach ($chunks as $c) {
                $text = trim((string) $c->chunk_text);
                $texts[] = $text !== '' ? $text : 'Document section';
            }

            try {
                $embeddings = $geminiService->generateEmbeddings($texts);

                if (count($embeddings) !== count($chunks)) {
                    $this->error("\nMismatched embedding count: got " . count($embeddings) . ", expected " . count($chunks));
                    break;
                }

                foreach ($chunks as $index => $chunk) {
                    $vector = '[' . implode(',', $embeddings[$index]) . ']';
                    DB::table('document_chunks')
                        ->where('id', $chunk->id)
                        ->update([
                            'embedding' => $vector,
                            'updated_at' => now(),
                        ]);
                }

                $processed += count($chunks);
                $bar->advance(count($chunks));

                // Small pause to respect Gemini free-tier RPM limits
                if ($processed < $toProcess) {
                    usleep(1500000); // 1.5 seconds
                }

            } catch (\Throwable $e) {
                $this->error("\nBatch failed: " . $e->getMessage());
                Log::error('GenerateMissingEmbeddings batch failed: ' . $e->getMessage());
                break;
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully generated and saved {$processed} embeddings!");

        return 0;
    }
}

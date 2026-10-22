<?php

namespace App\Console\Commands;

use App\Http\Controllers\AdminSubmissionController;
use Illuminate\Console\Command;

class PurgeStaleRevisions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'submissions:purge-stale-revisions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge unrevised thesis manuscripts older than 30 days (1 month)';

    /**
     * Execute the console command.
     */
    public function handle(AdminSubmissionController $controller): int
    {
        $this->info('Scanning for stale unrevised submissions (>30 days)...');

        $purgedCount = $controller->purgeStaleRevisions();

        if ($purgedCount > 0) {
            $this->info("Successfully purged {$purgedCount} expired revision manuscript(s) from Supabase and database.");
        } else {
            $this->info('No stale submissions found. All revisions are within the 30-day grace period.');
        }

        return Command::SUCCESS;
    }
}

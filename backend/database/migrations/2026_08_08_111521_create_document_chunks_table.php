<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enable vector extension in extensions schema
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA extensions;');
        
        // 2. Ensure PostgreSQL can resolve vector types during migration
        DB::statement('SET search_path TO public, extensions;');

        // 3. Create document_chunks table
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->text('chunk_text');
            $table->timestamps();
        });

        // 4. Add vector column referencing extensions schema explicitly
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding extensions.vector(768);');

        // 5. Create similarity search function
        DB::statement('
            CREATE OR REPLACE FUNCTION match_chunks (
              query_embedding extensions.vector(768),
              match_threshold float,
              match_count int
            )
            RETURNS TABLE (
              id bigint,
              document_id bigint,
              chunk_text text,
              similarity float
            )
            LANGUAGE sql STABLE
            AS $$
              SELECT
                document_chunks.id,
                document_chunks.document_id,
                document_chunks.chunk_text,
                1 - (document_chunks.embedding <=> query_embedding) AS similarity
              FROM document_chunks
              WHERE 1 - (document_chunks.embedding <=> query_embedding) > match_threshold
              ORDER BY document_chunks.embedding <=> query_embedding
              LIMIT match_count;
            $$;
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
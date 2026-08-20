<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    use HasFactory;

    protected $table = 'document_chunks';

    protected $fillable = [
        'document_id',
        'page_number',
        'chunk_text',
        'embedding',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
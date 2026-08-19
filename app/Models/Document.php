<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'abstract',
        'department',
        'course_code',
        'file_path',
        'file_url',
    ];

    public function chunks()
    {
        return $this->hasMany(DocumentChunk::class, 'document_id')->orderBy('page_number', 'asc');
    }
}

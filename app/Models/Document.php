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
        'publication_date',
        'file_path',
        'file_url',
        'status',
        'submitted_by_name',
        'submitted_by_email',
        'admin_notes',
    ];

    protected $casts = [
        'publication_date' => 'date',
    ];

    public function chunks()
    {
        return $this->hasMany(DocumentChunk::class, 'document_id')->orderBy('page_number', 'asc');
    }

    public function notifications()
    {
        return $this->hasMany(ThesisNotification::class, 'document_id');
    }
}

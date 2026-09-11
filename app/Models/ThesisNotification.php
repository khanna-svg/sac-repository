<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThesisNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_email',
        'title',
        'message',
        'type',
        'document_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}

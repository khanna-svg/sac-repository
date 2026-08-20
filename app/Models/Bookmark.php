<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_email',
        'document_id',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
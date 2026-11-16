<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_email',
        'admin_name',
        'action',
        'document_id',
        'document_title',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Convenient static recorder for admin actions.
     */
    public static function record(string $action, ?int $documentId, string $documentTitle, ?string $details = null): self
    {
        $email = session('sac_user_email') ?: 'admin@sac.edu.ph';
        $name = session('sac_user_name') ?: 'Administrator';
        $ip = request() ? request()->ip() : null;

        return self::create([
            'admin_email' => $email,
            'admin_name' => $name,
            'action' => $action,
            'document_id' => $documentId,
            'document_title' => $documentTitle,
            'details' => $details,
            'ip_address' => $ip,
        ]);
    }
}

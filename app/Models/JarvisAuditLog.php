<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JarvisAuditLog extends Model
{
    protected $table = 'jarvis_audit_logs';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'user_id');
    }
}

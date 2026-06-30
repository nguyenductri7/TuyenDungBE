<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'actor_role',
        'company_id',
        'target_type',
        'target_id',
        'action',
        'description',
        'before_json',
        'after_json',
        'metadata_json',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'actor_id' => 'integer',
        'company_id' => 'integer',
        'target_id' => 'integer',
        'before_json' => 'array',
        'after_json' => 'array',
        'metadata_json' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(NguoiDung::class, 'actor_id');
    }

    public function company()
    {
        return $this->belongsTo(CongTy::class, 'company_id');
    }
}

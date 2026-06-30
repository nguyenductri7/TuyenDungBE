<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    use HasFactory;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_FALLBACK = 'fallback';

    protected $fillable = [
        'feature',
        'endpoint',
        'provider',
        'model',
        'model_version',
        'status',
        'used_fallback',
        'duration_ms',
        'http_status',
        'error_message',
        'user_id',
        'company_id',
        'request_ref_type',
        'request_ref_id',
        'metadata_json',
    ];

    protected $casts = [
        'used_fallback' => 'boolean',
        'duration_ms' => 'integer',
        'http_status' => 'integer',
        'user_id' => 'integer',
        'company_id' => 'integer',
        'request_ref_id' => 'integer',
        'metadata_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(NguoiDung::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(CongTy::class, 'company_id');
    }
}

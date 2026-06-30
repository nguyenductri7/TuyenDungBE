<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoiDichVuTinhNang extends Model
{
    use HasFactory;

    protected $table = 'goi_dich_vu_tinh_nangs';

    protected $fillable = [
        'goi_dich_vu_id',
        'feature_code',
        'quota',
        'reset_cycle',
        'is_unlimited',
    ];

    protected $casts = [
        'goi_dich_vu_id' => 'integer',
        'quota' => 'integer',
        'is_unlimited' => 'boolean',
    ];

    public function goiDichVu()
    {
        return $this->belongsTo(GoiDichVu::class, 'goi_dich_vu_id');
    }
}

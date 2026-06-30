<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BangGiaTinhNangAi extends Model
{
    use HasFactory;

    public const TRANG_THAI_HOAT_DONG = 'active';
    public const TRANG_THAI_TAM_NGUNG = 'inactive';

    protected $table = 'bang_gia_tinh_nang_ai';

    protected $fillable = [
        'feature_code',
        'ten_hien_thi',
        'don_gia',
        'don_vi_tinh',
        'trang_thai',
    ];

    protected $casts = [
        'don_gia' => 'integer',
    ];
}

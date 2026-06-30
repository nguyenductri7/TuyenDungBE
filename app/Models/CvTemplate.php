<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvTemplate extends Model
{
    use HasFactory;

    protected $table = 'cv_templates';

    protected $fillable = [
        'ma_template',
        'ten_template',
        'mo_ta',
        'bo_cuc',
        'badges_json',
        'trang_thai',
        'thu_tu_hien_thi',
    ];

    protected $casts = [
        'badges_json' => 'array',
        'trang_thai' => 'integer',
        'thu_tu_hien_thi' => 'integer',
    ];

    public const TRANG_THAI_AN = 0;
    public const TRANG_THAI_HIEN = 1;

    public const BO_CUC_LIST = [
        'executive_navy',
        'topcv_maroon',
        'ats_serif',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TinTuyenDungKyNang extends Model
{
    use HasFactory;

    protected $table = 'tin_tuyen_dung_ky_nangs';

    protected $fillable = [
        'tin_tuyen_dung_id',
        'ky_nang_id',
        'muc_do_yeu_cau',
        'bat_buoc',
        'trong_so',
        'nguon_du_lieu',
        'do_tin_cay',
    ];

    protected $casts = [
        'tin_tuyen_dung_id' => 'integer',
        'ky_nang_id' => 'integer',
        'muc_do_yeu_cau' => 'integer',
        'bat_buoc' => 'boolean',
        'trong_so' => 'float',
        'do_tin_cay' => 'float',
    ];

    public function tinTuyenDung()
    {
        return $this->belongsTo(TinTuyenDung::class, 'tin_tuyen_dung_id');
    }

    public function kyNang()
    {
        return $this->belongsTo(KyNang::class, 'ky_nang_id');
    }
}

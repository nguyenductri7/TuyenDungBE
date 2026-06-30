<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'nguoi_dung_id',
        'loai',
        'tieu_de',
        'noi_dung',
        'duong_dan',
        'du_lieu_bo_sung',
        'da_doc_luc',
    ];

    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'du_lieu_bo_sung' => 'array',
        'da_doc_luc' => 'datetime',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }
}

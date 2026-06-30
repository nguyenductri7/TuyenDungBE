<?php

use App\Models\NguoiDung;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('candidate.{id}', function (NguoiDung $user, int $id) {
    return $user->isUngVien() && (int) $user->id === $id;
});

Broadcast::channel('user.{id}', function (NguoiDung $user, int $id) {
    return (int) $user->id === $id;
});

Broadcast::channel('company.{companyId}', function (NguoiDung $user, int $companyId) {
    return $user->isNhaTuyenDung()
        && (int) optional($user->congTyHienTai())->id === $companyId;
});

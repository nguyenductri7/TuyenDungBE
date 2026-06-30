<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\CongTy;
use App\Models\NguoiDung;
use App\Models\TinTuyenDung;
use App\Models\UngTuyen;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesEmployerCompany
{
    protected function getAuthenticatedEmployer(): ?NguoiDung
    {
        /** @var NguoiDung|null $user */
        $user = auth()->user();

        return $user;
    }

    protected function getCurrentEmployerCompany(): ?CongTy
    {
        $user = $this->getAuthenticatedEmployer();

        return $user?->congTyHienTai();
    }

    protected function isCompanyOwner(?NguoiDung $user, ?CongTy $congTy): bool
    {
        if (!$user || !$congTy) {
            return false;
        }

        return $user->laChuSoHuuCongTy($congTy->id);
    }

    protected function coTheQuanLyTatCaTinTuyenDung(?NguoiDung $user, ?CongTy $congTy): bool
    {
        if (!$user || !$congTy) {
            return false;
        }

        return $user->coVaiTroNoiBoCongTy(CongTy::VAI_TRO_NOI_BO_OWNER, $congTy);
    }

    protected function coTheQuanLyTatCaUngTuyen(?NguoiDung $user, ?CongTy $congTy): bool
    {
        if (!$user || !$congTy) {
            return false;
        }

        return $user->coVaiTroNoiBoCongTy(CongTy::VAI_TRO_NOI_BO_OWNER, $congTy);
    }

    protected function coTheQuanLyTinTheoOwnership(?NguoiDung $user, ?CongTy $congTy, ?TinTuyenDung $tin): bool
    {
        if (!$user || !$congTy || !$tin) {
            return false;
        }

        if ($this->coTheQuanLyTatCaTinTuyenDung($user, $congTy)) {
            return true;
        }

        return (int) ($tin->hr_phu_trach_id ?? 0) === (int) $user->id;
    }

    protected function coTheXuLyUngTuyenTheoOwnership(?NguoiDung $user, ?CongTy $congTy, ?UngTuyen $ungTuyen): bool
    {
        if (!$user || !$congTy || !$ungTuyen) {
            return false;
        }

        if ($this->coTheQuanLyTatCaUngTuyen($user, $congTy)) {
            return true;
        }



        $ungTuyen->loadMissing('tinTuyenDung:id,hr_phu_trach_id');

        return (int) ($ungTuyen->tinTuyenDung?->hr_phu_trach_id ?? 0) === (int) $user->id;
    }

    protected function abortIfCannotManageJobRecord(?NguoiDung $user, ?CongTy $congTy, ?TinTuyenDung $tin): void
    {
        if ($this->coTheQuanLyTinTheoOwnership($user, $congTy, $tin)) {
            return;
        }

        throw new HttpException(403, 'Bạn chỉ có thể thao tác trên tin tuyển dụng mình phụ trách.');
    }

    protected function abortIfCannotManageApplicationRecord(?NguoiDung $user, ?CongTy $congTy, ?UngTuyen $ungTuyen): void
    {
        if ($this->coTheXuLyUngTuyenTheoOwnership($user, $congTy, $ungTuyen)) {
            return;
        }

        throw new HttpException(403, 'Bạn chỉ có thể xử lý các đơn ứng tuyển mình phụ trách.');
    }
}

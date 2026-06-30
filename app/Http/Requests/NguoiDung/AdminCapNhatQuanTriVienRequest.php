<?php

namespace App\Http\Requests\NguoiDung;

use Illuminate\Foundation\Http\FormRequest;

class AdminCapNhatQuanTriVienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adminId = (int) $this->route('id');

        return [
            'ho_ten' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:150', "unique:nguoi_dungs,email,{$adminId}"],
            'mat_khau' => ['nullable', 'string', 'min:6'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20', 'regex:/^0[0-9]{9}$/'],
            'ngay_sinh' => ['nullable', 'date', 'before:today'],
            'gioi_tinh' => ['nullable', 'in:nam,nu,khac'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'trang_thai' => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}

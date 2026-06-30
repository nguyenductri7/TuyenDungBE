<?php

namespace App\Http\Requests\NguoiDung;

use Illuminate\Foundation\Http\FormRequest;

class AdminTaoNguoiDungRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ho_ten' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:nguoi_dungs,email'],
            'mat_khau' => ['required', 'string', 'min:6'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20', 'regex:/^0[0-9]{9}$/'],
            'ngay_sinh' => ['nullable', 'date', 'before:today'],
            'gioi_tinh' => ['nullable', 'in:nam,nu,khac'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'vai_tro' => ['required', 'integer', 'in:0,1,2'],
            'trang_thai' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ho_ten.required' => 'Họ tên không được để trống.',
            'email.required' => 'Email không được để trống.',
            'email.unique' => 'Email này đã được sử dụng.',
            'mat_khau.required' => 'Mật khẩu không được để trống.',
            'mat_khau.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'vai_tro.required' => 'Vai trò không được để trống.',
            'vai_tro.in' => 'Vai trò phải là: 0 (ứng viên), 1 (nhà tuyển dụng), 2 (admin).',
            'trang_thai.in' => 'Trạng thái phải là 0 (khoá) hoặc 1 (active).',
        ];
    }
}

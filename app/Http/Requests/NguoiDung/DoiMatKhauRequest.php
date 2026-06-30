<?php

namespace App\Http\Requests\NguoiDung;

use Illuminate\Foundation\Http\FormRequest;

class DoiMatKhauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mat_khau_cu' => ['required', 'string'],
            'mat_khau_moi' => ['required', 'string', 'min:6', 'confirmed', 'different:mat_khau_cu'],
            'mat_khau_moi_confirmation' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'mat_khau_cu.required' => 'Mật khẩu cũ không được để trống.',
            'mat_khau_moi.required' => 'Mật khẩu mới không được để trống.',
            'mat_khau_moi.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'mat_khau_moi.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'mat_khau_moi.different' => 'Mật khẩu mới phải khác mật khẩu cũ.',
        ];
    }
}

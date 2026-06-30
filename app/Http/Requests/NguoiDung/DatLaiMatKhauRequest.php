<?php

namespace App\Http\Requests\NguoiDung;

use Illuminate\Foundation\Http\FormRequest;

class DatLaiMatKhauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'mat_khau' => ['required', 'string', 'min:6'],
            'mat_khau_confirmation' => ['required', 'same:mat_khau'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'token.required' => 'Token đặt lại mật khẩu không được để trống.',
            'mat_khau.required' => 'Mật khẩu mới không được để trống.',
            'mat_khau.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'mat_khau_confirmation.required' => 'Vui lòng xác nhận mật khẩu mới.',
            'mat_khau_confirmation.same' => 'Mật khẩu xác nhận không khớp.',
        ];
    }
}

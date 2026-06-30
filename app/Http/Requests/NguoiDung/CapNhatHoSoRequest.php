<?php

namespace App\Http\Requests\NguoiDung;

use Illuminate\Foundation\Http\FormRequest;

class CapNhatHoSoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'ho_ten' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:150', "unique:nguoi_dungs,email,{$userId}"],
            'so_dien_thoai' => ['nullable', 'string', 'max:20', 'regex:/^0[0-9]{9}$/'],
            'ngay_sinh' => ['nullable', 'date', 'before:today'],
            'gioi_tinh' => ['nullable', 'in:nam,nu,khac'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'anh_dai_dien' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'ho_ten.max' => 'Họ tên tối đa 150 ký tự.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'so_dien_thoai.regex' => 'Số điện thoại không đúng định dạng.',
            'ngay_sinh.date' => 'Ngày sinh không đúng định dạng.',
            'ngay_sinh.before' => 'Ngày sinh phải là ngày trong quá khứ.',
            'gioi_tinh.in' => 'Giới tính phải là: nam, nu hoặc khac.',
            'anh_dai_dien.image' => 'Ảnh đại diện phải là file ảnh.',
            'anh_dai_dien.mimes' => 'Ảnh đại diện chỉ chấp nhận: jpeg, png, jpg, webp.',
            'anh_dai_dien.max' => 'Ảnh đại diện tối đa 2MB.',
        ];
    }
}

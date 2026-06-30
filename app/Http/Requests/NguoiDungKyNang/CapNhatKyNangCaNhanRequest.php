<?php

namespace App\Http\Requests\NguoiDungKyNang;

use App\Models\NguoiDungKyNang;
use Illuminate\Foundation\Http\FormRequest;

class CapNhatKyNangCaNhanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'muc_do' => ['sometimes', 'integer', 'in:' . implode(',', NguoiDungKyNang::MUC_DO_LIST)],
            'nam_kinh_nghiem' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'so_chung_chi' => ['sometimes', 'integer', 'min:0'],
            'hinh_anh' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'muc_do.in' => 'Mức độ phải từ 1 (Cơ bản) đến 5 (Chuyên gia).',
            'nam_kinh_nghiem.min' => 'Năm kinh nghiệm không được âm.',
            'nam_kinh_nghiem.max' => 'Năm kinh nghiệm tối đa 50.',
            'so_chung_chi.min' => 'Số chứng chỉ không được âm.',
            'hinh_anh.image' => 'File phải là hình ảnh.',
            'hinh_anh.mimes' => 'Hình ảnh chỉ chấp nhận: jpg, jpeg, png, webp.',
            'hinh_anh.max' => 'Hình ảnh tối đa 2MB.',
        ];
    }
}

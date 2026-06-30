<?php

namespace App\Http\Requests\NguoiDungKyNang;

use App\Models\NguoiDungKyNang;
use Illuminate\Foundation\Http\FormRequest;

class ThemKyNangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ky_nang_id' => ['required', 'integer', 'exists:ky_nangs,id'],
            'muc_do' => ['required', 'integer', 'in:' . implode(',', NguoiDungKyNang::MUC_DO_LIST)],
            'nam_kinh_nghiem' => ['nullable', 'integer', 'min:0', 'max:50'],
            'so_chung_chi' => ['nullable', 'integer', 'min:0'],
            'hinh_anh' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'ky_nang_id.required' => 'Vui lòng chọn kỹ năng.',
            'ky_nang_id.exists' => 'Kỹ năng không tồn tại.',
            'muc_do.required' => 'Vui lòng chọn mức độ thành thạo.',
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

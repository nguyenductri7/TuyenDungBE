<?php

namespace App\Http\Requests\NganhNghe;

use Illuminate\Foundation\Http\FormRequest;

class TaoNganhNgheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_nganh' => ['required', 'string', 'max:150'],
            'mo_ta' => ['nullable', 'string'],
            'danh_muc_cha_id' => ['nullable', 'integer', 'exists:nganh_nghes,id'],
            'icon' => ['nullable', 'string', 'max:100'],
            'trang_thai' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ten_nganh.required' => 'Tên ngành nghề không được để trống.',
            'ten_nganh.max' => 'Tên ngành nghề tối đa 150 ký tự.',
            'danh_muc_cha_id.exists' => 'Danh mục cha không tồn tại.',
            'icon.max' => 'Icon tối đa 100 ký tự.',
            'trang_thai.in' => 'Trạng thái phải là 0 (ẩn) hoặc 1 (hiển thị).',
        ];
    }
}

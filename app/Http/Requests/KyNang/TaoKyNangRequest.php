<?php

namespace App\Http\Requests\KyNang;

use Illuminate\Foundation\Http\FormRequest;

class TaoKyNangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_ky_nang' => ['required', 'string', 'max:150', 'unique:ky_nangs,ten_ky_nang'],
            'mo_ta' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'ten_ky_nang.required' => 'Tên kỹ năng không được để trống.',
            'ten_ky_nang.max' => 'Tên kỹ năng tối đa 150 ký tự.',
            'ten_ky_nang.unique' => 'Tên kỹ năng đã tồn tại.',
            'icon.max' => 'Icon tối đa 100 ký tự.',
        ];
    }
}

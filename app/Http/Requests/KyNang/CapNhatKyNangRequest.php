<?php

namespace App\Http\Requests\KyNang;

use Illuminate\Foundation\Http\FormRequest;

class CapNhatKyNangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'ten_ky_nang' => ['sometimes', 'string', 'max:150', "unique:ky_nangs,ten_ky_nang,{$id}"],
            'mo_ta' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'ten_ky_nang.max' => 'Tên kỹ năng tối đa 150 ký tự.',
            'ten_ky_nang.unique' => 'Tên kỹ năng đã tồn tại.',
            'icon.max' => 'Icon tối đa 100 ký tự.',
        ];
    }
}

<?php

namespace App\Http\Requests\CongTy;

use App\Models\CongTy;
use Illuminate\Foundation\Http\FormRequest;

class CapNhatCongTyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_cong_ty' => ['sometimes', 'string', 'max:200'],
            'ma_so_thue' => ['sometimes', 'string', 'max:20'],
            'mo_ta' => ['nullable', 'string'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'dien_thoai' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'website' => ['nullable', 'string', 'max:200'],
            'logo' => ['nullable', 'file', 'image', 'max:2048'],
            'nganh_nghe_id' => ['nullable', 'integer', 'exists:nganh_nghes,id'],
            'quy_mo' => ['nullable', 'string', 'in:' . implode(',', CongTy::QUY_MO_LIST)],
        ];
    }

    public function messages(): array
    {
        return [
            'ten_cong_ty.max' => 'Tên công ty tối đa 200 ký tự.',
            'ma_so_thue.max' => 'Mã số thuế tối đa 20 ký tự.',
            'email.email' => 'Email không đúng định dạng.',
            'nganh_nghe_id.exists' => 'Ngành nghề không tồn tại.',
            'quy_mo.in' => 'Quy mô không hợp lệ (1-10, 11-50, 51-200, 201-500, 500+).',
        ];
    }
}

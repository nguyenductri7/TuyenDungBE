<?php

namespace App\Http\Requests\CongTy;

use App\Models\CongTy;
use Illuminate\Foundation\Http\FormRequest;

class TaoCongTyRequest extends FormRequest
{
    public static function registrationRules(): array
    {
        return [
            'ten_cong_ty' => ['required', 'string', 'max:200'],
            'dien_thoai' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
        ];
    }

    public static function registrationMessages(): array
    {
        return [
            'ten_cong_ty.required' => 'Tên công ty không được để trống.',
            'ten_cong_ty.max' => 'Tên công ty tối đa 200 ký tự.',
            'email.email' => 'Email công ty không đúng định dạng.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_cong_ty' => ['required', 'string', 'max:200'],
            'ma_so_thue' => ['required', 'string', 'max:20', 'unique:cong_tys,ma_so_thue'],
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
        return self::registrationMessages() + [
            'ma_so_thue.required' => 'Mã số thuế không được để trống.',
            'ma_so_thue.max' => 'Mã số thuế tối đa 20 ký tự.',
            'ma_so_thue.unique' => 'Mã số thuế đã tồn tại.',
            'nganh_nghe_id.exists' => 'Ngành nghề không tồn tại.',
            'quy_mo.in' => 'Quy mô không hợp lệ (1-10, 11-50, 51-200, 201-500, 500+).',
        ];
    }
}

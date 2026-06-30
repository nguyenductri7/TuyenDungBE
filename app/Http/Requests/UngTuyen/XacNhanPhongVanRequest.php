<?php

namespace App\Http\Requests\UngTuyen;

use App\Models\UngTuyen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class XacNhanPhongVanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trang_thai_tham_gia_phong_van' => [
                'required',
                'integer',
                Rule::in(UngTuyen::PHONG_VAN_TRANG_THAI_LIST),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'trang_thai_tham_gia_phong_van.required' => 'Vui lòng chọn phản hồi phỏng vấn.',
            'trang_thai_tham_gia_phong_van.in' => 'Phản hồi phỏng vấn không hợp lệ.',
        ];
    }
}

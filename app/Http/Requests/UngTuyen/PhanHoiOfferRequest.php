<?php

namespace App\Http\Requests\UngTuyen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PhanHoiOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['accept', 'decline'])],
            'ghi_chu_phan_hoi_offer' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Vui lòng chọn phản hồi offer.',
            'action.in' => 'Phản hồi offer không hợp lệ.',
        ];
    }
}

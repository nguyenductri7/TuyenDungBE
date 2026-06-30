<?php

namespace App\Http\Requests\HoSo;

use App\Models\HoSo;
use App\Support\ExperienceValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminCapNhatHoSoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('trinh_do')) {
            $this->merge([
                'trinh_do' => HoSo::normalizeTrinhDo($this->input('trinh_do')),
            ]);
        }

        if ($this->has('kinh_nghiem_nam')) {
            $this->merge([
                'kinh_nghiem_nam' => ExperienceValue::normalize($this->input('kinh_nghiem_nam')),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tieu_de_ho_so' => ['sometimes', 'string', 'max:200'],
            'muc_tieu_nghe_nghiep' => ['nullable', 'string'],
            'trinh_do' => ['nullable', 'string', 'max:100', Rule::in(HoSo::acceptedTrinhDoValues())],
            'kinh_nghiem_nam' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'mo_ta_ban_than' => ['nullable', 'string'],
            'trang_thai' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'tieu_de_ho_so.max' => 'Tiêu đề hồ sơ tối đa 200 ký tự.',
            'trinh_do.in' => 'Trình độ không hợp lệ.',
            'kinh_nghiem_nam.numeric' => 'Kinh nghiệm năm phải là số hợp lệ, ví dụ: 0.5 hoặc 6 tháng.',
            'kinh_nghiem_nam.min' => 'Kinh nghiệm năm không được nhỏ hơn 0.',
            'kinh_nghiem_nam.max' => 'Kinh nghiệm năm không được lớn hơn 50.',
            'trang_thai.in' => 'Trạng thái phải là 0 (ẩn) hoặc 1 (công khai).',
        ];
    }
}

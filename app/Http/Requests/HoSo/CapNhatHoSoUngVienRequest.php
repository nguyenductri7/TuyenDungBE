<?php

namespace App\Http\Requests\HoSo;

use App\Models\HoSo;
use App\Support\ExperienceValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CapNhatHoSoUngVienRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $jsonFields = [
            'ky_nang_json',
            'kinh_nghiem_json',
            'hoc_van_json',
            'du_an_json',
            'chung_chi_json',
        ];

        $payload = [];

        foreach ($jsonFields as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $payload[$field] = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
        }

        if ($this->has('trinh_do')) {
            $payload['trinh_do'] = HoSo::normalizeTrinhDo($this->input('trinh_do'));
        }

        if ($this->has('kinh_nghiem_nam')) {
            $payload['kinh_nghiem_nam'] = ExperienceValue::normalize($this->input('kinh_nghiem_nam'));
        }

        if ($payload !== []) {
            $this->merge($payload);
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
            'file_cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'nguon_ho_so' => ['nullable', 'string', 'in:upload,builder,hybrid'],
            'mau_cv' => ['nullable', 'string', 'max:100'],
            'bo_cuc_cv' => ['nullable', 'string', 'in:executive_navy,topcv_maroon,ats_serif'],
            'ten_template_cv' => ['nullable', 'string', 'max:150'],
            'che_do_mau_cv' => ['nullable', 'string', 'in:style,position'],
            'vi_tri_ung_tuyen_muc_tieu' => ['nullable', 'string', 'max:150'],
            'ten_nganh_nghe_muc_tieu' => ['nullable', 'string', 'max:150'],
            'che_do_anh_cv' => ['nullable', 'string', 'in:profile,upload'],
            'anh_cv' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'ky_nang_json' => ['nullable', 'array'],
            'kinh_nghiem_json' => ['nullable', 'array'],
            'hoc_van_json' => ['nullable', 'array'],
            'du_an_json' => ['nullable', 'array'],
            'chung_chi_json' => ['nullable', 'array'],
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
            'file_cv.file' => 'File CV phải là một tệp tin.',
            'file_cv.mimes' => 'File CV chỉ chấp nhận: pdf, doc, docx.',
            'file_cv.max' => 'File CV tối đa 5MB.',
            'nguon_ho_so.in' => 'Nguồn hồ sơ không hợp lệ.',
            'che_do_mau_cv.in' => 'Chế độ template không hợp lệ.',
            'che_do_anh_cv.in' => 'Chế độ ảnh CV không hợp lệ.',
            'anh_cv.image' => 'Ảnh CV phải là file hình hợp lệ.',
            'anh_cv.mimes' => 'Ảnh CV chỉ chấp nhận: jpg, jpeg, png, webp.',
            'anh_cv.max' => 'Ảnh CV tối đa 2MB.',
            'trang_thai.in' => 'Trạng thái phải là 0 (ẩn) hoặc 1 (công khai).',
        ];
    }
}

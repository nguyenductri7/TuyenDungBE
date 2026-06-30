<?php

namespace App\Http\Requests\TinTuyenDung;

use App\Models\TinTuyenDung;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class TaoTinTuyenDungRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tieu_de' => ['required', 'string', 'max:200'],
            'mo_ta_cong_viec' => ['required', 'string'],
            'dia_diem_lam_viec' => ['required', 'string', 'max:255'],
            'hinh_thuc_lam_viec' => ['nullable', 'string', 'in:' . implode(',', TinTuyenDung::HINH_THUC_LIST)],
            'cap_bac' => ['nullable', 'string', 'max:50'],
            'so_luong_tuyen' => ['nullable', 'integer', 'min:1'],
            'muc_luong_tu' => ['nullable', 'integer', 'min:0'],
            'muc_luong_den' => ['nullable', 'integer', 'min:0', 'gte:muc_luong_tu'],
            'don_vi_luong' => ['nullable', 'string', 'max:50'],
            'kinh_nghiem_yeu_cau' => ['nullable', 'string', 'max:100'],
            'ngay_het_han' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        if (Carbon::parse((string) $value, 'UTC')->lt(now('UTC'))) {
                            $fail('Ngày giờ hết hạn phải lớn hơn thời điểm hiện tại.');
                        }
                    } catch (\Throwable) {
                        $fail('Ngày giờ hết hạn không hợp lệ.');
                    }
                },
            ],
            'trang_thai' => ['nullable', 'integer', 'in:0,1'],
            'hr_phu_trach_id' => ['nullable', 'integer', 'exists:nguoi_dungs,id'],
            'nganh_nghes' => ['required', 'array', 'min:1'],
            'nganh_nghes.*' => ['integer', 'exists:nganh_nghes,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('ngay_het_han')) {
            return;
        }

        try {
            $this->merge([
                'ngay_het_han' => Carbon::parse((string) $this->input('ngay_het_han'), 'Asia/Ho_Chi_Minh')
                    ->utc()
                    ->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Giữ nguyên giá trị để validator xử lý báo lỗi phù hợp.
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('muc_luong_den') && !$this->filled('muc_luong_tu')) {
                $validator->errors()->add('muc_luong_tu', 'Vui lòng nhập lương đầu tiên trước khi nhập lương cao nhất.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'tieu_de.required' => 'Tiêu đề không được để trống.',
            'mo_ta_cong_viec.required' => 'Mô tả công việc không được để trống.',
            'dia_diem_lam_viec.required' => 'Địa điểm làm việc không được để trống.',
            'nganh_nghes.required' => 'Vui lòng chọn ít nhất 1 ngành nghề.',
            'nganh_nghes.min' => 'Vui lòng chọn ít nhất 1 ngành nghề.',
            'nganh_nghes.*.exists' => 'Ngành nghề không tồn tại.',
            'so_luong_tuyen.min' => 'Số lượng tuyển phải lớn hơn 0.',
            'muc_luong_tu.min' => 'Lương thấp nhất không được nhỏ hơn 0.',
            'muc_luong_den.min' => 'Lương cao nhất không được nhỏ hơn 0.',
            'muc_luong_den.gte' => 'Lương cao nhất phải lớn hơn hoặc bằng lương thấp nhất.',
        ];
    }
}

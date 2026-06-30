<?php

namespace App\Http\Requests\UngTuyen;

use App\Models\UngTuyen;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CapNhatTrangThaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Phân quyền sẽ ở Controller/Middleware
    }

    public function rules(): array
    {
        return [
            'trang_thai' => [
                'required',
                'integer',
                Rule::in(UngTuyen::TRANG_THAI_LIST)
            ],
            'ngay_hen_phong_van' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        $targetStatus = (int) $this->input('trang_thai');

                        if ($targetStatus > UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN) {
                            return;
                        }

                        if (Carbon::parse((string) $value, 'UTC')->lt(now('UTC'))) {
                            $fail('Ngày giờ hẹn phỏng vấn phải lớn hơn hoặc bằng thời điểm hiện tại.');
                        }
                    } catch (\Throwable) {
                        $fail('Ngày giờ hẹn phỏng vấn không hợp lệ.');
                    }
                },
            ],
            'hinh_thuc_phong_van' => [
                'nullable',
                'string',
                Rule::in(['online', 'offline', 'phone']),
            ],
            'link_phong_van' => [
                'nullable',
                'string',
                'max:2048',
            ],
            'ket_qua_phong_van' => [
                'nullable',
                'string',
                'max:255'
            ],
            'ghi_chu' => [
                'nullable',
                'string',
                'max:5000'
            ]
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->filled('hinh_thuc_phong_van')) {
            $payload['hinh_thuc_phong_van'] = match (mb_strtolower(trim((string) $this->input('hinh_thuc_phong_van')))) {
                'online' => 'online',
                'offline', 'trực tiếp', 'truc tiep' => 'offline',
                'phone', 'điện thoại', 'dien thoai' => 'phone',
                default => $this->input('hinh_thuc_phong_van'),
            };
        }

        if ($this->filled('ngay_hen_phong_van')) {
            try {
                $payload['ngay_hen_phong_van'] = Carbon::parse((string) $this->input('ngay_hen_phong_van'), 'Asia/Ho_Chi_Minh')
                    ->utc()
                    ->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                // Giữ nguyên để validator xử lý.
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    public function attributes(): array
    {
        return [
            'trang_thai' => 'trạng thái',
            'ngay_hen_phong_van' => 'ngày giờ hẹn phỏng vấn',
            'hinh_thuc_phong_van' => 'hình thức phỏng vấn',
            'link_phong_van' => 'link phỏng vấn',
            'ket_qua_phong_van' => 'kết quả phỏng vấn',
            'ghi_chu' => 'ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'trang_thai.required' => 'Vui lòng cung cấp trạng thái mới.',
            'trang_thai.in' => 'Trạng thái không hợp lệ.',
            'hinh_thuc_phong_van.in' => 'Hình thức phỏng vấn không hợp lệ. Vui lòng chọn Online, Trực tiếp hoặc Điện thoại.',
        ];
    }
}

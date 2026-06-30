<?php

namespace App\Http\Requests\UngTuyen;

use Illuminate\Foundation\Http\FormRequest;

class NopHoSoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tin_tuyen_dung_id' => [
                'required',
                'integer',
                'exists:tin_tuyen_dungs,id'
            ],
            'ho_so_id' => [
                'required',
                'integer',
                // Phải là hồ sơ chưa bị xóa mềm, và thuộc về người dùng hiện tại
                // Chúng ta sẽ kiểm tra thuộc sở hữu ứng viên ở Controller, hoặc thêm rule ở đây
                'exists:ho_sos,id,deleted_at,NULL,nguoi_dung_id,' . auth()->id()
            ],
            'thu_xin_viec' => [
                'nullable',
                'string',
                'max:5000'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'tin_tuyen_dung_id.required' => 'Mã tin tuyển dụng không được để trống.',
            'tin_tuyen_dung_id.exists' => 'Tin tuyển dụng không tồn tại.',
            'ho_so_id.required' => 'Mã hồ sơ không được để trống.',
            'ho_so_id.exists' => 'Hồ sơ không hợp lệ hoặc không thuộc quyền sở hữu của bạn.',
        ];
    }
}

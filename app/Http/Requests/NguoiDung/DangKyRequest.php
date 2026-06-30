<?php

namespace App\Http\Requests\NguoiDung;

use Illuminate\Foundation\Http\FormRequest;

class DangKyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ho_ten' => ['required', 'string', 'max:150'],
            'ten_cong_ty' => ['required_if:vai_tro,1', 'nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:nguoi_dungs,email'],
            'mat_khau' => ['required', 'string', 'min:6'],
            'mat_khau_confirmation' => ['required', 'same:mat_khau'],
            'so_dien_thoai' => ['nullable', 'bail', 'string', 'regex:/^0[0-9]+$/', 'size:10'],
            'ngay_sinh' => ['nullable', 'date', 'before:today'],
            'gioi_tinh' => ['nullable', 'in:nam,nu,khac'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'vai_tro' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ho_ten.required' => 'Họ tên không được để trống.',
            'ho_ten.max' => 'Họ tên tối đa 150 ký tự.',
            'ten_cong_ty.required_if' => 'Tên công ty không được để trống với tài khoản nhà tuyển dụng.',
            'ten_cong_ty.max' => 'Tên công ty tối đa 150 ký tự.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'mat_khau.required' => 'Mật khẩu không được để trống.',
            'mat_khau.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'mat_khau_confirmation.required' => 'Vui lòng nhập lại mật khẩu.',
            'mat_khau_confirmation.same' => 'Xác nhận mật khẩu không khớp.',
            'so_dien_thoai.regex' => 'Số điện thoại không đúng định dạng, phải bắt đầu bằng 0 và chỉ chứa chữ số.',
            'so_dien_thoai.size' => 'Số điện thoại phải gồm đúng 10 số.',
            'ngay_sinh.date' => 'Ngày sinh không đúng định dạng.',
            'ngay_sinh.before' => 'Ngày sinh phải là ngày trong quá khứ.',
            'gioi_tinh.in' => 'Giới tính phải là: nam, nu hoặc khac.',
            'vai_tro.in' => 'Vai trò không hợp lệ (0: ứng viên, 1: nhà tuyển dụng).',
        ];
    }
}

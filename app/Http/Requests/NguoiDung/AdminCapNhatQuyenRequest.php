<?php

namespace App\Http\Requests\NguoiDung;

use App\Models\NguoiDung;
use Illuminate\Foundation\Http\FormRequest;

class AdminCapNhatQuyenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'quyen_admin' => ['required', 'array'],
            'quyen_admin.*' => ['boolean'],
        ];

        foreach (NguoiDung::adminPermissionKeys() as $permissionKey) {
            $rules["quyen_admin.{$permissionKey}"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'quyen_admin.required' => 'Danh sách quyền admin là bắt buộc.',
            'quyen_admin.array' => 'Danh sách quyền admin không hợp lệ.',
            'quyen_admin.*.boolean' => 'Giá trị quyền admin phải là true hoặc false.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $submittedKeys = array_keys((array) $this->input('quyen_admin', []));
            $unknownKeys = array_values(array_diff($submittedKeys, NguoiDung::adminPermissionKeys()));

            if ($unknownKeys !== []) {
                $validator->errors()->add(
                    'quyen_admin',
                    'Có quyền admin không hợp lệ: ' . implode(', ', $unknownKeys) . '.'
                );
            }
        });
    }
}

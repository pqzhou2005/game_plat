<?php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
            'mobile' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/', 'unique:users,mobile'],
            'real_name' => ['required_with:id_card', 'string', 'max:50', 'regex:/^[\x{4e00}-\x{9fa5}]+$/u'],
            'id_card' => ['required_with:real_name', 'string', 'regex:/^\d{17}[\dXx]$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '请输入用户名',
            'username.unique' => '该用户名已被注册',
            'username.min' => '用户名至少3位',
            'password.required' => '请输入密码',
            'password.confirmed' => '两次密码输入不一致',
            'mobile.regex' => '请输入正确的手机号',
            'mobile.unique' => '该手机号已被注册',
            'real_name.regex' => '请输入真实姓名',
            'id_card.regex' => '请输入正确的身份证号',
        ];
    }
}

<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ServerOpenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project' => 'required|string|max:100',
            'open_server' => 'required|integer',
            'open_server_time' => 'nullable|string',
            'created_role_num' => 'nullable|string',
            'preset_role_num' => 'nullable|string',
            'pay_num' => 'nullable|string',
            'preset_pay_num' => 'nullable|string',
            'preset_open_server' => 'nullable|integer',
            'preset_open_server_time' => 'nullable|string',
            'sur_dep_not_ser_num' => 'nullable|integer',
            'sign' => 'required|string',
        ];
    }
}

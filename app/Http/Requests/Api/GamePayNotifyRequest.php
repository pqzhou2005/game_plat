<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GamePayNotifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uid' => 'required|string',
            'serverId' => 'required|string',
            'orderId' => 'required|string',
            'money' => 'required|string',
            'goodsId' => 'required|string',
            'time' => 'required|string',
            'rid' => 'required|string',
            'ext' => 'nullable|string',
            'sign' => 'required|string',
        ];
    }
}

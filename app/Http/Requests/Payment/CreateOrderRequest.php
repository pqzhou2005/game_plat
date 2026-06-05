<?php
namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:99999'],
            'channel' => ['required', 'in:alipay,wechat'],
            'game_id' => ['nullable', 'exists:games,id'],
            'server_id' => ['nullable', 'exists:game_servers,id'],
            'game_account' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => '请输入充值金额',
            'amount.min' => '最低充值1元',
            'amount.max' => '单笔最高99999元',
            'channel.required' => '请选择支付方式',
            'channel.in' => '仅支持支付宝和微信支付',
        ];
    }
}

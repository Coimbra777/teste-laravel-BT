<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GatewayPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => 'required|integer|min:0',
        ];
    }
}

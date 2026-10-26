<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculatorLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:120', 'phone' => ['required', 'regex:/^[+0-9 ()-]{8,22}$/'], 'email' => 'required|email|max:200', 'location' => 'required|string|max:200', 'project_type' => 'required|string|max:100', 'timeline' => 'required|string|max:100', 'consultation_method' => 'required|in:phone,email,whatsapp,studio', 'consent' => 'required|accepted', 'website' => 'nullable|prohibited'];
    }
}

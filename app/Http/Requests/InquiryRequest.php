<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:120', 'phone' => ['required', 'regex:/^[+0-9 ()-]{8,22}$/'], 'email' => 'required|email|max:200', 'project_type' => 'required|string|max:100', 'location' => 'required|string|max:200', 'budget' => 'nullable|string|max:100', 'message' => 'required|string|min:10|max:5000', 'website' => 'nullable|prohibited', 'intent' => 'nullable|in:inquiry,whatsapp'];
    }
}

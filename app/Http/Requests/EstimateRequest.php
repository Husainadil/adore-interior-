<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:home,kitchen,room', 'property_type_id' => 'required_if:type,home|nullable|integer', 'area' => 'required_if:type,home|nullable|numeric|decimal:0,2|gt:0|lte:100000', 'package_id' => 'required_if:type,home|nullable|integer', 'bedrooms' => 'nullable|integer|min:0|max:30', 'kitchen_required' => 'nullable|boolean',
            'layout' => 'required_if:type,kitchen|nullable|in:straight,l-shaped,u-shaped,parallel,island', 'dimensions' => 'nullable|array:base,wall,tall,island,countertop', 'dimensions.*' => 'numeric|decimal:0,2|min:0|max:1000', 'materials' => 'nullable|array|max:12', 'materials.*' => 'integer|distinct',
            'items' => 'nullable|array|max:100', 'items.*' => 'array:service_id,quantity,room', 'items.*.service_id' => 'required|integer', 'items.*.quantity' => 'required|numeric|decimal:0,2|gt:0|lte:100000', 'items.*.room' => 'required|string|max:100',
            'addons' => 'nullable|array|max:30', 'addons.*' => 'array:addon_id,quantity', 'addons.*.addon_id' => 'required|integer|distinct', 'addons.*.quantity' => 'required|numeric|decimal:0,2|gt:0|lte:100000',
        ];
    }
}

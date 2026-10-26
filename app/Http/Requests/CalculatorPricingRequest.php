<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculatorPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $module = $this->route('module');
        if ($module === 'leads') {
            return ['status' => 'required|in:new,contacted,consultation,closed'];
        }
        if ($module === 'settings') {
            return ['enabled' => 'required|boolean', 'intro' => 'required|string|max:1000'];
        }
        if ($module === 'rules') {
            return ['calculator_type_id' => 'required|exists:calculator_types,id', 'name' => 'required|string|max:200', 'min_area' => 'required|numeric|decimal:0,2|min:1|max:100000', 'max_area' => 'required|numeric|decimal:0,2|gte:min_area|max:100000', 'area_basis' => 'required|in:carpet,built-up', 'tax_bps' => 'required|integer|min:0|max:5000', 'contingency_bps' => 'required|integer|min:0|max:5000', 'inclusions' => 'required|string|max:5000', 'exclusions' => 'required|string|max:5000', 'disclaimer' => 'required|string|max:5000', 'active' => 'nullable|boolean', 'is_demo' => 'nullable|boolean'];
        }
        if ($module === 'types') {
            return ['name' => 'required|string|max:200', 'description' => 'required|string|max:2000', 'image' => ['required', 'regex:#^/(?:images|storage/media)/[a-zA-Z0-9_.-]+$#'], 'active' => 'nullable|boolean', 'sort_order' => 'required|integer|min:0|max:10000'];
        }

        return ['calculator_type_id' => 'required|exists:calculator_types,id', 'code' => 'required|alpha_dash|max:100', 'name' => 'required|string|max:200', 'description' => 'nullable|string|max:2000', 'unit' => 'required|in:sqft,running_ft,item', 'billing_key' => 'required|alpha_dash|max:100', 'min_rate_rupees' => 'required|numeric|decimal:0,2|min:0|max:1000000', 'max_rate_rupees' => 'required|numeric|decimal:0,2|gte:min_rate_rupees|max:1000000', 'min_quantity' => 'required|numeric|decimal:0,2|min:0.01|max:100000', 'max_quantity' => 'required|numeric|decimal:0,2|gte:min_quantity|max:100000', 'features' => 'nullable|string|max:2000', 'included_keys' => 'nullable|string|max:2000', 'category' => 'nullable|in:carcass,finish,countertop', 'quantity_source' => 'nullable|in:cabinet_runs,countertop', 'dimension' => 'nullable|in:base,wall,tall,island', 'active' => 'nullable|boolean', 'is_demo' => 'nullable|boolean', 'sort_order' => 'required|integer|min:0|max:10000'];
    }
}

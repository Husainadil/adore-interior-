<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CalculatorEstimate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['inputs' => 'array', 'breakdown' => 'array', 'min_total' => 'integer', 'max_total' => 'integer', 'is_demo' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function lead(): HasOne
    {
        return $this->hasOne(CalculatorLead::class);
    }
}

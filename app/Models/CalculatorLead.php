<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculatorLead extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime'];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(CalculatorEstimate::class, 'calculator_estimate_id');
    }
}

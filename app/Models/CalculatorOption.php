<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculatorOption extends Model
{
    protected $guarded = ['id'];

    public static function for(string $table): static
    {
        return (new static)->setTable($table);
    }

    protected function casts(): array
    {
        return ['config' => 'array', 'active' => 'boolean', 'is_demo' => 'boolean', 'min_rate' => 'integer', 'max_rate' => 'integer'];
    }
}

<?php

namespace App\Services;

use App\Models\CalculatorOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InteriorCostEstimator
{
    public const OPTIONS = ['packages' => 'pricing_packages', 'properties' => 'calculator_property_types', 'materials' => 'calculator_materials', 'services' => 'calculator_services', 'addons' => 'calculator_addons'];

    public function catalog(): array
    {
        $data = ['types' => DB::table('calculator_types')->where('active', true)->orderBy('sort_order')->get()->map(fn ($row) => (array) $row)->all()];
        foreach (self::OPTIONS as $key => $table) {
            $query = CalculatorOption::for($table)->where('active', true)->orderBy('sort_order')->orderBy('id');
            if (app()->environment('production')) {
                $query->where('is_demo', false);
            }
            $data[$key] = $query->get()->toArray();
        }
        $rules = DB::table('calculator_pricing_rules')->where('active', true);
        if (app()->environment('production')) {
            $rules->where('is_demo', false);
        }
        $data['rules'] = $rules->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $data['settings'] = DB::table('calculator_settings')->pluck('value', 'key')->all();
        $data['enabled'] = ($data['settings']['enabled'] ?? '0') === '1';
        $data['demo_notice'] = ! app()->environment('production') && (collect($data['rules'])->contains('is_demo', true));

        return $data;
    }

    public static function hundredths(string|int|float $value): int
    {
        $text = (string) $value;
        if (! preg_match('/^\d{1,9}(?:\.\d{1,2})?$/', $text)) {
            throw ValidationException::withMessages(['quantity' => 'Use a positive quantity with at most two decimal places.']);
        }
        [$whole,$fraction] = array_pad(explode('.', $text, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    private function amount(int $rate, int $quantity): int
    {
        return intdiv($rate * $quantity + 50, 100);
    }

    private function percentage(int $amount, int $bps): int
    {
        return intdiv($amount * $bps + 5000, 10000);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['configuration' => $message]);
    }

    public function calculate(array $input): array
    {
        $catalog = $this->catalog();
        $type = collect($catalog['types'])->firstWhere('code', $input['type']);
        if (! $type || ! $catalog['enabled']) {
            $this->fail('Pricing available on consultation. Please contact our studio.');
        }
        $rule = collect($catalog['rules'])->firstWhere('calculator_type_id', $type['id']);
        if (! $rule) {
            $this->fail('Pricing available on consultation for this calculator.');
        }
        $options = [];
        foreach (self::OPTIONS as $key => $table) {
            $options[$key] = collect($catalog[$key])->where('calculator_type_id', $type['id'])->keyBy('id');
        }
        $package = null;
        $property = null;
        $included = [];
        $items = [];
        $seen = [];
        $billedKeys = [];
        $notes = [];
        $isDemo = (bool) $rule['is_demo'];
        $add = function (array $option, string|int|float $quantity, string $group, bool $forceIncluded = false) use (&$items, &$seen, &$billedKeys, &$notes, &$isDemo, &$included, $input): void {
            $units = self::hundredths($quantity);
            if ($units < self::hundredths($option['min_quantity']) || $units > self::hundredths($option['max_quantity']) || $units === 0) {
                $this->fail('Quantity for '.$option['name'].' is outside its configured limits.');
            }
            if ($option['unit'] === 'item' && $units % 100 !== 0) {
                $this->fail($option['name'].' must use whole item quantities.');
            }
            $key = ($input['type'] === 'room' ? mb_strtolower(trim($group)).':' : '').$option['billing_key'];
            if (isset($seen[$key])) {
                $notes[] = $option['name'].' was already counted in '.$group.'.';

                return;
            }
            $seen[$key] = true;
            $billedKeys[$option['billing_key']] = true;
            $covered = $forceIncluded || in_array($option['billing_key'], $included, true);
            $items[] = ['label' => $option['name'], 'group' => $group, 'unit' => $option['unit'], 'quantity' => number_format($units / 100, 2, '.', ''), 'min_rate' => (int) $option['min_rate'], 'max_rate' => (int) $option['max_rate'], 'min_total' => $covered ? 0 : $this->amount((int) $option['min_rate'], $units), 'max_total' => $covered ? 0 : $this->amount((int) $option['max_rate'], $units), 'included' => $covered];
            $isDemo = $isDemo || (bool) $option['is_demo'];
        };
        if ($input['type'] === 'home') {
            $property = $options['properties']->get($input['property_type_id'] ?? 0);
            $package = $options['packages']->get($input['package_id'] ?? 0);
            if (! $property || ! $package) {
                $this->fail('Choose an active property type and package.');
            }
            if ($package['min_rate'] <= 0 || $package['max_rate'] <= 0) {
                $this->fail('Approved package pricing is not configured. Please request a consultation.');
            }
            $area = self::hundredths($input['area']);
            if ($area < self::hundredths($rule['min_area']) || $area > self::hundredths($rule['max_area'])) {
                $this->fail('Enter '.$rule['area_basis'].' area between '.$rule['min_area'].' and '.$rule['max_area'].' sq ft.');
            }
            $add($package, $input['area'], 'Package base');
            $included = $package['config']['included_keys'] ?? [];
        }
        if ($input['type'] === 'kitchen') {
            $dimensions = $input['dimensions'] ?? [];
            $cabinetUnits = 0;
            foreach (['base', 'wall', 'island'] as $dimension) {
                $cabinetUnits += self::hundredths($dimensions[$dimension] ?? 0);
            }
            if ($cabinetUnits === 0 && self::hundredths($dimensions['tall'] ?? 0) === 0) {
                $this->fail('Enter measured cabinet running feet or tall unit quantities.');
            }
            if (($input['layout'] ?? '') === 'island' && self::hundredths($dimensions['island'] ?? 0) === 0) {
                $this->fail('Enter the island cabinet running feet for an island layout.');
            }
            if (($input['layout'] ?? '') !== 'island' && self::hundredths($dimensions['island'] ?? 0) > 0) {
                $this->fail('Choose the island layout to include island cabinetry.');
            }
            foreach ($options['services'] as $service) {
                $dimension = $service['config']['dimension'] ?? null;
                if ($dimension && self::hundredths($dimensions[$dimension] ?? 0) > 0) {
                    $add($service, $dimensions[$dimension], 'Kitchen cabinetry');
                }
            }
            foreach (['base', 'wall', 'tall', 'island'] as $dimension) {
                if (self::hundredths($dimensions[$dimension] ?? 0) > 0 && ! $options['services']->contains(fn ($service) => ($service['config']['dimension'] ?? null) === $dimension)) {
                    $this->fail('Approved pricing is unavailable for '.$dimension.' cabinets.');
                }
            }
            $selectedCategories = [];
            foreach ($input['materials'] ?? [] as $id) {
                $material = $options['materials']->get($id);
                if (! $material) {
                    $this->fail('Choose active kitchen materials.');
                }
                $category = $material['config']['category'] ?? '';
                if (isset($selectedCategories[$category])) {
                    $this->fail('Choose only one material per material category.');
                }
                $selectedCategories[$category] = $material['name'];
                $quantity = ($material['config']['quantity_source'] ?? '') === 'countertop' ? ($dimensions['countertop'] ?? 0) : number_format($cabinetUnits / 100, 2, '.', '');
                if (self::hundredths($quantity) > 0) {
                    $add($material, $quantity, 'Materials & finishes');
                }
            }
            foreach (['carcass', 'finish', 'countertop'] as $category) {
                if (! isset($selectedCategories[$category])) {
                    $this->fail('Select a '.$category.' material.');
                }
            }
            if (self::hundredths($dimensions['countertop'] ?? 0) === 0) {
                $this->fail('Enter the separately measured countertop area in sq ft.');
            }
            $notes[] = 'Base, wall and island cabinet materials use their measured running feet. Tall units include their configured standard carcass/finish. Countertops use separately measured square feet.';
        }
        if ($input['type'] === 'room' && empty($input['items'])) {
            $this->fail('Add at least one room work item with a measured quantity.');
        }
        foreach ($input['items'] ?? [] as $selection) {
            if ($input['type'] === 'kitchen') {
                $this->fail('Kitchen components must use the measured kitchen dimensions.');
            }
            $service = $options['services']->get($selection['service_id']);
            if (! $service) {
                $this->fail('This work item is not active for the chosen calculator.');
            }
            $add($service, $selection['quantity'], $selection['room']);
        }
        foreach ($input['addons'] ?? [] as $selection) {
            $addon = $options['addons']->get($selection['addon_id']);
            if (! $addon) {
                $this->fail('Choose active add-ons for this calculator.');
            }
            if (isset($billedKeys[$addon['billing_key']])) {
                $notes[] = $addon['name'].' was already included or counted; no second charge was added.';

                continue;
            }
            $add($addon, $selection['quantity'], 'Optional additions');
        }
        $base = collect($items)->where('group', '!=', 'Optional additions');
        $extras = collect($items)->where('group', 'Optional additions');
        $subtotal = ['min' => (int) collect($items)->sum('min_total'), 'max' => (int) collect($items)->sum('max_total')];
        if ($subtotal['max'] > 10000000000000) {
            $this->fail('This scope exceeds the online estimation limit. Please request a studio consultation.');
        }
        $allowance = ['min' => $this->percentage($subtotal['min'], (int) $rule['contingency_bps']), 'max' => $this->percentage($subtotal['max'], (int) $rule['contingency_bps'])];
        $tax = ['min' => $this->percentage($subtotal['min'] + $allowance['min'], (int) $rule['tax_bps']), 'max' => $this->percentage($subtotal['max'] + $allowance['max'], (int) $rule['tax_bps'])];
        $snapshot = ['catalog' => $catalog, 'rule' => $rule, 'rounding' => 'Integer paisa; quantity precision 0.01; round-half-up per line, allowance and tax. Tax applies after provisional allowance.'];
        $result = ['type' => $type['name'], 'type_id' => $type['id'], 'property' => $property['name'] ?? null, 'property_id' => $property['id'] ?? null, 'package' => $package['name'] ?? null, 'package_id' => $package['id'] ?? null, 'area_basis' => $rule['area_basis'], 'items' => $items, 'base' => ['min' => (int) $base->sum('min_total'), 'max' => (int) $base->sum('max_total')], 'addons' => ['min' => (int) $extras->sum('min_total'), 'max' => (int) $extras->sum('max_total')], 'subtotal' => $subtotal, 'allowance' => $allowance, 'tax' => $tax, 'tax_bps' => (int) $rule['tax_bps'], 'contingency_bps' => (int) $rule['contingency_bps'], 'total' => ['min' => $subtotal['min'] + $allowance['min'] + $tax['min'], 'max' => $subtotal['max'] + $allowance['max'] + $tax['max']], 'inclusions' => $rule['inclusions'], 'exclusions' => $rule['exclusions'], 'disclaimer' => $rule['disclaimer'], 'notes' => array_values(array_unique($notes)), 'is_demo' => $isDemo, 'version_hash' => hash('sha256',json_encode($snapshot,JSON_THROW_ON_ERROR)), 'snapshot' => $snapshot];

        return $result;
    }
}

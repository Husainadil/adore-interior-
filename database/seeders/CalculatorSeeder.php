<?php

namespace Database\Seeders;

use App\Models\CalculatorOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalculatorSeeder extends Seeder
{
    public function run(): void
    {
        $demo = app()->environment(['local', 'testing']);
        foreach (['home' => ['Complete Home Interior', 'A complete perspective on the place you call home.', 'villa'], 'kitchen' => ['Modular Kitchen', 'Measured cabinetry, considered materials and everyday rituals.', 'kitchen-study'], 'room' => ['Room-Wise Interior', 'Start with one room, one detail or one fresh possibility.', 'retreat']] as $code => [$name,$description,$image]) {
            CalculatorOption::for('calculator_types')->firstOrCreate(['code' => $code], ['name' => $name, 'description' => $description, 'image' => '/images/'.$image.'.jpg', 'active' => true, 'sort_order' => array_search($code, ['home', 'kitchen', 'room'])]);
        }
        $types = DB::table('calculator_types')->pluck('id', 'code');
        foreach ($types as $code => $id) {
            if (! DB::table('calculator_pricing_rules')->where('calculator_type_id', $id)->exists()) {
                DB::table('calculator_pricing_rules')->insert(['calculator_type_id' => $id, 'name' => ucfirst($code).' estimation rules', 'min_area' => 100, 'max_area' => 20000, 'area_basis' => 'carpet', 'tax_bps' => 0, 'contingency_bps' => 0, 'inclusions' => $code === 'home' ? 'Selected package scope. Included requirements are shown at no extra charge.' : 'Only the measured work items and selected materials shown in the breakdown.', 'exclusions' => 'Civil/structural work, appliances, loose decor, site-specific remedial work and any item not explicitly listed. No tax or allowance is applied unless configured.', 'disclaimer' => 'Indicative Interior Cost Estimate - Not a Final Quotation. Subject to verified measurements, material selection, scope and site assessment.'.($demo ? ' DEMO PRICING: illustrative development values, not approved Adore Interior commercial rates.' : ''), 'active' => $demo, 'is_demo' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $insert = function (string $table, string $type, string $code, string $name, string $unit, int $min, int $max, string $key, array $config = [], string $description = '') use ($types, $demo): void {
            CalculatorOption::for($table)->firstOrCreate(['code' => $code], ['calculator_type_id' => $types[$type], 'name' => $name, 'unit' => $unit, 'billing_key' => $key, 'description' => $description, 'min_rate' => $demo ? $min : 0, 'max_rate' => $demo ? $max : 0, 'min_quantity' => 0.01, 'max_quantity' => $unit === 'item' ? 100 : 100000, 'config' => $config, 'active' => $demo, 'is_demo' => true]);
        };
        foreach (['1 BHK', '2 BHK', '3 BHK', '4 BHK', 'Villa', 'Independent House', 'Commercial Space'] as $name) {
            $insert('calculator_property_types', 'home', strtolower(str_replace(' ', '-', $name)), $name, 'item', 0, 0, 'property-'.strtolower(str_replace(' ', '-', $name)));
        }
        foreach (['essential' => ['ESSENTIAL', 120000, 160000, ['Functional design', 'Standard materials', 'Practical finishes']], 'premium' => ['PREMIUM', 180000, 240000, ['Enhanced materials', 'Custom design options', 'Refined finishes']], 'luxury' => ['LUXURY', 280000, 400000, ['Bespoke detailing', 'High-end materials', 'Advanced customization']]] as $code => [$name,$min,$max,$features]) {
            $insert('pricing_packages', 'home', $code, $name, 'sqft', $min, $max, 'home-package', ['features' => $features, 'included_keys' => ['bedroom', 'kitchen', 'living', 'dining', 'wardrobe', 'tv-unit', 'ceiling', 'lighting', 'wall', 'storage', 'furniture']], implode('. ', $features).'. Scope is indicative and must be confirmed by the studio.');
        }
        $work = ['wardrobe' => ['Wardrobe', 'sqft', 150000, 200000], 'tv-unit' => ['TV Unit', 'running_ft', 350000, 500000], 'ceiling' => ['False Ceiling', 'sqft', 12000, 18000], 'wall' => ['Wall Finishes / Panels', 'sqft', 20000, 40000], 'storage' => ['Storage / Cabinetry', 'running_ft', 500000, 800000], 'furniture' => ['Custom Furniture', 'item', 2500000, 5000000], 'lighting' => ['Lighting', 'item', 150000, 400000], 'bedroom' => ['Bedroom Requirements', 'item', 500000, 1000000], 'living' => ['Living Room Requirements', 'item', 500000, 1000000], 'dining' => ['Dining Area Requirements', 'item', 500000, 1000000], 'kitchen' => ['Kitchen Requirements', 'item', 500000, 1000000]];
        foreach (['home', 'room'] as $type) {
            foreach ($work as $code => [$name,$unit,$min,$max]) {
                $insert('calculator_services', $type, $type.'-'.$code, $name, $unit, $min, $max, $code, [], $type === 'home' ? 'Included when listed in your selected whole-home package.' : 'Enter the measured quantity in the unit shown.');
            }
        }
        foreach (['base' => ['Base Cabinets', 'running_ft', 800000, 1100000], 'wall' => ['Wall Cabinets', 'running_ft', 600000, 900000], 'tall' => ['Tall Units', 'item', 2500000, 4000000], 'island' => ['Island Cabinets', 'running_ft', 1000000, 1400000]] as $dimension => [$name,$unit,$min,$max]) {
            $insert('calculator_services', 'kitchen', 'kitchen-'.$dimension, $name, $unit, $min, $max, 'cabinet-'.$dimension, ['dimension' => $dimension], $dimension === 'tall' ? 'Each tall unit includes its configured standard carcass and finish.' : 'Cabinet construction only; material/finish upgrades and countertops are separate lines.');
        }
        foreach (['Plywood', 'HDHMR', 'MDF', 'Other approved board'] as $i => $name) {
            $insert('calculator_materials', 'kitchen', 'carcass-'.$i, $name, 'running_ft', 100000 + $i * 20000, 150000 + $i * 20000, 'carcass', ['category' => 'carcass', 'quantity_source' => 'cabinet_runs'], 'Carcass upgrade per combined base, wall and island running feet.');
        }
        foreach (['Laminate', 'Acrylic', 'PU Finish', 'Veneer', 'Glass'] as $i => $name) {
            $insert('calculator_materials', 'kitchen', 'finish-'.$i, $name, 'running_ft', 60000 + $i * 30000, 100000 + $i * 40000, 'finish', ['category' => 'finish', 'quantity_source' => 'cabinet_runs'], 'Shutter finish per combined base, wall and island running feet.');
        }
        foreach (['Granite', 'Quartz', 'Marble'] as $i => $name) {
            $insert('calculator_materials', 'kitchen', 'countertop-'.$i, $name, 'sqft', 25000 + $i * 10000, 40000 + $i * 15000, 'countertop', ['category' => 'countertop', 'quantity_source' => 'countertop'], 'Separately measured countertop surface area in square feet.');
        }
        $extras = ['premium-hardware' => 'Premium hardware', 'designer-lighting' => 'Designer lighting', 'wall' => 'Decorative wall panels', 'countertop' => 'Premium countertops', 'smart-home' => 'Smart home provisions', 'furniture' => 'Custom furniture', 'storage' => 'Additional storage', 'surface' => 'Premium surface finishes'];
        foreach ($types as $type => $id) {
            foreach ($extras as $key => $name) {
                $insert('calculator_addons', $type, $type.'-addon-'.$key, $name, 'item', 500000, 1000000, $key, [], 'Per selected item. An already included or counted billing key is not charged twice.');
            }
        }
        foreach (['soft-close' => 'Soft-close hardware', 'baskets' => 'Pull-out baskets', 'corner' => 'Corner accessories', 'cutlery' => 'Cutlery organizers', 'pantry' => 'Pantry units', 'tall-storage' => 'Tall storage'] as $key => $name) {
            $insert('calculator_addons', 'kitchen', 'kitchen-addon-'.$key, $name, 'item', 100000, 300000, $key === 'tall-storage' ? 'cabinet-tall' : $key);
        }
        foreach (['enabled' => $demo ? '1' : '0', 'intro' => 'Explore the possibilities for your home with a personalized interior cost estimate.'] as $key => $value) {
            if (! DB::table('calculator_settings')->where('key', $key)->exists()) {
                DB::table('calculator_settings')->insert(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}

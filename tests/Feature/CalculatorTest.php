<?php

namespace Tests\Feature;

use App\Http\Controllers\CalculatorAdminController as CalculatorAdminControllerAlias;
use App\Models\CalculatorEstimate;
use App\Models\CalculatorLead;
use App\Models\CalculatorOption;
use App\Models\User;
use App\Services\InteriorCostEstimator;
use Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_preserves_admin_content_and_approved_rates(): void
    {
        DB::table('calculator_types')->where('code', 'home')->update(['name' => 'Our edited home calculator', 'active' => false]);
        $this->option('pricing_packages', 'essential')->update(['min_rate' => 225000, 'is_demo' => false]);
        $this->seed(CalculatorSeeder::class);
        $this->assertDatabaseHas('calculator_types', ['code' => 'home', 'name' => 'Our edited home calculator', 'active' => false]);
        $this->assertDatabaseHas('pricing_packages', ['code' => 'essential', 'min_rate' => 225000, 'is_demo' => false]);
    }

    public function test_admin_can_publish_approved_rates_and_disable_packages(): void
    {
        $package = $this->option('pricing_packages', 'essential');
        $this->actingAs($this->admin());
        $data = ['calculator_type_id' => $package->calculator_type_id, 'code' => $package->code, 'name' => 'Approved Essential', 'unit' => 'sqft', 'billing_key' => 'home-package', 'min_rate_rupees' => '2000.01', 'max_rate_rupees' => '2500.02', 'min_quantity' => '1', 'max_quantity' => '100000', 'sort_order' => 0, 'active' => 1, 'is_demo' => 0, 'features' => 'Approved scope', 'included_keys' => 'wardrobe'];
        $this->post('/admin/calculator/packages/'.$package->id, $data)->assertRedirect('/admin/calculator/packages');
        $this->assertDatabaseHas('pricing_packages', ['id' => $package->id, 'min_rate' => 200001, 'max_rate' => 250002, 'is_demo' => 0]);
        $this->postJson(route('calculator.preview'), $this->home())->assertOk()->assertJsonPath('total.min', 200001000);
        $data['unit'] = 'running_ft';
        $this->post('/admin/calculator/packages/'.$package->id, $data)->assertSessionHasErrors('unit');
        $data['unit'] = 'sqft';
        $data['active'] = 0;
        $this->post('/admin/calculator/packages/'.$package->id, $data)->assertRedirect();
        $this->postJson(route('calculator.preview'), $this->home())->assertUnprocessable();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CalculatorSeeder::class);
        $this->withoutVite();
    }

    private function option(string $table, string $code): CalculatorOption
    {
        return CalculatorOption::for($table)->where('code', $code)->firstOrFail();
    }

    private function home(string $package = 'essential'): array
    {
        return ['type' => 'home', 'property_type_id' => $this->option('calculator_property_types', '2-bhk')->id, 'area' => '1000', 'package_id' => $this->option('pricing_packages', $package)->id, 'bedrooms' => 2, 'items' => [], 'addons' => []];
    }

    private function kitchen(): array
    {
        return ['type' => 'kitchen', 'layout' => 'l-shaped', 'dimensions' => ['base' => '10', 'wall' => '6', 'tall' => '1', 'island' => '0', 'countertop' => '20'], 'materials' => [$this->option('calculator_materials', 'carcass-0')->id, $this->option('calculator_materials', 'finish-0')->id, $this->option('calculator_materials', 'countertop-0')->id], 'addons' => []];
    }

    private function storeEstimate(?array $input = null): CalculatorEstimate
    {
        $response = $this->postJson(route('calculator.store'), $input ?? $this->home())->assertCreated();

        return CalculatorEstimate::where('reference', $response->json('reference'))->firstOrFail();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    public function test_landing_has_three_choices_and_does_not_require_contact_details(): void
    {
        $this->get(route('calculator.index'))->assertOk()->assertSee('Plan Your Dream Interior')->assertSee('studio-approved pricing')->assertSee('Complete Home Interior')->assertSee('Modular Kitchen')->assertSee('Room-Wise Interior');
        $this->assertDatabaseCount('calculator_leads', 0);
    }

    public function test_public_wizard_excludes_demo_options_but_preserves_approved_options(): void
    {
        $this->option('pricing_packages', 'essential')->update(['name' => 'Unapproved UI package', 'is_demo' => true]);
        $this->option('pricing_packages', 'premium')->update(['name' => 'Approved UI package', 'is_demo' => false]);

        $this->get(route('calculator.index'))->assertOk()
            ->assertDontSee('Unapproved UI package')
            ->assertSee('Approved UI package');
    }

    public function test_home_package_switch_and_client_total_manipulation(): void
    {
        $this->postJson(route('calculator.preview'), $this->home() + ['total' => 1, 'min_total' => 1])->assertOk()->assertJsonPath('total.min', 120000000)->assertJsonPath('total.max', 160000000);
        $this->postJson(route('calculator.preview'), $this->home('premium'))->assertOk()->assertJsonPath('total.min', 180000000);
        $this->assertDatabaseCount('calculator_estimates', 0);
    }

    public function test_invalid_negative_zero_extreme_and_cross_type_selections_are_rejected(): void
    {
        foreach (['-1', '0', '20000.01', '1000000', '100.001'] as $area) {
            $input = $this->home();
            $input['area'] = $area;
            $this->postJson(route('calculator.preview'), $input)->assertUnprocessable();
        }$input = $this->home();
        $input['package_id'] = 999999;
        $this->postJson(route('calculator.preview'), $input)->assertUnprocessable();
        $input = $this->home();
        $input['items'] = [['service_id' => $this->option('calculator_services', 'kitchen-base')->id, 'quantity' => 1, 'room' => 'Bedroom']];
        $this->postJson(route('calculator.preview'), $input)->assertUnprocessable();
    }

    public function test_kitchen_uses_measured_components_and_material_units_not_floor_area(): void
    {
        $input = $this->kitchen();
        $this->postJson(route('calculator.preview'), $input + ['area' => 99999])->assertOk()->assertJsonPath('total.min', 17160000);
        $this->postJson(route('calculator.preview'), $input + ['area' => 1])->assertOk()->assertJsonPath('total.min', 17160000);
        $input['dimensions']['tall'] = '1.5';
        $this->postJson(route('calculator.preview'), $input)->assertUnprocessable();
    }

    public function test_kitchen_rejects_duplicate_material_categories_and_inactive_components(): void
    {
        $input = $this->kitchen();
        $input['materials'][] = $this->option('calculator_materials', 'carcass-1')->id;
        $this->postJson(route('calculator.preview'), $input)->assertUnprocessable();
        $this->option('calculator_services', 'kitchen-base')->update(['active' => false]);
        $this->postJson(route('calculator.preview'), $this->kitchen())->assertUnprocessable();
    }

    public function test_room_quantities_and_duplicate_charges(): void
    {
        $service = $this->option('calculator_services', 'room-wardrobe');
        $input = ['type' => 'room', 'items' => [['service_id' => $service->id, 'quantity' => '20', 'room' => 'Bedroom 1'], ['service_id' => $service->id, 'quantity' => '20', 'room' => 'Bedroom 1'], ['service_id' => $service->id, 'quantity' => '20', 'room' => 'Bedroom 2']]];
        $this->postJson(route('calculator.preview'), $input)->assertOk()->assertJsonPath('total.min', 6000000)->assertJsonCount(2, 'items');
    }

    public function test_package_inclusions_and_duplicate_addon_keys_do_not_charge_twice(): void
    {
        $input = $this->home();
        $input['items'] = [['service_id' => $this->option('calculator_services', 'home-furniture')->id, 'quantity' => 2, 'room' => 'Living']];
        $input['addons'] = [['addon_id' => $this->option('calculator_addons', 'home-addon-furniture')->id, 'quantity' => 2], ['addon_id' => $this->option('calculator_addons', 'home-addon-smart-home')->id, 'quantity' => 2]];
        $this->postJson(route('calculator.preview'), $input)->assertOk()->assertJsonPath('total.min', 121000000)->assertJsonPath('items.1.included', true)->assertJsonCount(3, 'items');
    }

    public function test_tax_allowance_and_decimal_rounding_are_explicit(): void
    {
        $type = DB::table('calculator_types')->where('code', 'home')->value('id');
        DB::table('calculator_pricing_rules')->where('calculator_type_id', $type)->update(['tax_bps' => 1800, 'contingency_bps' => 500]);
        $this->postJson(route('calculator.preview'), $this->home())->assertOk()->assertJsonPath('allowance.min', 6000000)->assertJsonPath('tax.min', 22680000)->assertJsonPath('total.min', 148680000);
        $this->option('pricing_packages', 'essential')->update(['min_rate' => 101, 'max_rate' => 101]);
        DB::table('calculator_pricing_rules')->where('calculator_type_id', $type)->update(['tax_bps' => 0, 'contingency_bps' => 0]);
        $input = $this->home();
        $input['area'] = '100.01';
        $this->postJson(route('calculator.preview'), $input)->assertOk()->assertJsonPath('total.min', 10101);
    }

    public function test_rate_updates_preserve_historical_estimates_and_versions(): void
    {
        $first = $this->storeEstimate();
        $this->option('pricing_packages', 'essential')->update(['min_rate' => 125000]);
        $second = $this->storeEstimate();
        $this->assertSame(120000000, $first->fresh()->min_total);
        $this->assertSame(125000000, $second->min_total);
        $this->assertNotSame($first->calculator_price_version_id, $second->calculator_price_version_id);
        $this->assertDatabaseCount('calculator_price_versions', 2);
        $this->assertDatabaseHas('calculator_estimate_items', ['calculator_estimate_id' => $first->id, 'min_rate' => 120000]);
    }

    public function test_pdf_requires_valid_capability_and_contains_pdf_data(): void
    {
        $estimate = $this->storeEstimate();
        $this->get('/interior-cost-calculator/estimate/'.$estimate->public_id.'/pdf')->assertForbidden();
        $response = $this->get(URL::signedRoute('calculator.pdf', ['estimate' => $estimate]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        if (getenv('CALCULATOR_PDF_QA')) {
            file_put_contents(getenv('CALCULATOR_PDF_QA'), $response->getContent());
        }$this->get(URL::signedRoute('calculator.result', ['estimate' => $estimate]))->assertOk()->assertSee('Not a Final Quotation')->assertSee('Make it');
    }

    public function test_optional_lead_consent_storage_and_whatsapp_privacy(): void
    {
        $estimate = $this->storeEstimate();
        $this->assertDatabaseCount('calculator_leads', 0);
        $url = URL::signedRoute('calculator.lead', ['estimate' => $estimate]);
        $data = ['name' => 'Private Visitor', 'phone' => '+91 98765 43210', 'email' => 'private@example.com', 'location' => 'Ahmedabad', 'project_type' => 'Villa', 'timeline' => 'Within 3 months', 'consultation_method' => 'phone'];
        $this->postJson($url, $data)->assertUnprocessable();
        $this->post($url, $data + ['consent' => 1])->assertRedirect();
        $this->assertDatabaseHas('calculator_leads', ['calculator_estimate_id' => $estimate->id, 'email' => 'private@example.com', 'status' => 'new']);
        $this->post($url, $data + ['consent' => 1])->assertRedirect();
        $this->assertDatabaseCount('calculator_leads', 1);
        $response = $this->get(URL::signedRoute('calculator.whatsapp', ['estimate' => $estimate]))->assertRedirect();
        $message = rawurldecode($response->headers->get('Location'));
        $this->assertStringContainsString($estimate->reference, $message);
        $this->assertStringContainsString('1000 sq ft', $message);
        $this->assertStringNotContainsString('private@example.com', $message);
        $this->assertStringNotContainsString('Private Visitor', $message);
        $this->assertStringNotContainsString('98765', $message);
    }

    public function test_admin_authorization_and_all_calculator_modules_render(): void
    {
        $this->get('/admin/calculator')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/calculator')->assertForbidden();
        $this->actingAs($this->admin());
        foreach (CalculatorAdminControllerAlias::MODULES as $module => $label) {
            $this->get($module === 'overview' ? '/admin/calculator' : '/admin/calculator/'.$module)->assertOk();
            if (! in_array($module, ['overview', 'leads', 'estimates', 'settings'], true)) {
                $this->get('/admin/calculator/'.$module.'/create')->assertOk();
            }
        }
    }

    public function test_admin_updates_lead_status_and_exports_csv_safely(): void
    {
        $estimate = $this->storeEstimate();
        $lead = CalculatorLead::create(['calculator_estimate_id' => $estimate->id, 'name' => '=1+1', 'phone' => '+919999999999', 'email' => 'csv@example.com', 'location' => 'Ahmedabad', 'project_type' => 'Home', 'timeline' => 'Soon', 'consultation_method' => 'email', 'consented_at' => now()]);
        $this->actingAs($this->admin())->post('/admin/calculator/leads/'.$lead->id, ['status' => 'contacted'])->assertRedirect();
        $this->assertSame('contacted', $lead->fresh()->status);
        $response = $this->get('/admin/calculator/leads/export')->assertOk();
        $this->assertStringContainsString("'=1+1", $response->streamedContent());
        $this->get('/admin/calculator/estimates/'.$estimate->public_id)->assertOk();
    }

    public function test_demo_rates_are_never_used_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        try {
            $catalog = app(InteriorCostEstimator::class)->catalog();
            $this->assertSame([], $catalog['packages']);
            $this->expectException(ValidationException::class);
            app(InteriorCostEstimator::class)->calculate($this->home());
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_public_estimate_endpoints_are_rate_limited(): void
    {
        for ($index = 0; $index < 120; $index++) {
            $this->postJson(route('calculator.preview'), ['type' => 'invalid'])->assertUnprocessable();
        }$this->postJson(route('calculator.preview'), ['type' => 'invalid'])->assertTooManyRequests();
    }
}

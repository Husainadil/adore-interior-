<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculatorPricingRequest;
use App\Models\CalculatorEstimate;
use App\Models\CalculatorLead;
use App\Models\CalculatorOption;
use App\Services\InteriorCostEstimator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalculatorAdminController extends Controller
{
    public const MODULES = ['overview' => 'Calculator Overview', 'packages' => 'Pricing Packages', 'properties' => 'Property Types', 'services' => 'Service Pricing', 'materials' => 'Material Pricing', 'addons' => 'Add-on Pricing', 'rules' => 'Estimation Rules', 'leads' => 'Calculator Leads', 'estimates' => 'Generated Estimates', 'settings' => 'Calculator Settings', 'types' => 'Calculator Choices'];

    private function authorizeAdmin(): void
    {
        Gate::authorize('viewAny', CalculatorEstimate::class);
    }

    private function table(string $module): string
    {
        return InteriorCostEstimator::OPTIONS[$module] ?? match ($module) {
            'types' => 'calculator_types','rules' => 'calculator_pricing_rules','leads' => 'calculator_leads','estimates' => 'calculator_estimates',default => abort(404)
        };
    }

    private function data(string $module): array
    {
        return ['modules' => AdminController::TABLES, 'calculatorModules' => self::MODULES, 'module' => 'calculator-'.$module, 'calculatorModule' => $module, 'types' => DB::table('calculator_types')->get()];
    }

    public function index(Request $request, string $module = 'overview'): View
    {
        $this->authorizeAdmin();
        abort_unless(isset(self::MODULES[$module]), 404);
        $rows = null;
        $stats = null;
        if ($module === 'overview') {
            $count = CalculatorEstimate::count();
            $leads = CalculatorLead::count();
            $stats = ['estimates' => $count, 'leads' => $leads, 'conversion' => $count ? number_format($leads * 100 / $count, 1) : '0.0', 'packages' => DB::table('calculator_estimates')->join('pricing_packages', 'pricing_packages.id', '=', 'calculator_estimates.pricing_package_id')->select('pricing_packages.name')->selectRaw('COUNT(*) as total')->groupBy('pricing_packages.id', 'pricing_packages.name')->orderByDesc('total')->limit(5)->get(), 'properties' => DB::table('calculator_estimates')->join('calculator_property_types', 'calculator_property_types.id', '=', 'calculator_estimates.calculator_property_type_id')->select('calculator_property_types.name')->selectRaw('COUNT(*) as total')->groupBy('calculator_property_types.id', 'calculator_property_types.name')->orderByDesc('total')->limit(5)->get(), 'recent' => CalculatorLead::with('estimate')->latest()->limit(5)->get()];
        } elseif ($module !== 'settings') {
            $query = DB::table($this->table($module));
            $field = match ($module) {
                'estimates' => 'reference',default => 'name'
            };
            if ($request->filled('q')) {
                $query->where($field, 'like', '%'.$request->string('q').'%');
            }
            if ($module === 'leads' && $request->filled('status')) {
                $query->where('status', $request->string('status'));
            }
            $rows = $query->orderByDesc('id')->paginate(15)->withQueryString();
        }

        return view('admin.calculator.index', $this->data($module) + ['rows' => $rows, 'stats' => $stats, 'settings' => DB::table('calculator_settings')->pluck('value', 'key')]);
    }

    public function edit(string $module, ?int $id = null): View
    {
        $this->authorizeAdmin();
        abort_if(in_array($module, ['leads', 'estimates', 'overview', 'settings'], true), 404);
        $record = $id ? DB::table($this->table($module))->where('id', $id)->first() : null;
        abort_if($id && ! $record, 404);

        return view('admin.calculator.edit', $this->data($module) + ['record' => $record]);
    }

    public function save(CalculatorPricingRequest $request, string $module, ?int $id = null): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_unless(isset(self::MODULES[$module]) && ! in_array($module, ['overview', 'estimates'], true), 404);
        $data = $request->validated();
        if ($module === 'settings') {
            foreach ($data as $key => $value) {
                DB::table('calculator_settings')->updateOrInsert(['key' => $key], ['value' => (string) $value, 'created_at' => now(), 'updated_at' => now()]);
            }

            return back()->with('success', 'Calculator settings saved.');
        }
        $table = $this->table($module);
        if ($id) {
            abort_unless(DB::table($table)->where('id', $id)->exists(), 404);
        }
        if ($module === 'leads') {
            abort_unless($id, 404);
            DB::table($table)->where('id', $id)->update(['status' => $data['status'], 'updated_at' => now()]);

            return back()->with('success', 'Lead status updated.');
        }
        $data['active'] = $request->boolean('active');
        if ($module !== 'types') {
            $data['is_demo'] = $request->boolean('is_demo');
        }
        if (in_array($module, array_keys(InteriorCostEstimator::OPTIONS), true)) {
            $request->validate(['code' => 'unique:'.$table.',code'.($id ? ','.$id : '')]);
            $data['min_rate'] = InteriorCostEstimator::hundredths($data['min_rate_rupees']);
            $data['max_rate'] = InteriorCostEstimator::hundredths($data['max_rate_rupees']);
            $type = DB::table('calculator_types')->where('id', $data['calculator_type_id'])->value('code');
            $config = ['features' => array_values(array_filter(array_map('trim', explode("\n", $data['features'] ?? '')))), 'included_keys' => array_values(array_filter(array_map('trim', explode(',', $data['included_keys'] ?? ''))))];
            if ($module === 'packages' && ($type !== 'home' || $data['unit'] !== 'sqft')) {
                throw ValidationException::withMessages(['unit' => 'Home packages must use square-foot pricing.']);
            }
            if ($module === 'properties' && $type !== 'home') {
                throw ValidationException::withMessages(['calculator_type_id' => 'Property types belong to the complete home calculator.']);
            }
            if ($module === 'materials') {
                if ($type !== 'kitchen' || empty($data['category'])) {
                    throw ValidationException::withMessages(['category' => 'Select a kitchen material category.']);
                }
                $category = $data['category'];
                $source = $category === 'countertop' ? 'countertop' : 'cabinet_runs';
                $unit = $category === 'countertop' ? 'sqft' : 'running_ft';
                if ($data['unit'] !== $unit) {
                    throw ValidationException::withMessages(['unit' => 'Use '.$unit.' for '.$category.' pricing.']);
                }
                $config += ['category' => $category, 'quantity_source' => $source];
            }
            if ($module === 'services' && $type === 'kitchen') {
                if (empty($data['dimension'])) {
                    throw ValidationException::withMessages(['dimension' => 'Choose the kitchen dimension this component prices.']);
                }
                $dimension = $data['dimension'];
                $unit = $dimension === 'tall' ? 'item' : 'running_ft';
                if ($data['unit'] !== $unit) {
                    throw ValidationException::withMessages(['unit' => 'Use '.$unit.' for this kitchen dimension.']);
                }
                $others = CalculatorOption::for($table)->where('calculator_type_id', $data['calculator_type_id'])->where('active', true)->when($id, fn ($q) => $q->where('id', '!=', $id))->get();
                if ($data['active'] && $others->contains(fn ($row) => ($row->config['dimension'] ?? null) === $dimension)) {
                    throw ValidationException::withMessages(['dimension' => 'An active price already exists for this dimension. Edit it or disable it first.']);
                }
                $config['dimension'] = $dimension;
            }
            foreach (['min_rate_rupees', 'max_rate_rupees', 'features', 'included_keys', 'category', 'quantity_source', 'dimension'] as $field) {
                unset($data[$field]);
            }$data['config'] = json_encode($config, JSON_THROW_ON_ERROR);
        }
        if ($module === 'rules') {
            $request->validate(['calculator_type_id' => 'unique:calculator_pricing_rules,calculator_type_id'.($id ? ','.$id : '')]);
        }
        $data['updated_at'] = now();
        if ($id) {
            DB::table($table)->where('id', $id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table($table)->insert($data);
        }

        return redirect()->route('calculator.admin.module', ['module' => $module])->with('success', 'Calculator configuration saved. Historical estimates retain their original prices.');
    }

    public function show(CalculatorEstimate $estimate): View
    {
        $this->authorizeAdmin();
        Gate::authorize('view', $estimate);

        return view('admin.calculator.estimate', $this->data('estimates') + ['estimate' => $estimate, 'result' => $estimate->breakdown]);
    }

    public function pdf(CalculatorEstimate $estimate, CalculatorController $controller): Response
    {
        $this->authorizeAdmin();
        Gate::authorize('view', $estimate);

        return $controller->pdf($estimate);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();
        $query = CalculatorLead::with('estimate')->orderBy('id');
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Name', 'Phone', 'Email', 'Location', 'Project type', 'Timeline', 'Method', 'Status', 'Submitted'], ',', '"', '');
            foreach ($query->lazy(100) as $lead) {
                $values = [$lead->estimate->reference, $lead->name, $lead->phone, $lead->email, $lead->location, $lead->project_type, $lead->timeline, $lead->consultation_method, $lead->status, $lead->created_at->toIso8601String()];
                $safe = array_map(fn ($v) => preg_match('/^[\s]*[=+@\-\t\r]/u', (string) $v) ? "'".$v : $v, $values);
                fputcsv($out, $safe, ',', '"', '');
            }fclose($out);
        }, 'calculator-leads.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}

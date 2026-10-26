<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculatorLeadRequest;
use App\Http\Requests\EstimateRequest;
use App\Models\CalculatorEstimate;
use App\Models\Content;
use App\Services\InteriorCostEstimator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CalculatorController extends Controller
{
    public function __construct(private InteriorCostEstimator $estimator) {}

    private function context(): array
    {
        return ['page' => 'calculator', 'record' => null, 'settings' => DB::table('website_settings')->pluck('value', 'key'), 'seo' => DB::table('seo_metadata')->where('path', 'interior-cost-calculator')->first(), 'banners' => Content::for('banners')->where('published', true)->orderBy('sort_order')->get()];
    }

    public function index(): View
    {
        return view('calculator.index', $this->context() + ['catalog' => $this->estimator->catalog()]);
    }

    public function preview(EstimateRequest $request): JsonResponse
    {
        $result = $this->estimator->calculate($request->validated());
        unset($result['snapshot']);

        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    public function store(EstimateRequest $request): JsonResponse
    {
        $estimate = DB::transaction(function () use ($request): CalculatorEstimate {
            $result = $this->estimator->calculate($request->validated());
            DB::table('calculator_price_versions')->insertOrIgnore(['hash' => $result['version_hash'], 'snapshot' => json_encode($result['snapshot'], JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
            $version = DB::table('calculator_price_versions')->where('hash', $result['version_hash'])->value('id');
            unset($result['snapshot']);
            $estimate = CalculatorEstimate::create(['public_id' => (string) Str::uuid(), 'reference' => 'AD-'.Str::ulid(), 'calculator_type_id' => $result['type_id'], 'pricing_package_id' => $result['package_id'], 'calculator_property_type_id' => $result['property_id'], 'calculator_price_version_id' => $version, 'inputs' => $request->validated(), 'breakdown' => $result, 'min_total' => $result['total']['min'], 'max_total' => $result['total']['max'], 'is_demo' => $result['is_demo']]);
            foreach ($result['items'] as $item) {
                DB::table('calculator_estimate_items')->insert($item + ['calculator_estimate_id' => $estimate->id, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $estimate;
        });

        return response()->json(['url' => URL::temporarySignedRoute('calculator.result', now()->addDays(7), ['estimate' => $estimate]), 'reference' => $estimate->reference], 201)->header('Cache-Control', 'no-store');
    }

    public function result(CalculatorEstimate $estimate): Response
    {
        return response()->view('calculator.result', $this->context() + ['estimate' => $estimate, 'result' => $estimate->breakdown])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function lead(CalculatorLeadRequest $request, CalculatorEstimate $estimate): RedirectResponse
    {
        $estimate->lead()->firstOrCreate([], $request->safe()->except(['consent', 'website']) + ['consented_at' => now()]);

        return back()->with('calculator_success', 'Thank you. Your estimate and consultation request have been saved for our studio.');
    }

    public function whatsapp(CalculatorEstimate $estimate): RedirectResponse
    {
        $input = $estimate->inputs;
        $result = $estimate->breakdown;
        $details = $input['type'] === 'home' ? ($input['area'].' sq ft '.$result['area_basis'].' area') : ($input['type'] === 'kitchen' ? collect($input['dimensions'] ?? [])->map(fn ($qty, $key) => $key.': '.$qty.($key === 'countertop' ? ' sq ft' : ($key === 'tall' ? ' units' : ' running ft')))->implode(', ') : count($input['items'] ?? []).' measured work items');
        $message = implode("\n", ['Hello Adore Interior, I would like to discuss my indicative estimate.', 'Reference: '.$estimate->reference, 'Calculator: '.$result['type'], 'Property: '.($result['property'] ?? ucfirst($input['type'])), 'Measurements: '.$details, 'Package: '.($result['package'] ?? 'Measured item pricing'), 'Estimated range: '.self::money($estimate->min_total).' - '.self::money($estimate->max_total), $estimate->is_demo ? 'DEMO pricing only - please provide approved project pricing.' : 'Subject to site assessment and material selection.']);

        return redirect()->away('https://wa.me/919173774898?text='.rawurlencode($message));
    }

    public static function money(int $paisa): string
    {
        return 'INR '.number_format($paisa / 100, 2, '.', ',');
    }

    public function pdf(CalculatorEstimate $estimate): Response
    {
        return Pdf::loadView('calculator.pdf', ['estimate' => $estimate, 'result' => $estimate->breakdown])->setPaper('a4')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'chroot' => public_path(), 'defaultFont' => 'DejaVu Sans'])->download($estimate->reference.'.pdf')->header('Cache-Control', 'private, no-store');
    }
}

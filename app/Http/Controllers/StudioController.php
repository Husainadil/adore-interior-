<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryRequest;
use App\Models\Content;
use App\Models\Inquiry;
use App\Models\ProjectCategory;
use App\Notifications\NewInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class StudioController extends Controller
{
    public function page(string $page = 'home', ?string $slug = null)
    {
        $services = Content::for('services')->where('published', true)->orderBy('sort_order')->get();
        $projects = Content::for('projects')->where('published', true)->with('category')->orderBy('sort_order')->get();
        $record = null;
        if ($slug) {
            $record = Content::for($page)->where('published', true)->where('slug', $slug)->firstOrFail();
        }
        $copy = Content::for('pages')->where('slug', $page)->where('published', true)->first();

        return view('frontend.page', compact('page', 'slug', 'services', 'projects', 'record', 'copy') + ['processSteps' => Content::for('pages')->where('published', true)->where('slug', 'like', 'process-%')->orderBy('sort_order')->pluck('description', 'title'), 'principles' => Content::for('pages')->where('published', true)->where('slug', 'like', 'principle-%')->orderBy('sort_order')->pluck('description', 'title'), 'features' => Content::for('pages')->where('published', true)->where('slug', 'like', 'feature-%')->orderBy('sort_order')->pluck('description', 'title'), 'categories' => ProjectCategory::all(), 'settings' => DB::table('website_settings')->pluck('value', 'key'), 'seo' => DB::table('seo_metadata')->where('path', request()->path())->first(), 'banners' => Content::for('banners')->where('published', true)->orderBy('sort_order')->get(), 'comparisons' => Content::for('before_after_galleries')->where('published', true)->get(), 'testimonials' => Content::for('testimonials')->where('published', true)->where('is_sample', false)->get()]);
    }

    public function inquiry(InquiryRequest $request): RedirectResponse
    {
        $inquiry = DB::transaction(function () use ($request): Inquiry {
            $inquiry = Inquiry::create($request->safe()->except(['website', 'intent']));
            Notification::route('mail', config('studio.inquiry_to'))->notify(new NewInquiry($inquiry));

            return $inquiry;
        });

        if ($request->input('intent') === 'whatsapp') {
            $message = implode("\n", [
                'Hello Adore Interior, I would like to book a consultation.',
                'Inquiry reference: #'.$inquiry->id,
                'Name: '.$inquiry->name,
                'Project: '.$inquiry->project_type,
                'Location: '.$inquiry->location,
                'Budget: '.($inquiry->budget ?: 'To discuss'),
                'About my space: '.$inquiry->message,
            ]);

            return redirect()->away('https://wa.me/919173774898?text='.rawurlencode($message));
        }

        return back()->with('success', 'Thank you. Your inquiry has been received. Our studio will be in touch.');
    }

    public function sitemap()
    {
        $paths = ['', 'about', 'services', 'projects', 'design-process', 'contact', 'privacy', 'terms', 'interior-cost-calculator'];
        foreach (['services', 'projects'] as $table) {
            foreach (Content::for($table)->where('published', true)->pluck('slug') as $slug) {
                $paths[] = "$table/$slug";
            }
        }

        return response()->view('sitemap', compact('paths'))->header('Content-Type', 'application/xml');
    }
}

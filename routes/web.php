<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudioController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

require __DIR__.'/calculator.php';
Route::get('/', [StudioController::class, 'page']);
foreach (['about', 'services', 'projects', 'design-process', 'contact', 'privacy', 'terms'] as $page) {
    Route::get('/'.$page, fn () => app(StudioController::class)->page($page));
}
foreach (['services', 'projects'] as $page) {
    Route::get('/'.$page.'/{slug}', fn (string $slug) => app(StudioController::class)->page($page, $slug));
}
Route::post('/inquiries', [StudioController::class, 'inquiry'])->middleware('throttle:5,10');
Route::get('/sitemap.xml', [StudioController::class, 'sitemap']);
Route::get('/admin/login', fn () => view('admin.login'))->name('login')->middleware('guest');
Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:5,1');
Route::middleware(AdminOnly::class)->prefix('admin')->group(function () {
    Route::post('/logout', [AdminController::class, 'logout']);
    Route::post('/profile', [AdminController::class, 'profile']);
    Route::get('/', [AdminController::class, 'index']);
    Route::get('/{module}/create', [AdminController::class, 'edit']);
    Route::get('/{module}/{id}/edit', [AdminController::class, 'edit'])->whereNumber('id');
    Route::post('/{module}/{id?}', [AdminController::class, 'save'])->whereNumber('id');
    Route::delete('/{module}/{id}', [AdminController::class, 'delete'])->whereNumber('id');
    Route::get('/{module}', [AdminController::class, 'index']);
});
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /admin\nSitemap: ".url('/sitemap.xml')."\n")->header('Content-Type','text/plain'));

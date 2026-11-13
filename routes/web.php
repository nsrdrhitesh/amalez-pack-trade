<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\HomeController;
// Public pages are stateless: no database-backed session is started.
$stateless = [\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class, \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class];
Route::get('/', HomeController::class)->withoutMiddleware($stateless)->name('home');
Route::get('/sitemap.xml', fn () => new \Illuminate\Http\Response(view('frontend.sitemap')->render(), 200, ['Content-Type' => 'application/xml']))
    ->withoutMiddleware($stateless)->name('sitemap');
require __DIR__.'/admin.php';

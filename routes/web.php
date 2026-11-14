<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\SiteController;
use App\Http\Controllers\Frontend\SubmissionController;
use App\Http\Middleware\CmsEnabled;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Response;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Public pages are stateless: no database-backed session is started.
$stateless = [StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class];
Route::get('/', HomeController::class)->withoutMiddleware($stateless)->name('home');
Route::get('/sitemap.xml', fn () => config('cms.enabled') ? app(SiteController::class)->sitemap() : new Response(view('frontend.sitemap')->render(), 200, ['Content-Type' => 'application/xml']))
    ->withoutMiddleware($stateless)->name('sitemap');
require __DIR__.'/admin.php';
require __DIR__.'/cms.php';
Route::get('/robots.txt', fn () => config('cms.enabled') ? app(SiteController::class)->robots() : response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain']))->withoutMiddleware($stateless)->name('robots');
Route::middleware(CmsEnabled::class)->group(function () {
    $site = SiteController::class;
    $submit = SubmissionController::class;
    Route::get('/services', [$site, 'services'])->name('services.index');
    Route::get('/services/{path}', [$site, 'service'])->where('path', '.*')->name('services.show');
    Route::get('/blog', [$site, 'blog'])->name('blog.index');
    Route::get('/blog/{slug}', [$site, 'post'])->name('blog.show');
    Route::post('/blog/{post}/comments', [$submit, 'comment'])->middleware('throttle:5,10,comments-submit:')->name('comments.store');
    Route::post('/contact', [$submit, 'inquiry'])->middleware('throttle:5,10,contact-submit:')->name('contact.store');
    Route::get('/media/{media}', [$site, 'media'])->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class])->name('media.show');
    Route::get('/{slug}', [$site, 'page'])->where('slug', '[a-z0-9-]+')->name('pages.show');
});

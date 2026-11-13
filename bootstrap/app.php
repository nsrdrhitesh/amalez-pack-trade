<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\LocalRegistration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToPriorityList(\Illuminate\Session\Middleware\StartSession::class, \App\Http\Middleware\AdminModuleEnabled::class);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
        $middleware->alias(['admin' => EnsureAdmin::class, 'local.registration' => LocalRegistration::class, 'admin.enabled' => \App\Http\Middleware\AdminModuleEnabled::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['provisioning_token']);
    })
    ->create();

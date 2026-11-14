<?php

use App\Http\Middleware\AdminModuleEnabled;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\LocalRegistration;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->prependToPriorityList(StartSession::class, AdminModuleEnabled::class);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
        $middleware->alias(['admin' => EnsureAdmin::class, 'local.registration' => LocalRegistration::class, 'admin.enabled' => AdminModuleEnabled::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['provisioning_token']);
    })
    ->create();

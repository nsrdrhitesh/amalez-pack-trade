<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class LocalRegistration {
    public function handle(Request $request, Closure $next): Response {
        $local = app()->environment('local') && config('admin.registration_enabled');
        $bootstrap = app()->environment('production') && config('admin.bootstrap_enabled')
            && app(\App\Services\AdminBootstrap::class)->available();
        abort_unless($local || $bootstrap, 404);
        if ($bootstrap) {
            abort_unless($request->isSecure(), 403);
            abort_if($request->query->has('provisioning_token'), 400);
        }
        return $next($request);
    }
}

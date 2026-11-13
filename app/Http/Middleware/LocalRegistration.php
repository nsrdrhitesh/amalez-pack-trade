<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class LocalRegistration {
    public function handle(Request $request, Closure $next): Response {
        abort_unless(app()->environment('local') && config('admin.registration_enabled'), 404);
        return $next($request);
    }
}

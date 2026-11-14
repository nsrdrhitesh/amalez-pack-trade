<?php

namespace App\Http\Middleware;

class CmsEnabled
{
    public function handle($request, \Closure $next)
    {
        abort_unless(config('cms.enabled'), 404);

        return $next($request);
    }
}

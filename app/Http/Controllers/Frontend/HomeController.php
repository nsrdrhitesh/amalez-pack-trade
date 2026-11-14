<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function __invoke()
    {
        if (config('cms.enabled')) {
            return app(SiteController::class)->home();
        }

        return view('frontend.home', ['company' => config('company')]);
    }
}

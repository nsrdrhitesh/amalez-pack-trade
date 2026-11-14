<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\DemoActivityLog;
use App\Models\Inquiry;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;

class DashboardController extends Controller
{
    public function __invoke()
    {
        if (config('cms.enabled')) {
            return view('admin.cms.dashboard', ['metrics' => [
                'Published pages' => Page::published()->count(), 'Active services' => Service::published()->count(),
                'Published articles' => Post::published()->count(), 'Pending comments' => auth()->user()->can('cms-private') ? Comment::where('status', 'pending')->count() : 'Restricted',
                'New inquiries' => auth()->user()->can('cms-private') ? Inquiry::where('status', 'new')->count() : 'Restricted',
            ], 'events' => ActivityLog::with('user')->latest()->limit(8)->get()]);
        }

        return view('admin.dashboard.index', ['activities' => DemoActivityLog::with('user')->latest()->paginate(5)]);
    }

    public function placeholder(string $section)
    {
        if (config('cms.enabled')) {
            return redirect()->route(match ($section) {
                'users' => 'admin.users.index','content' => 'admin.cms.index','analytics' => 'admin.seo.index','settings' => 'admin.website-settings.edit',default => abort(404)
            }, $section === 'content' ? 'pages' : []);
        }
        abort_unless(in_array($section, ['users', 'content', 'analytics', 'settings']), 404);

        return view('admin.dashboard.placeholder', ['section' => $section]);
    }
}

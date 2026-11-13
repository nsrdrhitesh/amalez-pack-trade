<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\DemoActivityLog;
class DashboardController extends Controller {
    public function __invoke() {
        return view('admin.dashboard.index', ['activities' => DemoActivityLog::with('user')->latest()->paginate(5)]);
    }
    public function placeholder(string $section) {
        abort_unless(in_array($section, ['users','content','analytics','settings']), 404);
        return view('admin.dashboard.placeholder', ['section' => $section]);
    }
}

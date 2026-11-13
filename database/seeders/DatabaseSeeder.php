<?php
namespace Database\Seeders;
use App\Models\DemoActivityLog;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        if (! app()->environment('local', 'testing')) { return; }
        foreach ([['Olivia Rhye','Published the getting started guide','Content'], ['Phoenix Baker','Updated workspace preferences','Settings'], ['Lana Steiner','Joined the design workspace','Users'], ['Demi Wilkinson','Exported the monthly overview','Analytics'], ['Drew Cano','Reviewed the new landing page','Content'], ['Andi Lane','Updated their profile','Users'], ['Natali Craig','Published the release notes','Content']] as $index => $item) {
            DemoActivityLog::firstOrCreate(['actor' => $item[0], 'action' => $item[1]], ['category' => $item[2], 'status' => 'Completed', 'created_at' => now()->subMinutes(($index + 1) * 18)]);
        }
    }
}

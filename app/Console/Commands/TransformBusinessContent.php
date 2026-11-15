<?php

namespace App\Console\Commands;

use App\Services\BusinessContentRelease;
use Illuminate\Console\Command;

class TransformBusinessContent extends Command
{
    protected $signature = 'content:transform {--apply : Apply the reviewed content plan} {--expected= : SHA-256 fingerprint from the review} {--enable-indexing : Enable indexing only for reviewed eligible pages}';

    protected $description = 'Preview or apply the versioned five-service editorial release without deleting existing content';

    public function handle(BusinessContentRelease $release): int
    {
        if (! config('cms.enabled')) {
            $this->error('CMS must be enabled to review the content release.');

            return self::FAILURE;
        }
        try {
            $result = $this->option('apply') ? $release->apply((string) $this->option('expected'), (bool) $this->option('enable-indexing')) : $release->plan();
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            // No database exception details or connection values in an operator report.
            report($e);
            $this->error('Content release stopped ('.class_basename($e).'). Check the protected application log.');

            return self::FAILURE;
        }
    }
}

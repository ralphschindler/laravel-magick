<?php

namespace LaravelMagick\Test\Unit;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use LaravelMagick\MagickProvider;

abstract class AbstractTestCase extends TestCase
{
    public function getEnvironmentSetUp($application)
    {
        $application['config']->set('magick.filesystem', 'magick');
        $application['config']->set('filesystems.disks.magick', [
            'driver' => 'local',
            'root' => realpath(__DIR__ . '/../') . '/storage',
        ]);

        Carbon::setTestNow(Carbon::now());
    }

    protected function getPackageProviders($app)
    {
        return [MagickProvider::class];
    }

    public function tearDown(): void
    {
        $disk = Storage::disk('magick');

        foreach ($disk->allDirectories() as $directory) {
            $disk->deleteDirectory($directory);
        }

        parent::tearDown();
    }
}

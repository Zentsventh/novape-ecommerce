<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local') && $this->app->runningInConsole()) {
            \Illuminate\Support\Facades\Event::listen(\Illuminate\Console\Events\CommandStarting::class, function (\Illuminate\Console\Events\CommandStarting $event) {
                if ($event->command === 'serve') {
                    $hotPath = public_path('hot');
                    if (file_exists($hotPath)) {
                        @unlink($hotPath);
                    }
                }
            });
        }
    }
}

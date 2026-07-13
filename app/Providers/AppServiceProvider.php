<?php

namespace App\Providers;

use App\Support\Capture\PageCapturer;
use App\Support\Capture\PlaywrightCapturer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Motore di cattura dietro interfaccia: default Playwright, sostituibile.
        // I test sostituiscono il binding con un FakeCapturer.
        $this->app->bind(PageCapturer::class, function () {
            return new PlaywrightCapturer(
                nodeBinary: config('capture.node_binary'),
                scriptPath: config('capture.script_path'),
                timeoutSecondi: config('capture.timeout'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // I componenti Livewire full-page usano il layout slot-based dedicato.
        // (Il default di Livewire 4 è `layouts::app`, che qui risolve al layout del
        // controller basato su @yield: incompatibile con l'iniezione via slot.)
        config(['livewire.component_layout' => 'components.layouts.app']);
    }
}

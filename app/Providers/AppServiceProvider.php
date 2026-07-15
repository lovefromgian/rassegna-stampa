<?php

namespace App\Providers;

use App\Support\Capture\PageCapturer;
use App\Support\Capture\PlaywrightCapturer;
use App\Support\Discovery\ArticleDiscoverySource;
use App\Support\Discovery\GoogleNewsRss;
use Illuminate\Pagination\Paginator;
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

        // Fonte di scoperta dietro interfaccia: default Google News/RSS, sostituibile.
        // I test sostituiscono il binding con un FakeDiscoverySource.
        $this->app->bind(ArticleDiscoverySource::class, GoogleNewsRss::class);
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

        // Paginazione con vista coerente al CSS del progetto (la default Tailwind
        // rendeva le frecce SVG enormi: l'app non usa Tailwind).
        Paginator::defaultView('vendor.pagination.rassegna');
        Paginator::defaultSimpleView('vendor.pagination.rassegna');
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Collection;
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
        // Team/room names are admin-chosen but conventionally "Team 1".."Team 15" /
        // "Kamer 1".."Kamer 17" — a plain SQL ORDER BY sorts those as strings
        // (Team 1, Team 10, Team 11, ..., Team 2), which reads as broken on a
        // live event dashboard. Natural sort handles both that case and
        // arbitrary admin-chosen names sensibly.
        Collection::macro('sortByNatural', function (string $key) {
            /** @var Collection $this */
            return $this->sortBy($key, SORT_NATURAL | SORT_FLAG_CASE)->values();
        });
    }
}

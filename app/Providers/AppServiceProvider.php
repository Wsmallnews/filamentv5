<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        Model::unguard();

        // 开启 SPA 模式
        \Filament\Support\Facades\FilamentView::spa(true);

        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'navigation' => \App\Models\Navigation::class
        ]);
    }
}

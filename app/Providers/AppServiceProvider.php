<?php

namespace App\Providers;

use App\Models\Navigation;
use App\Models\User;
use Filament\Support\Facades\FilamentView;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Wsmallnews\Cms\CmsPlugin;
use Wsmallnews\Support\Facades\CompositionRegistry as CompositionRegistryFacade;

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
        FilamentView::spa(true);

        Relation::enforceMorphMap([
            'user' => User::class,
            'navigation' => Navigation::class,
        ]);

        // 注册导航内容（key = 模块标识插件 id）
        CompositionRegistryFacade::registers(app(CmsPlugin::class)->getId(), []);
    }
}

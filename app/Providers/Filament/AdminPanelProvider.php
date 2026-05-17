<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wsmallnews\Cms\CmsPlugin;
use Wsmallnews\Cms\Filament\Pages\Navigation;
use Wsmallnews\Cms\Filament\Pages\Category as CategoryPage;
use Wsmallnews\Cms\Filament\Pages\GeneralSetting as GeneralSettingPage;
use Wsmallnews\Cms\Filament\Resources\Posts\PostResource;
use Wsmallnews\Comment\CommentPlugin;
use Wsmallnews\Comment\Filament\Pages\Comment\CommentPage;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->spa()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->plugins([
                CommentPlugin::make()
                    ->forResource(CommentPage::class)
                    ->navigationGroup('网站管理')
                    ->navigationLabel('评论管理'),
                CmsPlugin::make()
                    ->forResource(Navigation::class)
                    ->navigationGroup('网站管理')
                    ->navigationLabel('导航管理')
                    ->customProperties([
                        'emptyLabel' => '呀，怎么没数据呀！',
                        'level' => 3,
                    ])
                    ->forResource(PostResource::class)
                    ->navigationGroup('网站管理')
                    ->navigationLabel('图文管理')
                    ->forResource(CategoryPage::class)
                    ->navigationGroup('网站管理')
                    ->navigationParentItem('图文管理')
                    ->navigationLabel('图文分类')
                    ->forResource(GeneralSettingPage::class)
                    ->navigationGroup('网站管理')
                    ->navigationLabel('网站设置'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

<?php

use Filament\Facades\Filament;
use Wsmallnews\Category\Filament\Pages\Category\CategoryPage as PostCategoryPage;
use Wsmallnews\Cms\Filament\Pages\Navigation\Footer\FooterNavigationPage;
use Wsmallnews\Cms\Filament\Resources\Posts\PostResource;

it('cms 自有资源默认解析 main 实例，module_id 注册时自动注入', function () {
    withAdminPanelContext(function () {
        expect(PostResource::getScopeable())->toBe(['scope_type' => 'sn-cms', 'scope_id' => 0])
            ->and(PostResource::getScopeType())->toBe('sn-cms')
            ->and(PostResource::getScopeId())->toBe(0)
            ->and(PostResource::getModuleId())->toBe('sn-cms');
    });
});

it('FooterNavigationPage 解析 footer 差异实例', function () {
    withAdminPanelContext(function () {
        expect(FooterNavigationPage::getScopeable())->toBe(['scope_type' => 'sn-cms-footer', 'scope_id' => 0]);
    });
});

it('跨包注册的 PostCategoryPage 归属注册模块（module_id 自动注入为 sn-cms）', function () {
    // CategoryPage 自身的插件语境是 sn-category，但注册进 cms 后 module_id 由
    // CmsPlugin 注册时自动注入——跨模块注册无需（也不应）手工声明 module_id；
    // 该条目带 'key' => 'post-category'（多实例页面），configuration key 随之
    $previousPageKey = Filament::getCurrentPageConfigurationKey();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setCurrentPageConfigurationKey('post-category');

    try {
        expect(PostCategoryPage::getModuleId())->toBe('sn-cms')
            ->and(PostCategoryPage::getScopeable())->toBe(['scope_type' => 'sn-cms', 'scope_id' => 0]);
    } finally {
        Filament::setCurrentPageConfigurationKey($previousPageKey);
    }
});

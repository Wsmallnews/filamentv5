<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Livewire\Components\Navigation\Navigation as CmsNavigation;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Support\ProductSpecService;
use Wsmallnews\Shop\Exceptions\ShopException;
use Wsmallnews\Shop\ShopPlugin;
use Wsmallnews\Shop\Support\Utils;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;

uses(RefreshDatabase::class);

it('解析 shop 模块 scopeable main 默认实例', function () {
    expect(Utils::getScopeable())->toBe(['scope_type' => 'sn-shop', 'scope_id' => 0])
        ->and(Utils::getScopeType())->toBe('sn-shop')
        ->and(Utils::getScopeId())->toBe(0);
});

it('scopeable 实例键缺失时抛 ShopException', function () {
    config(['sn-shop.scopeables' => ['other' => ['scope_type' => 'sn-shop-other', 'scope_id' => 0]]]);

    Utils::getScopeable();
})->throws(ShopException::class);

it('基础认证路由与个人中心路由全部注册', function () {
    expect(route('sn-shop.index', absolute: false))->toBe('/shop')
        ->and(route('sn-shop.login', absolute: false))->toBe('/shop/login')
        ->and(route('sn-shop.register', absolute: false))->toBe('/shop/register')
        ->and(route('sn-shop.forgot.password', absolute: false))->toBe('/shop/forgot-password')
        ->and(route('sn-shop.product.detail', ['id' => 1], false))->toBe('/shop/product-detail/1')
        ->and(route('sn-shop.profile', absolute: false))->toBe('/shop/profile')
        ->and(route('sn-shop.profile.views', absolute: false))->toBe('/shop/profile/views')
        ->and(route('sn-shop.settings.password', absolute: false))->toBe('/shop/settings/password')
        ->and(Utils::route('pay.cashier'))->toBe(url('/shop/pay-cashier'));
});

it('shop 插件注册到面板（panel_register 配置驱动）', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->hasPlugin((new ShopPlugin)->getId()))->toBeTrue();
});

it('shop 注册产品资源：后台 scopeable 解析为 sn-shop main（product 包自身不注册）', function () {
    // 产品资源经 shop 的 panel_register 注册，注册即归属 module_id = sn-shop：
    // 后台创建/查询的产品落 sn-shop main scope，与前台组件传入的 scope 一致
    expect(Utils::getPanelRegister('resources'))->toContain(ProductResource::class);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setCurrentPageConfigurationKey('default');
    Filament::setCurrentResourceConfigurationKey('default');

    try {
        expect(ProductResource::getScopeable())->toBe(['scope_type' => 'sn-shop', 'scope_id' => 0]);
    } finally {
        Filament::setCurrentPanel(null);
        Filament::setCurrentPageConfigurationKey(null);
        Filament::setCurrentResourceConfigurationKey(null);
    }
});

it('导航配置独立于 cms（navigation 节默认形态，经 cms 组件 moduleConfig 消费）', function () {
    expect(config('sn-shop.navigation.style'))->toBe('primary')
        ->and(config('sn-shop.navigation.desktop_submenu_style'))->toBe('cascade')
        ->and(Utils::getLayout())->toBe('sn-shop::components.layouts.app')
        ->and(Utils::getPageContainer())->toBe('sn-shop::container.page');
});

it('cms 导航组件支持 module 上下文（shop 复用同一视图，配置走 sn-shop 节）', function () {
    $reflection = new ReflectionClass(CmsNavigation::class);

    expect($reflection->hasProperty('module'))->toBeTrue()
        ->and($reflection->hasMethod('moduleConfig'))->toBeTrue()
        ->and($reflection->hasMethod('getOwnerModule'))->toBeTrue()
        // owner 归属由 ModuleRegistry 反查，组件无需手写
        ->and(ModuleRegistry::moduleOf(CmsNavigation::class))->toBe('sn-cms')
        ->and(config('sn-shop.navigation.style'))->toBe('primary');
});

it('认证用户按 auth_user_type 解析（默认 member）', function () {
    expect(Utils::getConfig('auth_user_type'))->toBe('member')
        ->and(Utils::getUser())->toBeNull();
});

it('产品列表路由注册（shop 定义产品列表与详情路由，product 包自身不设路由）', function () {
    expect(route('sn-shop.products', absolute: false))->toBe('/shop/products')
        ->and(route('sn-shop.product.detail', ['id' => 1], false))->toBe('/shop/product-detail/1');
});

it('商城前台页面渲染产品组件（产品数据落 shop main scope）', function () {
    $product = Product::factory()->create([
        'title' => '测试商城产品',
        'scope_type' => Utils::getScopeType(),
        'scope_id' => Utils::getScopeId(),
    ]);

    ProductSpecService::save($product, [
        'specs' => [],
        'variants' => [],
        'variant' => [
            'price' => '12.50',
            'stock' => 5,
            'product_sn' => 'SN-SHOP-001',
            'weight' => '0.5',
        ],
    ]);

    // 首页 / 产品列表页 / 产品详情页均渲染产品组件
    $this->get('/shop')->assertOk()->assertSee('测试商城产品');
    $this->get('/shop/products')->assertOk()->assertSee('测试商城产品');
    $this->get('/shop/product-detail/' . $product->id)->assertOk()->assertSee('测试商城产品');
});

it('下架产品详情返回 404', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::Down,
        'scope_type' => Utils::getScopeType(),
        'scope_id' => Utils::getScopeId(),
    ]);

    $this->get('/shop/product-detail/' . $product->id)->assertNotFound();
});

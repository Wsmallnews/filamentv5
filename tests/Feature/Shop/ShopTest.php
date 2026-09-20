<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Shop\Exceptions\ShopException;
use Wsmallnews\Shop\Filament\Pages\Settings\WechatPay;
use Wsmallnews\Shop\Settings\WechatPay as WechatPaySetting;
use Wsmallnews\Shop\ShopPlugin;
use Wsmallnews\Shop\Support\Utils;

use function Pest\Livewire\livewire;

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

it('路由名自动拼接模块前缀并注册到路由表', function () {
    expect(route('sn-shop.index', absolute: false))->toBe('/shop/index')
        ->and(route('sn-shop.product.detail', ['id' => 1], false))->toBe('/shop/product-detail/1')
        ->and(Utils::route('pay.cashier'))->toBe(url('/shop/pay-cashier'));
});

it('shop 插件注册设置页面（panel_register 配置驱动）', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->hasPlugin((new ShopPlugin)->getId()))->toBeTrue()
        ->and($panel->getPageConfiguration(WechatPay::class, 'default'))->not->toBeNull();
});

it('微信支付设置使用独立分组与默认值', function () {
    expect(WechatPaySetting::group())->toBe('shop_wechat_pay')
        ->and((new WechatPaySetting)->mode)->toBe(0)
        ->and((new WechatPaySetting)->mch_id)->toBe('');
});

it('微信支付设置页可渲染（Filament v5 Schema 表单）', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    livewire(WechatPay::class)
        ->assertSuccessful()
        ->assertFormFieldExists('mch_id');
});

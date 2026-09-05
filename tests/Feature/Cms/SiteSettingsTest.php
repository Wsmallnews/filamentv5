<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Filament\Pages\GeneralSetting;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Settings\GeneralSettings;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('zh_CN');

    // 容器页头部导航依赖 navigation type，缺失时会 404
    NavigationType::create([
        'name' => 'Cms',
        'level' => 2,
        'status' => NavigationTypeStatus::Normal,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);
});

it('settings 迁移包含新增的站点与 SEO 字段且默认为空字符串', function () {
    $settings = app(GeneralSettings::class);

    foreach ([
        'site_name', 'site_slogan', 'logo', 'favicon', 'homepage_banner', 'default_og_image',
        'seo_description', 'analytics_code',
        'work_time', 'beian_police_no', 'beian_police_url',
    ] as $field) {
        expect($settings->{$field})->toBe('');
    }
});

it('首页未配置 logo 时回退渲染站点名称文字且无背景图', function () {
    $this->get('/cms')
        ->assertOk()
        ->assertSee(config('app.name').'</span>', false)
        ->assertDontSee('image/logo.png')
        ->assertDontSee('image/banner.jpg')
        ->assertDontSee('background-image');
});

it('首页优先使用设置中的 logo 与 banner', function () {
    $settings = app(GeneralSettings::class);
    $settings->site_name = '测试站点';
    $settings->logo = 'sn/cms/settings/logo-20260904.png';
    $settings->homepage_banner = 'sn/cms/settings/banner-20260904.png';
    $settings->save();

    $response = $this->get('/cms');

    $response->assertOk()
        ->assertSee(files_url('sn/cms/settings/logo-20260904.png'))
        ->assertSee(files_url('sn/cms/settings/banner-20260904.png'))
        ->assertSee('alt="测试站点"', false)
        ->assertSee('background-image', false);
});

it('logo 旁显示站名的开关统一控制页头与页脚', function () {
    $settings = app(GeneralSettings::class);
    $settings->site_name = '开关测试站';
    $settings->logo = 'sn/cms/settings/logo-switch.png';
    $settings->save();

    // 默认开：logo + 站名（页头/页脚统一，站名在品牌 span 内）
    $this->get('/cms')
        ->assertOk()
        ->assertSee(files_url('sn/cms/settings/logo-switch.png'))
        ->assertSee('开关测试站</span>', false);

    // 关：仅显示 logo 图，品牌区的站名 span 消失（SEO meta 不受影响）
    $settings->logo_with_site_name = false;
    $settings->save();

    $this->get('/cms')
        ->assertOk()
        ->assertSee(files_url('sn/cms/settings/logo-switch.png'))
        ->assertDontSee('开关测试站</span>', false);
});

it('未上传 logo 时无论开关如何都回退首字标块加站名', function () {
    $settings = app(GeneralSettings::class);
    $settings->site_name = '兜底测试站';
    $settings->logo_with_site_name = false;
    $settings->save();

    // 开关关闭 + 无 logo：仍保证站点身份（首字标块 + 站名）
    $this->get('/cms')
        ->assertOk()
        ->assertSee('>兜</span>', false)
        ->assertSee('兜底测试站</span>', false);
});

it('后台设置页表单渲染新增字段', function () {
    // admin 面板使用 admin guard
    $this->actingAs(User::factory()->create(), 'admin');

    $this->get('/admin/general-setting')
        ->assertOk()
        ->assertSee('站点名称')
        ->assertSee('站点 Logo')
        ->assertSee('Logo 旁显示站名')
        ->assertSee('首页 Banner 图')
        ->assertSee('默认分享图')
        ->assertSee('SEO 设置')
        ->assertSee('公安备案号')
        ->assertSee('工作时间');
});

it('后台设置页可通过表单保存新增字段', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    livewire(GeneralSetting::class)
        ->fillForm([
            'site_name' => '表单保存站点',
            'seo_description' => '表单保存的默认描述',
            'beian_police_no' => '京公网安备11000000000000号',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(GeneralSettings::class);
    expect($settings->site_name)->toBe('表单保存站点')
        ->and($settings->seo_description)->toBe('表单保存的默认描述')
        ->and($settings->beian_police_no)->toBe('京公网安备11000000000000号');
});

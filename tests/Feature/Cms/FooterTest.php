<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Models\Navigation;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Settings\GeneralSettings;
use Wsmallnews\Cms\Support\Utils;

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

    // 底部导航类型按需创建（见 createFooterNav；不在此处预建，保证管理页的自动创建路径可被真实测试）
    $this->footerTypeId = null;
});

/**
 * 创建一条底部导航（url 型，挂到 footer 独立 scope；首次调用时创建 footer 类型）
 */
function createFooterNav(string $name, ?int $parentId = null, array $attributes = []): Navigation
{
    if (test()->footerTypeId === null) {
        // 嵌套集带 scope 的父子关联要求 type_id 一致
        test()->footerTypeId = NavigationType::create([
            'name' => 'Footer',
            'level' => 2,
            'status' => NavigationTypeStatus::Normal,
            'scope_type' => 'sn-cms-footer',
            'scope_id' => 0,
        ])->id;
    }

    return Navigation::create(array_merge([
        'name' => $name,
        'type' => 'url',
        'status' => 'normal',
        'scope_type' => 'sn-cms-footer',
        'scope_id' => 0,
        'type_id' => test()->footerTypeId,
        'parent_id' => $parentId,
        'options' => ['url' => 'https://example.com/'.$name],
    ], $attributes));
}

/*
 * 空树形态
 */

it('底部导航为空时仅显示品牌区与合规条', function () {
    $response = $this->get('/cms');

    $response->assertOk()
        // 品牌区（站名回退 app.name）
        ->assertSee(config('app.name'))
        // 合规条存在
        ->assertSee('border-t-2', false);

    // 无导航区：快捷条 aria-label 不出现
    $response->assertDontSee('页脚快捷导航');
});

it('头部导航树的节点不再进入 footer（footer_show 回退已移除）', function () {
    // 头部 scope 下建一条开了 footer_show 的导航
    Navigation::create([
        'name' => '头部专属导航',
        'type' => 'url',
        'status' => 'normal',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'options' => ['url' => 'https://example.com/header', 'footer_show' => true],
    ]);

    $this->get('/cms')->assertOk()->assertDontSee('头部专属导航');
});

/*
 * 全一级形态
 */

it('全一级导航渲染为品牌右侧的快捷链接', function () {
    createFooterNav('首页');
    createFooterNav('资讯');
    createFooterNav('隐私政策');

    $this->get('/cms')
        ->assertOk()
        ->assertSee('页脚快捷导航')
        ->assertSee('首页')
        ->assertSee('资讯')
        ->assertSee('隐私政策')
        ->assertSee('https://example.com/隐私政策');
});

/*
 * 混合形态（分组 + 快捷条）
 */

it('有子级的一级导航渲染为分组列，无子级的进快捷条', function () {
    $about = createFooterNav('关于我们');
    createFooterNav('公司简介', $about->id);
    createFooterNav('团队介绍', $about->id);

    createFooterNav('首页');
    createFooterNav('资讯');

    $response = $this->get('/cms');

    $response->assertOk()
        // 分组列（页脚导航 aria-label）
        ->assertSee('页脚导航')
        ->assertSee('关于我们')
        ->assertSee('公司简介')
        ->assertSee('团队介绍')
        // 快捷条（页脚快捷导航 aria-label）
        ->assertSee('页脚快捷导航')
        ->assertSee('首页')
        ->assertSee('资讯');
});

it('三级导航不渲染（层级防御，只取两层）', function () {
    $group = createFooterNav('分组');
    $child = createFooterNav('二级导航', $group->id);
    createFooterNav('三级导航', $child->id);

    $this->get('/cms')
        ->assertOk()
        ->assertSee('二级导航')
        ->assertDontSee('三级导航');
});

/*
 * 设置驱动
 */

it('品牌区与合规条使用站点设置', function () {
    $settings = app(GeneralSettings::class);
    $settings->site_name = '设置站点名';
    $settings->site_slogan = '设置口号';
    $settings->work_time = '周一至周五 9:00-18:00';
    $settings->beian_police_no = '粤公网安备 44030000000000号';
    $settings->beian_police_url = 'http://www.beian.gov.cn/';
    $settings->save();

    $this->get('/cms')
        ->assertOk()
        ->assertSee('设置站点名')
        ->assertSee('设置口号')
        ->assertSee('周一至周五 9:00-18:00')
        ->assertSee('粤公网安备 44030000000000号')
        ->assertSee('http://www.beian.gov.cn/');
});

/*
 * 后台管理页面
 */

it('底部导航管理页面可访问且自动创建独立类型（level=2）', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    $this->get('/admin/footer-navigations')
        ->assertOk()
        ->assertSee('底部导航');

    // 自动创建了 footer scope 的导航类型，且不影响头部导航类型
    $footerType = NavigationType::where('scope_type', 'sn-cms-footer')->first();
    expect($footerType)->not->toBeNull()
        ->and($footerType->level)->toBe(2)
        ->and(NavigationType::where('scope_type', 'sn-cms')->count())->toBe(1);
});

it('底部导航 scope 为派生约定：模块 scope_type + -footer', function () {
    expect(Utils::getFooterScopeType())->toBe('sn-cms-footer')
        ->and(Utils::getFooterScopeable())->toBe(['scope_type' => 'sn-cms-footer', 'scope_id' => 0]);

    // 模块 scopeable 变化时，底部导航 scope 自动跟随
    config(['sn-cms.scopeable' => ['scope_type' => 'news', 'scope_id' => 0]]);

    expect(Utils::getFooterScopeType())->toBe('news-footer')
        ->and(Utils::getFooterScopeable())->toBe(['scope_type' => 'news-footer', 'scope_id' => 0]);
});

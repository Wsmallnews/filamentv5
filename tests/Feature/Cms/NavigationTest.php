<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Models\Navigation;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Support\Utils;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('zh_CN');

    // 页头导航类型（无限级）
    $this->typeId = NavigationType::create([
        'name' => 'Cms',
        'level' => 0,
        'status' => NavigationTypeStatus::Normal,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ])->id;
});

/**
 * 创建一条页头导航（url 型；attributes 可覆盖 status/options 等）
 */
function createNav(string $name, ?int $parentId = null, array $attributes = []): Navigation
{
    return Navigation::create(array_merge([
        'name' => $name,
        'type' => 'url',
        'status' => 'normal',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'type_id' => test()->typeId,
        'parent_id' => $parentId,
        'options' => ['url' => 'https://example.com/'.$name],
    ], $attributes));
}

/*

 * first_leaf_url accessor

 */

it('first_leaf_url 三层导航返回第一个叶子 url', function () {
    $parent = createNav('父级');
    $child = createNav('子级', $parent->id);
    createNav('叶子一', $child->id);
    createNav('叶子二', $child->id);

    // 重新从库中取，避免使用 create 返回的空 children 关联
    $root = Navigation::where('name', '父级')->first();

    expect($root->first_leaf_url)->toBe('https://example.com/叶子一')
        ->and($root->children->first()->first_leaf_url)->toBe('https://example.com/叶子一');
});

it('first_leaf_url 第一个子级隐藏时跳过取第二个', function () {
    $parent = createNav('父级');
    createNav('隐藏子级', $parent->id, ['status' => 'hidden']);
    createNav('可用子级', $parent->id);

    $root = Navigation::where('name', '父级')->first();

    expect($root->first_leaf_url)->toBe('https://example.com/可用子级');
});

it('first_leaf_url 整棵子树隐藏且自身无链接时返回 null', function () {
    // 父级为 url 型但未配置链接，子级全部隐藏
    $parent = createNav('父级', null, ['options' => []]);
    createNav('隐藏子级', $parent->id, ['status' => 'hidden']);
    createNav('另一个隐藏子级', $parent->id, ['status' => 'hidden']);

    $root = Navigation::where('name', '父级')->first();

    expect($root->first_leaf_url)->toBeNull();
});

it('first_leaf_url 子树不可用时回退为自身 url', function () {
    $parent = createNav('父级');
    createNav('隐藏子级', $parent->id, ['status' => 'hidden']);

    $root = Navigation::where('name', '父级')->first();

    expect($root->first_leaf_url)->toBe('https://example.com/父级');
});

it('first_leaf_url 叶子节点返回自身 url', function () {
    $leaf = createNav('独立叶子');

    expect($leaf->first_leaf_url)->toBe('https://example.com/独立叶子');
});

/*

 * navigation 配置读取与无效组合降级

 */

it('navigation 配置默认值正确', function () {
    // 直接读包配置文件断言出厂默认值，与本地 config() 运行时覆盖（如预览皮肤）解耦
    // style / more_* 是高频预览开关（用户会来回切换），不参与文件级断言
    $defaults = require __DIR__.'/../../../addons/cms/config/sn-cms.php';

    expect($defaults['navigation']['desktop_submenu_style'])->toBe('cascade')
        ->and($defaults['navigation']['desktop_submenu_trigger'])->toBe('hover')
        ->and($defaults['navigation']['desktop_item_style'])->toBe('flush')
        ->and($defaults['navigation']['parent_clickable'])->toBeTrue();
});

it('parent_clickable 仅 hover 级联生效（无效组合强制无效）', function () {
    // accordion：父项固定纯展开
    config(['sn-cms.navigation.desktop_submenu_style' => 'accordion']);
    expect(Utils::isDesktopParentClickable())->toBeFalse();

    // cascade + click：点击语义被展开占用
    config(['sn-cms.navigation.desktop_submenu_style' => 'cascade', 'sn-cms.navigation.desktop_submenu_trigger' => 'click']);
    expect(Utils::isDesktopParentClickable())->toBeFalse();

    // hover 级联 + parent_clickable=false
    config(['sn-cms.navigation.desktop_submenu_trigger' => 'hover', 'sn-cms.navigation.parent_clickable' => false]);
    expect(Utils::isDesktopParentClickable())->toBeFalse();

    // hover 级联 + parent_clickable=true：可点击
    config(['sn-cms.navigation.parent_clickable' => true]);
    expect(Utils::isDesktopParentClickable())->toBeTrue();
});

/*

 * 前台渲染（tradition 主题）

 */

it('PC 主行渲染溢出折叠组件与更多按钮', function () {
    createNav('首页');
    $parent = createNav('新闻中心');
    createNav('公司新闻', $parent->id);

    $this->get('/cms')
        ->assertOk()
        // Alpine 溢出测量组件 + 更多按钮（纯图标 + 多语言 aria-label）
        ->assertSee('snCmsNav(', false)
        ->assertSee('sn-cms-nav-more', false)
        ->assertSee('aria-label="更多"', false)
        // 导航点击事件（父项 node-click / 叶子 leaf-click）
        ->assertSee('sn-cms-navigation-node-click', false)
        ->assertSee('sn-cms-navigation-leaf-click', false)
        // 服务端全量渲染：折叠项也出现在「更多」下拉结构中
        ->assertSee('首页')
        ->assertSee('新闻中心')
        ->assertSee('公司新闻');
});

/**
 * 取主行第一个导航链接的 href（页面中主行 ul 位于最前）
 */
function firstNavLinkHref(TestResponse $response): ?string
{
    preg_match('/<a[^>]*sn-cms-nav-link[^>]*\shref="([^"]*)"/u', $response->getContent(), $matches);

    return $matches[1] ?? null;
}

it('hover 级联下父项直达第一个叶子', function () {
    config(['sn-cms.navigation.desktop_submenu_style' => 'cascade', 'sn-cms.navigation.desktop_submenu_trigger' => 'hover', 'sn-cms.navigation.parent_clickable' => true]);

    $parent = createNav('新闻中心');
    createNav('公司新闻', $parent->id);

    $response = $this->get('/cms');
    $response->assertOk();

    // 主行唯一父项的 href = 第一个叶子 url（真实链接，中键/新标签可用）
    expect(firstNavLinkHref($response))->toBe('https://example.com/公司新闻');
});

it('cascade click 模式下父项纯展开不渲染叶子链接', function () {
    config(['sn-cms.navigation.desktop_submenu_trigger' => 'click']);

    $parent = createNav('新闻中心');
    createNav('公司新闻', $parent->id);

    $response = $this->get('/cms');
    $response->assertOk();

    // 点击语义被展开/收起占用：主行父项纯展开（叶子自身的链接仍在子菜单中）
    expect(firstNavLinkHref($response))->toBe('javascript:;');
});

it('minimal 风格渲染换肤根类', function () {
    config(['sn-cms.navigation.style' => 'minimal']);

    $this->get('/cms')
        ->assertOk()
        ->assertSee('sn-cms-nav-minimal', false);
});

it('desktop_item_style=rounded 时一级链接渲染胶囊形态类', function () {
    config(['sn-cms.navigation.desktop_item_style' => 'rounded']);
    createNav('首页');

    $this->get('/cms')
        ->assertOk()
        ->assertSee('my-2 rounded-md', false);
});

it('cascade 模式的更多面板不渲染滚动容器（flyout 可自由弹出）', function () {
    config(['sn-cms.navigation.more_submenu_style' => 'cascade']);
    createNav('首页');

    $content = $this->get('/cms')->assertOk()->getContent();

    // overflow 滚动容器会裁切向左/右弹出的 flyout 子菜单，cascade 下禁止
    expect($content)->toContain('class="sn-cms-nav-more-menu"')
        ->and($content)->not->toContain('overflow-y-auto sn-scrollbar');
});

it('accordion 模式的更多面板保留限高滚动', function () {
    config(['sn-cms.navigation.more_submenu_style' => 'accordion']);
    createNav('首页');

    $content = $this->get('/cms')->assertOk()->getContent();

    expect($content)->toContain('sn-cms-nav-more-menu max-h-[60vh] overflow-y-auto sn-scrollbar');
});

it('more_icon_only=false 时更多按钮渲染文字形态与宽内边距', function () {
    config(['sn-cms.navigation.more_icon_only' => false]);
    createNav('首页');

    $content = $this->get('/cms')->assertOk()->getContent();

    expect($content)->toContain('<span>更多</span>')
        ->and($content)->toContain('underline-offset-2 px-4"');
});

it('more_icon_only=true 时更多按钮保持纯图标与紧凑内边距', function () {
    config(['sn-cms.navigation.more_icon_only' => true]);
    createNav('首页');

    $content = $this->get('/cms')->assertOk()->getContent();

    expect($content)->toContain('underline-offset-2 px-1"')
        ->and($content)->not->toContain('<span>更多</span>');
});

it('深层导航激活时整条祖先链默认展开', function () {
    // 三层结构，叶子 url 与请求地址一致 → 激活；一二级两个父级都应默认展开（isExpanded: true）
    $level1 = createNav('一级导航');
    $level2 = createNav('二级导航', $level1->id);
    createNav('三级叶子', $level2->id, ['options' => ['url' => rtrim((string) config('app.url'), '/').'/cms']]);

    $content = $this->get('/cms')->assertOk()->getContent();

    expect(substr_count($content, 'isExpanded: true'))->toBeGreaterThanOrEqual(2);
});

it('cascade 子菜单项含激活链时渲染 is-active 选中态', function () {
    // 二级导航下的叶子 url 与请求地址一致 → 二级项在级联 partial（主行 / 更多 cascade）中带 is-active
    $parent = createNav('新闻中心');
    $child = createNav('公司新闻', $parent->id);
    createNav('激活叶子', $child->id, ['options' => ['url' => rtrim((string) config('app.url'), '/').'/cms']]);

    $content = $this->get('/cms')->assertOk()->getContent();

    expect($content)->toContain('sn-cms-sub-item relative has-sub is-active')
        // 激活叶子自身（无子级）也带选中标记
        ->and($content)->toContain('sn-cms-sub-item relative is-active');
});

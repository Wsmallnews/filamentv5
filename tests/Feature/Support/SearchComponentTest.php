<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Facades\Search;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 断言中文文案，切换应用语言
    app()->setLocale('zh_CN');

    // cms 的 post 条目视图渲染分类标签，测试库需补建 category 包的表（迁移未发布到应用目录）
    foreach (glob(base_path('addons/category/database/migrations/*.php.stub')) ?: [] as $migrationFile) {
        (require $migrationFile)->up();
    }
});

function createComponentPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'publisher_type' => 'user',
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => '默认标题',
        'slug' => 'post-'.Str::random(8),
        'status' => 'published',
    ], $attributes));
}

it('搜索组件渲染输入框与默认占位符', function () {
    livewire('sn-support::components.search')
        ->assertSee('搜索…')
        ->assertSee('type="search"', escape: false);
});

it('输入关键词后渲染分组结果与链接', function () {
    Search::registers('sn-cms', [
        [
            'key' => 'post',
            'model' => Post::class,
            'group' => '图文',
            'query' => fn ($query) => $query->published(),
            'url' => fn ($record) => '/posts/'.$record->slug,
        ],
    ]);
    $post = createComponentPost(['title' => '组件搜索文章', 'slug' => 'component-search']);

    livewire('sn-support::components.search')
        ->set('query', '组件搜索')
        ->assertSee('图文')
        ->assertSeeHtml('<mark class="sn-text-highlight">组件搜索</mark>文章')
        ->assertSee('href="/posts/component-search"', escape: false);
});

it('无 url 的来源渲染为无链接项（不含 panel 地址）', function () {
    Search::registers('sn-cms', [
        [
            'key' => 'post',
            'model' => Post::class,
            'group' => '图文',
            'url' => null,
        ],
    ]);
    createComponentPost(['title' => '无链接搜索结果']);

    livewire('sn-support::components.search')
        ->set('query', '无链接搜索')
        ->assertSeeHtml('<mark class="sn-text-highlight">无链接搜索</mark>结果')
        ->assertDontSeeHtml('href=');
});

it('命中高亮包裹 mark 标签', function () {
    Search::registers('sn-cms', [
        [
            'key' => 'post',
            'model' => Post::class,
            'group' => '图文',
            'url' => null,
        ],
    ]);
    createComponentPost(['title' => '高亮测试文章']);

    livewire('sn-support::components.search')
        ->set('query', '高亮测试')
        ->assertSeeHtml('<mark class="sn-text-highlight">高亮测试</mark>文章');
});

it('无结果时渲染空态', function () {
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文'],
    ]);

    livewire('sn-support::components.search')
        ->set('query', '不存在的关键词内容')
        ->assertSee('没有找到相关内容');
});

it('module 绑定模块后只返回该模块的结果', function () {
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文', 'url' => null],
    ]);
    Search::registers('sn-other', [
        ['key' => 'other', 'model' => Post::class, 'group' => '其他', 'url' => null],
    ]);
    createComponentPost(['title' => '模块绑定文章']);

    // 显式 dropdown：应用已发布配置 sn-cms.search.display = page 会经模块声明生效
    livewire('sn-support::components.search', ['module' => 'sn-cms', 'display' => 'dropdown'])
        ->set('query', '模块绑定')
        ->assertSee('图文')
        ->assertDontSee('其他');
});

it('默认 display 配置渲染实时下拉搜索', function () {
    livewire('sn-support::components.search')
        ->assertSeeHtml('wire:model.live.debounce.300ms="query"');
});

it('display 配置为 page 时回车跳转注册的结果页地址并携带 q 参数', function () {
    // 闭包声明：接收搜索关键词，自行拼接完整 URL
    Search::config('sn-cms', ['page' => fn (?string $query) => '/cms/search?q='.urlencode((string) $query)]);

    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms'])
        ->set('query', '组件搜索')
        ->call('gotoSearchPage')
        ->assertRedirect('/cms/search?q='.urlencode('组件搜索'));
});

it('page 模式支持字符串声明与全局兜底解析', function () {
    // 字符串声明：getPage 原样返回，resolvePage 统一拼接 ?q=关键词
    Search::config('sn-cms', ['page' => '/cms/search']);
    expect(Search::getConfig('sn-cms', 'page'))->toBe('/cms/search')
        ->and(Search::resolvePage('sn-cms'))->toBe('/cms/search')
        ->and(Search::resolvePage('sn-cms', '关键词'))->toBe('/cms/search?q='.urlencode('关键词'));

    // 覆盖为 null 清除模块声明：getPage 不再返回值，resolvePage 恢复全局兜底
    config(['sn-support.search.page' => '/global-search']);
    Search::config('sn-cms', ['page' => null]);
    expect(Search::getConfig('sn-cms', 'page'))->toBeNull()
        ->and(Search::resolvePage('sn-cms'))->toBe('/global-search');

    // 无声明且无兜底时为 null
    config(['sn-support.search.page' => null]);
    expect(Search::resolvePage('sn-other'))->toBeNull();
});

it('config 增量合并声明同模块的多个选项', function () {
    // 用独立模块名，避免 cms 服务提供者预置的 sn-cms 声明（terms_operator 等）干扰全量断言
    Search::config('sn-demo', ['engine' => 'database']);
    Search::config('sn-demo', ['page' => '/cms/search']);

    expect(Search::getConfig('sn-demo'))->toBe(['engine' => 'database', 'page' => '/cms/search'])
        ->and(Search::getConfig('sn-demo', 'engine'))->toBe('database')
        ->and(Search::getConfig('sn-demo', 'page'))->toBe('/cms/search');

    Search::forget('sn-demo');
    expect(Search::getConfig('sn-demo'))->toBe([]);
});

it('display 为 page 时输入框不实时搜索且无下拉浮层', function () {
    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms'])
        ->set('query', '组件搜索')
        ->assertDontSeeHtml('wire:model.live')
        ->assertDontSeeHtml('z-40');
});

it('page 模式以 wrapper suffix 常驻渲染 Enter 提示，dropdown 模式不渲染', function () {
    // 应用已发布配置为 sn-cms 声明了 show_search_button（会替代 Enter 提示），先清除以验证默认
    Search::config('sn-cms', ['show_search_button' => null]);

    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms'])
        ->assertSeeHtml('fi-input-wrp-suffix')
        ->assertSee('↵ Enter');

    livewire('sn-support::components.search')
        ->assertDontSeeHtml('fi-input-wrp-suffix')
        ->assertDontSee('↵ Enter');
});

it('show_search_button 开启时 page 模式渲染一体化搜索按钮并替代 Enter 提示', function () {
    config(['sn-support.search.show_search_button' => true]);

    // 自定义按钮 HTML 经 wrapper 的 suffix 渲染：sn-search-submit 样式类 + wire:click；
    // 类序断言证明 suffix 非 inline（fi-inline 不在其间）—— 与输入框之间保留竖向分割线
    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms'])
        ->assertSeeHtml('sn-search-submit')
        ->assertSeeHtml('wire:click="gotoSearchPage"')
        ->assertSeeHtml('fi-input-wrp-suffix fi-input-wrp-suffix-has-label')
        ->assertSee('搜索')
        ->assertDontSee('↵ Enter');

    // 按钮与回车等价：点击同样跳转结果页
    Search::config('sn-cms', ['page' => '/cms/search']);

    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms'])
        ->set('query', '组件搜索')
        ->call('gotoSearchPage')
        ->assertRedirect('/cms/search?q='.urlencode('组件搜索'));
});

it('show_search_button 仅在 page 模式生效，dropdown 模式不渲染', function () {
    config(['sn-support.search.show_search_button' => true]);

    // 组件属性优先于模块/全局（模块 display = page 不影响显式 dropdown）
    livewire('sn-support::components.search', ['module' => 'sn-cms', 'display' => 'dropdown'])
        ->assertDontSeeHtml('wire:click="gotoSearchPage"');
});

it('show_search_button 支持模块声明覆盖全局', function () {
    config(['sn-support.search.show_search_button' => false]);
    Search::config('sn-cms', ['show_search_button' => true]);

    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms'])
        ->assertSeeHtml('wire:click="gotoSearchPage"');
});

it('showButton 组件属性优先于配置', function () {
    config(['sn-support.search.show_search_button' => true]);

    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms', 'showButton' => false])
        ->assertDontSeeHtml('wire:click="gotoSearchPage"')
        ->assertSee('↵ Enter');

    Search::config('sn-cms', ['show_search_button' => false]);

    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms', 'showButton' => true])
        ->assertSeeHtml('wire:click="gotoSearchPage"');
});

it('display 与 debounce 支持模块声明覆盖（经 Search::config 通道）', function () {
    Search::config('sn-demo', ['display' => 'page', 'debounce' => '500ms']);

    // 模块声明 display = page：不实时搜索，渲染 Enter 提示
    livewire('sn-support::components.search', ['module' => 'sn-demo'])
        ->assertDontSeeHtml('wire:model.live')
        ->assertSee('↵ Enter');

    // 模块声明 debounce 覆盖全局（dropdown 模式渲染到 wire:model.live）
    Search::config('sn-demo', ['display' => 'dropdown']);

    livewire('sn-support::components.search', ['module' => 'sn-demo'])
        ->assertSeeHtml('wire:model.live.debounce.500ms="query"');

    Search::forget('sn-demo');
});

it('cms 注册时整节透传 sn-cms.search：剔除 enabled、page 由包闭包兜底', function () {
    // 应用已发布配置的 search 节整节进入注册表（display = page）
    expect(Search::getConfig('sn-cms', 'display'))->toBe(config('sn-cms.search.display'))
        // enabled 是 cms 启用门控，不透传
        ->and(Search::getConfig('sn-cms', 'enabled'))->toBeNull()
        // page 未声明时由包内结果页路由闭包兜底
        ->and(Search::getConfig('sn-cms', 'page'))->toBeCallable();
});

it('page 模式关键词为空时不跳转', function () {
    Search::config('sn-cms', ['page' => '/cms/search']);

    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms'])
        ->set('query', '   ')
        ->call('gotoSearchPage')
        ->assertNoRedirect();
});

it('page 模式未声明结果页地址时不跳转', function () {
    // Registry 是单例，移除其他用例声明的模块结果页，再断言无跳转
    config(['sn-support.search.page' => null]);
    Search::config('sn-cms', ['page' => null]);

    livewire('sn-support::components.search', ['display' => 'page', 'module' => 'sn-cms'])
        ->set('query', '组件搜索')
        ->call('gotoSearchPage')
        ->assertNoRedirect();
});

it('来源声明的自定义条目视图与渲染闭包用于条目渲染', function () {
    // render 闭包优先于视图
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文', 'render' => fn ($result, $query) => '<em data-testid="render-closure">闭包渲染：'.$query.'</em>'],
    ]);
    createComponentPost(['title' => '自定义渲染文章']);

    livewire('sn-support::components.search')
        ->set('query', '自定义渲染')
        ->assertSeeHtml('<em data-testid="render-closure">闭包渲染：自定义渲染</em>');

    // view 视图声明（cms 的 post 条目视图：标题高亮 + 发布日期）
    Search::forget('sn-cms');
    $post = createComponentPost(['title' => '自定义视图文章']);
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文', 'view' => 'sn-cms::components.search.post-item'],
    ]);

    livewire('sn-support::components.search')
        ->set('query', '自定义视图')
        ->assertSeeHtml('<mark class="sn-text-highlight">自定义视图</mark>文章')
        ->assertSee($post->updated_at->format('Y-m-d'));
});

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Facades\Search;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('zh_CN');
});

function createResultsPost(array $attributes = []): Post
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

it('结果页组件空关键词时渲染初始提示', function () {
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文', 'url' => null],
    ]);

    livewire('sn-support::components.search-results')
        ->assertSee('输入关键词开始搜索')
        ->assertDontSee('没有找到相关内容');
});

it('结果页组件 placeholder 支持调用方设置并有默认值', function () {
    // 调用方自定义
    livewire('sn-support::components.search-results', ['placeholder' => '搜索文章'])
        ->assertSeeHtml('placeholder="搜索文章"');

    // 未传时回退默认翻译
    livewire('sn-support::components.search-results')
        ->assertSeeHtml('placeholder="搜索…"');
});

it('结果页组件渲染分组结果与命中高亮', function () {
    Search::registers('sn-cms', [
        [
            'key' => 'post',
            'model' => Post::class,
            'group' => '图文',
            'query' => fn ($query) => $query->published(),
            'url' => fn ($record) => '/posts/'.$record->slug,
        ],
    ]);
    createResultsPost(['title' => '结果页搜索文章', 'slug' => 'results-page-post']);

    livewire('sn-support::components.search-results')
        ->set('query', '结果页')
        ->assertSee('图文')
        ->assertSeeHtml('<mark class="sn-text-highlight">结果页</mark>搜索文章')
        ->assertSee('href="/posts/results-page-post"', escape: false);
});

it('结果页组件无结果时渲染空态', function () {
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文', 'url' => null],
    ]);

    livewire('sn-support::components.search-results')
        ->set('query', '不存在的关键词内容')
        ->assertSee('没有找到相关内容');
});

it('结果页组件 show_search_button 开启时渲染一体化搜索按钮', function () {
    // 应用已发布配置为 sn-cms 声明了 show_search_button，先清除模块声明以验证全局兜底
    Search::config('sn-cms', ['show_search_button' => null]);
    config(['sn-support.search.show_search_button' => true]);

    // 自定义按钮 HTML 经 wrapper 的 suffix 渲染，点击调用 search() 显式触发；
    // 类序断言证明 suffix 非 inline（fi-inline 不在其间）—— 与输入框之间保留竖向分割线；
    // 按钮模式下输入框为 deferred 绑定（不渲染 wire:model.live），搜索必须由按钮/回车触发
    livewire('sn-support::components.search-results', ['module' => 'sn-cms'])
        ->assertSeeHtml('sn-search-submit')
        ->assertSeeHtml('wire:click="search"')
        ->assertSeeHtml('fi-input-wrp-suffix fi-input-wrp-suffix-has-label')
        ->assertSeeHtml('wire:model="query"')
        ->assertSeeHtml('wire:keydown.enter="search"')
        ->assertDontSeeHtml('wire:model.live')
        ->assertSee('搜索');

    // 默认关闭时不渲染，输入框回到 live 防抖自动搜索
    config(['sn-support.search.show_search_button' => false]);

    livewire('sn-support::components.search-results', ['module' => 'sn-cms'])
        ->assertDontSeeHtml('sn-search-submit')
        ->assertSeeHtml('wire:model.live.debounce.300ms="query"');
});

it('结果页组件 search 动作提交 deferred 关键词并渲染结果', function () {
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文', 'url' => null],
    ]);
    createResultsPost(['title' => '按钮搜索文章', 'slug' => 'btn-search-post']);

    // set() 模拟 deferred 输入的待定值，search() 调用随请求一并提交并触发搜索
    livewire('sn-support::components.search-results')
        ->set('query', '按钮搜索')
        ->call('search')
        ->assertSeeHtml('<mark class="sn-text-highlight">按钮搜索</mark>文章');
});

it('结果页组件 showButton 属性与模块声明控制按钮渲染', function () {
    // 模块声明覆盖全局关闭
    config(['sn-support.search.show_search_button' => false]);
    Search::config('sn-cms', ['show_search_button' => true]);

    livewire('sn-support::components.search-results', ['module' => 'sn-cms'])
        ->assertSeeHtml('sn-search-submit');

    // 组件属性优先于模块声明
    livewire('sn-support::components.search-results', ['module' => 'sn-cms', 'showButton' => false])
        ->assertDontSeeHtml('sn-search-submit');

    // 模块声明恢复 null = 不覆盖，回落全局关闭
    Search::config('sn-cms', ['show_search_button' => null]);

    livewire('sn-support::components.search-results', ['module' => 'sn-cms'])
        ->assertDontSeeHtml('sn-search-submit');
});

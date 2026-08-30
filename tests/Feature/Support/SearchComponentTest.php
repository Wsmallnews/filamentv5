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
    livewire('sn-support-components-search')
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

    livewire('sn-support-components-search')
        ->set('query', '组件搜索')
        ->assertSee('图文')
        ->assertSeeHtml('<mark class="sn-search-highlight">组件搜索</mark>文章')
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

    livewire('sn-support-components-search')
        ->set('query', '无链接搜索')
        ->assertSeeHtml('<mark class="sn-search-highlight">无链接搜索</mark>结果')
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

    livewire('sn-support-components-search')
        ->set('query', '高亮测试')
        ->assertSeeHtml('<mark class="sn-search-highlight">高亮测试</mark>文章');
});

it('无结果时渲染空态', function () {
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文'],
    ]);

    livewire('sn-support-components-search')
        ->set('query', '不存在的关键词内容')
        ->assertSee('没有找到相关内容');
});

it('search-key 绑定模块后只返回该模块的结果', function () {
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文', 'url' => null],
    ]);
    Search::registers('sn-other', [
        ['key' => 'other', 'model' => Post::class, 'group' => '其他', 'url' => null],
    ]);
    createComponentPost(['title' => '模块绑定文章']);

    livewire('sn-support-components-search', ['searchKey' => 'sn-cms'])
        ->set('query', '模块绑定')
        ->assertSee('图文')
        ->assertDontSee('其他');
});

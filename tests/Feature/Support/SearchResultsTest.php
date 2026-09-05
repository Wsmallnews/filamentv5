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

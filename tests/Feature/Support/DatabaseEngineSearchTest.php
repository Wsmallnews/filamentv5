<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Facades\Search;
use Wsmallnews\Support\Features\Search\SearchSource;

uses(RefreshDatabase::class);

function createSearchPost(array $attributes = []): Post
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

function registerPostSource(array $overrides = []): void
{
    Search::registers('sn-cms', [
        array_merge([
            'key' => 'post',
            'model' => Post::class,
            'group' => '图文',
            'query' => fn ($query) => $query->published(),
            'scopeable' => ['scope_type' => 'sn-cms', 'scope_id' => 0],
            'url' => fn ($record) => '/posts/'.$record->slug,
        ], $overrides),
    ]);
}

it('LIKE 命中标题与描述', function () {
    registerPostSource();
    createSearchPost(['title' => 'Laravel 入门教程', 'slug' => 'laravel-intro']);
    createSearchPost(['title' => '其他文章', 'description' => '聊聊 Laravel 队列']);

    $results = Search::search('sn-cms', 'laravel')->flatten();

    expect($results)->toHaveCount(2)
        ->and($results->every(fn ($result) => $result->url !== null))->toBeTrue();
});

it('拆词为 AND 语义、字段间 OR 语义', function () {
    registerPostSource();
    createSearchPost(['title' => 'Laravel 入门教程', 'description' => '从零开始']);
    createSearchPost(['title' => 'Laravel 进阶']);
    createSearchPost(['title' => '完全无关的内容']);

    // 同时命中两个词
    $results = Search::search('sn-cms', 'Laravel 入门')->flatten();
    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Laravel 入门教程');

    // 单词命中任一字段
    expect(Search::search('sn-cms', '进阶')->flatten())->toHaveCount(1)
        ->and(Search::search('sn-cms', '从零开始')->flatten())->toHaveCount(1);
});

it('query 闭包过滤草稿状态', function () {
    registerPostSource();
    createSearchPost(['title' => '已发布文章', 'status' => 'published']);
    createSearchPost(['title' => '草稿文章', 'status' => 'draft']);

    $results = Search::search('sn-cms', '文章')->flatten();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('已发布文章');
});

it('scopeable 过滤其他作用域的记录', function () {
    registerPostSource();
    createSearchPost(['title' => '当前作用域文章']);
    createSearchPost(['title' => '其他作用域文章', 'scope_type' => 'other']);

    $results = Search::search('sn-cms', '作用域')->flatten();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('当前作用域文章');
});

it('limit 限制每个来源的返回条数', function () {
    registerPostSource();
    foreach (['第一篇', '第二篇', '第三篇'] as $title) {
        createSearchPost(['title' => $title.'限流']);
    }

    expect(Search::search('sn-cms', '限流')->flatten())->toHaveCount(3)
        ->and(Search::search('sn-cms', '限流', limit: 1)->flatten())->toHaveCount(1);
});

it('相同 key 重复注册时覆盖原来源', function () {
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '包内分组', 'url' => null],
    ]);
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '覆盖分组', 'url' => null],
    ]);

    expect(Search::getSources('sn-cms'))->toHaveCount(1)
        ->and(Search::getSources('sn-cms')->first()->group())->toBe('覆盖分组');
});

it('无 url 来源渲染为无链接结果', function () {
    registerPostSource(['url' => null]);
    createSearchPost(['title' => '没有链接的文章']);

    $results = Search::search('sn-cms', '没有链接')->flatten();

    expect($results)->toHaveCount(1)
        ->and($results->first()->url)->toBeNull();
});

it('默认字段剔除含 . 的关联字段', function () {
    // 匿名模型携带含关联路径的搜索字段，验证默认解析剔除 '.'
    $model = get_class(new class extends Model
    {
        public static array $keywordSearchFields = ['name', 'publisher.name', 'category.title'];
    });

    $source = new SearchSource(['model' => $model]);

    expect($source->fields())->toBe(['name']);
});

it('空关键词不触发查询', function () {
    registerPostSource();

    expect(Search::search(null, '  '))->toBeEmpty();
});

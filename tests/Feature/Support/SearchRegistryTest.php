<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Facades\Search;
use Wsmallnews\Support\Features\Search\Engines\Engine;
use Wsmallnews\Support\Features\Search\SearchResult;
use Wsmallnews\Support\Features\Search\SearchSource;

uses(RefreshDatabase::class);

/**
 * 永远返回空结果的夹具引擎：用于验证模块级引擎覆盖全局配置。
 */
class SupportEmptySearchEngine implements Engine
{
    public function search(SearchSource $source, string $query): Collection
    {
        return collect();
    }
}

function registerFakeSource(string $search, string $key, array $overrides = []): void
{
    Search::registers($search, [
        array_merge([
            'key' => $key,
            'model' => User::class,
            'results' => fn () => collect([new SearchResult($key, '分组', "{$key}-结果")]),
        ], $overrides),
    ]);
}

function createRegistryPost(string $title, string $slug): Post
{
    return Post::create([
        'publisher_type' => 'user',
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => $title,
        'slug' => $slug,
        'status' => 'published',
    ]);
}

it('多搜索实例按搜索名隔离注册', function () {
    registerFakeSource('sn-cms', 'post');
    registerFakeSource('sn-product', 'product');

    expect(Search::getSources('sn-cms'))->toHaveCount(1)
        ->and(Search::getSources('sn-cms')->first()->key())->toBe('post')
        ->and(Search::getSources('sn-product'))->toHaveCount(1)
        ->and(Search::getSources('sn-product')->first()->key())->toBe('product');
});

it('union 搜索合并所有来源，指定搜索名只取该搜索', function () {
    registerFakeSource('sn-cms', 'post');
    registerFakeSource('sn-product', 'product');

    $union = Search::search('任意', limit: 5);

    expect($union->flatten())->toHaveCount(2)
        ->and(Search::search('任意', 'sn-cms', 5)->flatten())->toHaveCount(1)
        ->and(Search::search('任意', 'sn-cms', 5)->flatten()->first()->key)->toBe('post');
});

it('自定义 results 闭包绕过引擎并按 sort 分组', function () {
    registerFakeSource('demo', 'low', ['sort' => 2, 'group' => 'B组']);
    registerFakeSource('demo', 'high', ['sort' => 1, 'group' => 'A组']);

    $groups = Search::search('任意');

    expect($groups->keys()->toArray())->toBe(['A组', 'B组'])
        ->and($groups->first()->first()->title)->toBe('high-结果');
});

it('visible 为 false 的来源跳过搜索', function () {
    registerFakeSource('demo', 'hidden', ['visible' => fn () => false]);
    registerFakeSource('demo', 'shown');

    expect(Search::search('任意')->flatten())->toHaveCount(1)
        ->and(Search::search('任意')->flatten()->first()->key)->toBe('shown');
});

it('forget 注销搜索', function () {
    registerFakeSource('demo', 'post');
    Search::forget('demo');

    expect(Search::getSources('demo'))->toBeEmpty();
});

it('搜索未注册的搜索名抛异常', function () {
    Search::search('任意', 'not-exists');
})->throws(SupportException::class);

it('缺少 model 选项时注册抛异常', function () {
    Search::registers('bad', [['key' => 'no-model']]);
})->throws(SupportException::class);

it('空关键词返回空集合', function () {
    registerFakeSource('demo', 'post');

    expect(Search::search(''))->toBeEmpty()
        ->and(Search::search('   '))->toBeEmpty();
});

it('模块级引擎优先于全局配置', function () {
    Search::engine('sn-cms', SupportEmptySearchEngine::class)->registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文'],
    ]);

    createRegistryPost('引擎测试文章', 'engine-test');

    // 数据库中有可命中数据，但模块声明了空结果引擎 → 无结果（模块引擎生效）
    expect(Search::search('引擎测试', 'sn-cms'))->toBeEmpty();

    // 未声明引擎的模块走全局兜底（database）→ 正常命中
    Search::registers('other', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文'],
    ]);
    expect(Search::search('引擎测试', 'other')->flatten())->toHaveCount(1);
});

it('引擎与注册顺序无关，后设置也可、再次调用即覆盖', function () {
    // 先注册来源，后设置引擎
    Search::registers('sn-cms', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文'],
    ]);
    Search::engine('sn-cms', SupportEmptySearchEngine::class);

    createRegistryPost('顺序无关文章', 'order-independent');

    expect(Search::getEngine('sn-cms'))->toBe(SupportEmptySearchEngine::class)
        ->and(Search::search('顺序无关', 'sn-cms'))->toBeEmpty();

    // 再次调用 engine() 显式覆盖为 database
    Search::engine('sn-cms', 'database');
    expect(Search::getEngine('sn-cms'))->toBe('database')
        ->and(Search::search('顺序无关', 'sn-cms')->flatten())->toHaveCount(1);
});

it('engine 传 null 移除模块声明恢复全局兜底', function () {
    Search::engine('demo', SupportEmptySearchEngine::class)->registers('demo', [
        ['key' => 'post', 'model' => Post::class, 'group' => '图文'],
    ]);
    expect(Search::getEngine('demo'))->toBe(SupportEmptySearchEngine::class);

    Search::engine('demo', null);
    expect(Search::getEngine('demo'))->toBeNull();
});

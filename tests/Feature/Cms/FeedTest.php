<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Facades\Feed;

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

    // feed 渲染结果按流整份缓存，测试间需清空避免读到上一用例的陈旧内容
    Feed::flush();
});

/**
 * 创建一篇已发布文章（挂到默认 scope）
 */
function createFeedPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'publisher_type' => 'user',
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => '默认标题',
        'slug' => 'feed-post-'.Str::random(8),
        'published_at' => now(),
        'status' => 'published',
    ], $attributes));
}

/*
 * 聚合端点 /feed
 */

it('聚合端点输出 RSS 2.0 与已发布文章', function () {
    createFeedPost(['title' => 'RSS 文章标题', 'slug' => 'rss-feed-post', 'description' => 'RSS 文章描述']);

    $response = $this->get('/feed');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

    $xml = $response->getContent();

    expect($xml)
        ->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<rss version="2.0">')
        ->toContain('<item>')
        ->toContain('<title>RSS 文章标题</title>')
        ->toContain(url('/cms/posts/rss-feed-post'))
        ->toContain('<description>RSS 文章描述</description>')
        ->toContain('<pubDate>');
});

it('草稿文章与其他 scope 不进 feed', function () {
    $publisher = ['publisher_type' => 'user', 'publisher_id' => User::factory()->create()->id];

    Post::create($publisher + [
        'scope_type' => 'sn-cms', 'scope_id' => 0,
        'title' => '草稿文章', 'slug' => 'feed-draft', 'status' => 'draft',
    ]);
    Post::create($publisher + [
        'scope_type' => 'sn-cms', 'scope_id' => 5,
        'title' => '其他范围文章', 'slug' => 'feed-other-scope', 'status' => 'published',
    ]);

    $xml = $this->get('/feed')->getContent();

    expect($xml)
        ->not->toContain('草稿文章')
        ->not->toContain('其他范围文章');
});

it('聚合流合并多模块流并按时间倒序', function () {
    createFeedPost(['title' => '较旧文章', 'slug' => 'feed-older', 'published_at' => now()->subHour()]);

    Feed::register('sn-shop-test', 'products', [
        'title' => '商品流',
        'items' => fn (): array => [
            ['title' => '较新商品', 'url' => url('/shop/p-1'), 'updated_at' => now()],
        ],
    ]);

    Feed::flush();

    $xml = $this->get('/feed')->getContent();

    // 两个模块的流都进聚合输出，且时间新的在前
    expect($xml)
        ->toContain('较新商品')
        ->toContain('较旧文章')
        ->and(strpos($xml, '较新商品'))->toBeLessThan(strpos($xml, '较旧文章'));
});

it('sn-cms.feed.limit 限制本流条数', function () {
    $publisher = ['publisher_type' => 'user', 'publisher_id' => User::factory()->create()->id];

    foreach (range(1, 5) as $i) {
        Post::create($publisher + [
            'scope_type' => 'sn-cms', 'scope_id' => 0,
            'title' => "限量文章 {$i}", 'slug' => "feed-limit-{$i}",
            'published_at' => now()->subMinutes(10 - $i), 'status' => 'published',
        ]);
    }

    config(['sn-cms.feed.limit' => 2]);

    $xml = $this->get('/feed/posts')->getContent();

    // published_at 最新两条（限量文章 5、4）在，其余不在
    expect(substr_count($xml, '<item>'))->toBe(2)
        ->and($xml)->toContain('限量文章 5')
        ->and($xml)->toContain('限量文章 4')
        ->not->toContain('限量文章 3');
});

it('sn-support.feeds.limit 限制聚合流总量', function () {
    $publisher = ['publisher_type' => 'user', 'publisher_id' => User::factory()->create()->id];

    foreach (range(1, 5) as $i) {
        Post::create($publisher + [
            'scope_type' => 'sn-cms', 'scope_id' => 0,
            'title' => "聚合限量文章 {$i}", 'slug' => "feed-aggregate-limit-{$i}",
            'published_at' => now()->subMinutes(10 - $i), 'status' => 'published',
        ]);
    }

    config(['sn-support.feeds.limit' => 3]);

    $xml = $this->get('/feed')->getContent();

    expect(substr_count($xml, '<item>'))->toBe(3)
        ->and($xml)->toContain('聚合限量文章 5')
        ->not->toContain('聚合限量文章 2');
});

/*
 * 具名端点 /feed/{name}
 */

it('具名端点输出单模块流', function () {
    createFeedPost(['title' => '具名流文章', 'slug' => 'named-feed-post']);

    $response = $this->get('/feed/posts');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

    $xml = $response->getContent();

    expect($xml)
        ->toContain('具名流文章')
        // 频道标题 = 站点名 - 文章动态
        ->toContain('文章动态');
});

it('未知流名返回 404', function () {
    $this->get('/feed/nonexistent')->assertNotFound();
});

it('域名不匹配的模块流 404 且不进聚合', function () {
    Feed::config('sn-shop-domained', ['domain' => 'shop.smallnews.top'])
        ->register('sn-shop-domained', 'domained-products', [
            'items' => fn (): array => [
                ['title' => '外域商品', 'url' => url('/shop/domained-p-1')],
            ],
        ]);

    Feed::flush();

    $this->get('/feed/domained-products')->assertNotFound();
    expect($this->get('/feed')->getContent())->not->toContain('/shop/domained-p-1');
});

/*
 * 模块端点 /cms/feed（路径前缀部署下的内容隔离）
 */

it('模块聚合端点仅输出本模块流与模块频道元数据', function () {
    createFeedPost(['title' => '模块聚合文章', 'slug' => 'module-feed-post']);

    // 其他模块的流（路径前缀模式下共用域名，域名过滤无法隔离，模块端点负责隔离）
    Feed::register('sn-shop-test', 'products', [
        'title' => '商品流',
        'items' => fn (): array => [
            ['title' => '不应出现的商品', 'url' => url('/shop/should-not-appear')],
        ],
    ]);

    Feed::flush();

    $response = $this->get('/cms/feed');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

    $xml = $response->getContent();

    expect($xml)
        ->toContain('模块聚合文章')
        // 频道元数据取模块 config 声明（site_name 空时回退 app.name）
        ->toContain('<title>'.e(config('app.name')).'</title>')
        ->toContain(url('/cms'))
        // 其他模块的流被隔离
        ->not->toContain('不应出现的商品');
});

it('模块具名端点输出属于本模块的单流，其他模块流名 404', function () {
    createFeedPost(['title' => '模块具名文章', 'slug' => 'module-named-post']);

    Feed::register('sn-shop-test', 'products', [
        'items' => fn (): array => [
            ['title' => '商品', 'url' => url('/shop/p-1')],
        ],
    ]);

    Feed::flush();

    $xml = $this->get('/cms/feed/posts')->getContent();

    expect($xml)
        ->toContain('模块具名文章')
        ->not->toContain('商品');

    // 属于 shop 模块的流名在 cms 模块端点下不可见
    $this->get('/cms/feed/products')->assertNotFound();

    // 根具名端点仍可访问（全局视角）
    $this->get('/feed/products')->assertOk();
});

/*
 * 注册器行为
 */

it('重名 feed 注册抛异常（feed 名全局唯一）', function () {
    Feed::register('sn-a', 'posts', ['items' => fn (): array => []]);
    Feed::register('sn-b', 'posts', ['items' => fn (): array => []]);
})->throws(InvalidArgumentException::class);

it('缺少 items 闭包的注册抛异常', function () {
    Feed::register('sn-a', 'broken', ['title' => '缺 items']);
})->throws(InvalidArgumentException::class);

it('feed 渲染走缓存，flush 后重新聚合', function () {
    createFeedPost(['title' => '缓存文章', 'slug' => 'feed-cached-post']);

    $first = $this->get('/feed/posts')->getContent();
    expect($first)->toContain('缓存文章');

    // 缓存命中：删除数据后（不 flush）仍是旧内容
    Post::where('slug', 'feed-cached-post')->delete();
    expect($this->get('/feed/posts')->getContent())->toContain('缓存文章');

    // flush 后重新聚合，旧内容消失
    Feed::flush();
    expect($this->get('/feed/posts')->getContent())->not->toContain('缓存文章');
});

/*
 * 前台渲染（footer 入口 + autodiscovery）
 */

it('footer 渲染 RSS 订阅入口与页头 autodiscovery', function () {
    $this->get('/cms')
        ->assertOk()
        ->assertSee('<link rel="alternate" type="application/rss+xml"', false)
        // 入口指向模块端点（路径前缀部署下的隔离形态）
        ->assertSee(url('/cms/feed/posts'), false)
        // footer 入口（posts 流的短标签）
        ->assertSee('文章动态');
});

it('feed.enabled 关闭时 footer 无 RSS 链接与 autodiscovery', function () {
    // 注：流注册发生在 boot 期，运行时 config 无法移除已注册的流，这里只验证渲染层开关
    config(['sn-cms.feed.enabled' => false]);

    $this->get('/cms')
        ->assertOk()
        ->assertDontSee('文章动态')
        ->assertDontSee('<link rel="alternate" type="application/rss+xml"', false);
});

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Filament\Resources\Links\Pages\CreateLink;
use Wsmallnews\Cms\Models\Link;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Models\Post;

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

/**
 * 创建一条友链（挂到默认 scope）
 */
function createLink(array $attributes = []): Link
{
    return Link::create(array_merge([
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'name' => '示例友链',
        'url' => 'https://example.com/link',
        'status' => 'normal',
    ], $attributes));
}

/*
 * 前台 footer 渲染
 */

it('footer 渲染启用状态的友情链接', function () {
    createLink(['name' => '科技日报', 'url' => 'https://tech.example.com']);
    createLink(['name' => '新华网', 'url' => 'https://news.example.com', 'nofollow' => true]);

    $this->get('/cms')
        ->assertOk()
        ->assertSee('友情链接')
        ->assertSee('科技日报')
        ->assertSee('https://tech.example.com')
        ->assertSee('新华网')
        // nofollow 链接带 rel 声明
        ->assertSee('rel="noopener nofollow"', false);
});

it('隐藏与其他 scope 的友链不显示', function () {
    createLink(['name' => '隐藏友链', 'status' => 'hidden']);
    createLink(['name' => '其他范围友链', 'scope_id' => 5]);

    $this->get('/cms')
        ->assertOk()
        ->assertDontSee('隐藏友链')
        ->assertDontSee('其他范围友链');
});

it('友链按 order_column 降序渲染', function () {
    $first = createLink(['name' => '排前面的友链', 'order_column' => 10]);
    $second = createLink(['name' => '排后面的友链', 'order_column' => 1]);

    $response = $this->get('/cms');

    $positionA = strpos($response->getContent(), '排前面的友链');
    $positionB = strpos($response->getContent(), '排后面的友链');

    expect($positionA)->toBeLessThan($positionB);
});

it('页头输出 RSS autodiscovery', function () {
    $this->get('/cms')
        ->assertOk()
        ->assertSee('<link rel="alternate" type="application/rss+xml"', false)
        ->assertSee(url('/feed'), false);
});

it('feed.enabled 关闭时 footer 无 RSS 链接与 autodiscovery', function () {
    // 注：路由注册发生在 boot 期，运行时 config 无法注销路由，这里只验证渲染层开关
    config(['sn-cms.feed.enabled' => false]);

    $this->get('/cms')
        ->assertOk()
        ->assertDontSee('RSS 订阅')
        ->assertDontSee('<link rel="alternate" type="application/rss+xml"', false);
});

/*
 * RSS feed 内容
 */

it('feed 输出 RSS 2.0 与已发布文章', function () {
    $post = Post::create([
        'publisher_type' => 'user',
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => 'RSS 文章标题',
        'slug' => 'rss-feed-post',
        'description' => 'RSS 文章描述',
        'published_at' => now(),
        'status' => 'published',
    ]);

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

it('feed.limit 限制输出条数', function () {
    $publisher = ['publisher_type' => 'user', 'publisher_id' => User::factory()->create()->id];

    foreach (range(1, 5) as $i) {
        Post::create($publisher + [
            'scope_type' => 'sn-cms', 'scope_id' => 0,
            'title' => "限量文章 {$i}", 'slug' => "feed-limit-{$i}",
            'published_at' => now()->subMinutes(10 - $i), 'status' => 'published',
        ]);
    }

    config(['sn-cms.feed.limit' => 2]);

    $xml = $this->get('/feed')->getContent();

    // published_at 最新两条（限量文章 5、4）在，其余不在
    expect(substr_count($xml, '<item>'))->toBe(2)
        ->and($xml)->toContain('限量文章 5')
        ->and($xml)->toContain('限量文章 4')
        ->not->toContain('限量文章 3');
});

/*
 * 后台管理
 */

it('后台友情链接列表与创建页可访问', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    $this->get('/admin/links')
        ->assertOk()
        ->assertSee('友情链接');

    $this->get('/admin/links/create')
        ->assertOk()
        ->assertSee('名称')
        ->assertSee('nofollow');
});

it('后台可创建友链并进入列表', function () {
    $this->actingAs(User::factory()->create(), 'admin');

    livewire(CreateLink::class)
        ->fillForm([
            'name' => '表单创建友链',
            'url' => 'https://form.example.com',
            'nofollow' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $link = Link::where('name', '表单创建友链')->first();

    expect($link)->not->toBeNull()
        ->and($link->url)->toBe('https://form.example.com')
        ->and($link->nofollow)->toBeTrue()
        ->and($link->status->value)->toBe('normal')
        ->and($link->scope_type)->toBe('sn-cms');
});

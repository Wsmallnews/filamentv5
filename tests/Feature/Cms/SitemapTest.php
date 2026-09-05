<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Models\Navigation;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Facades\Sitemap;

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

    // sitemap 渲染结果整份缓存，测试间需清空避免读到上一用例的陈旧内容
    Sitemap::flush();
});

function createSitemapPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'publisher_type' => 'user',
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => '默认标题',
        'slug' => 'sitemap-post-'.Str::random(8),
        'status' => 'published',
    ], $attributes));
}

it('sitemap.xml 输出站点页面与已发布文章', function () {
    $post = createSitemapPost(['title' => 'Sitemap 文章', 'slug' => 'sitemap-published-post']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = $response->getContent();

    expect($xml)
        ->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        // 首页与列表页
        ->toContain('<loc>'.url('/cms').'</loc>')
        ->toContain('<loc>'.url('/cms/posts').'</loc>')
        // 已发布文章 + lastmod
        ->toContain('<loc>'.url('/cms/posts/sitemap-published-post').'</loc>')
        ->toContain('<lastmod>'.$post->updated_at->toDateString().'</lastmod>');
});

it('草稿与隐藏文章不进 sitemap', function () {
    createSitemapPost(['slug' => 'sitemap-draft-post', 'status' => 'draft']);
    createSitemapPost(['slug' => 'sitemap-hidden-post', 'status' => 'hidden']);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)
        ->not->toContain('sitemap-draft-post')
        ->not->toContain('sitemap-hidden-post');
});

it('其他 scope 的文章不进当前 sitemap（租户/scope 隔离）', function () {
    createSitemapPost(['slug' => 'sitemap-default-scope-post']);
    createSitemapPost(['slug' => 'sitemap-other-scope-post', 'scope_id' => 5]);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)
        ->toContain('sitemap-default-scope-post')
        ->not->toContain('sitemap-other-scope-post');
});

it('导航页面（Page/Content 型）进 sitemap', function () {
    Navigation::create([
        'name' => '关于我们',
        'slug' => 'about-us',
        'type' => 'page',
        'status' => 'normal',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->toContain('<loc>'.url('/cms/navigation/about-us').'</loc>');
});

it('robots.txt 自动发现 Filament 面板路径并输出模块注册的禁爬规则', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->getContent())
        ->toContain('User-agent: *')
        // panel 路径自动发现（应用面板注册在 /admin）
        ->toContain('Disallow: /admin')
        // cms 注册的禁爬路径（前缀与路径均来自 sn-cms 路由配置）
        ->toContain('Disallow: /cms/search')
        ->toContain('Sitemap: '.url('/sitemap.xml'));
});

it('sitemap 渲染结果走缓存，flush 后重新聚合', function () {
    createSitemapPost(['slug' => 'sitemap-cached-post']);

    $first = $this->get('/sitemap.xml')->getContent();
    expect($first)->toContain('sitemap-cached-post');

    // 缓存命中：删除数据后（不 flush）仍是旧内容
    Post::where('slug', 'sitemap-cached-post')->delete();
    expect($this->get('/sitemap.xml')->getContent())->toContain('sitemap-cached-post');

    // flush 后重新聚合，旧 URL 消失
    Sitemap::flush();
    expect($this->get('/sitemap.xml')->getContent())->not->toContain('sitemap-cached-post');
});

/*
 * 注册器单元行为
 */

it('sitemap 注册器转义 URL 中的特殊字符', function () {
    Sitemap::registers('test-escape', [
        [
            'key' => 'query',
            'urls' => fn (): array => [
                ['loc' => url('/cms/posts?a=1&b=2')],
            ],
        ],
    ]);

    $xml = Sitemap::flush()->render()->toHtml();

    expect($xml)
        ->toContain('a=1&amp;b=2')
        ->not->toContain('a=1&b=2');
});

it('多模块注册互不覆盖', function () {
    Sitemap::registers('sn-shop', [
        ['key' => 'product', 'urls' => fn (): array => [['loc' => url('/shop/p-1')]]],
    ]);

    Sitemap::flush();
    $xml = $this->get('/sitemap.xml')->getContent();

    // shop 的来源与 cms 的来源同时聚合输出
    expect($xml)
        ->toContain('/shop/p-1')
        ->toContain('<loc>'.url('/cms').'</loc>');
});

it('声明了域名的模块只在匹配域名时参与输出', function () {
    $host = parse_url(config('app.url'), PHP_URL_HOST);   // 测试请求的默认域名

    // shop 绑定独立域名，当前测试请求域名不匹配 → 不输出
    Sitemap::config('sn-shop-domained', ['domain' => 'shop.smallnews.top'])
        ->registers('sn-shop-domained', [
            ['key' => 'product', 'urls' => fn (): array => [['loc' => url('/shop/domained-p-1')]]],
        ]);

    Sitemap::flush();
    expect($this->get('/sitemap.xml')->getContent())->not->toContain('/shop/domained-p-1');

    // 域名声明为当前请求域名 → 输出
    Sitemap::config('sn-shop-local', ['domain' => $host])
        ->registers('sn-shop-local', [
            ['key' => 'product', 'urls' => fn (): array => [['loc' => url('/shop/local-p-1')]]],
        ]);

    Sitemap::flush();
    expect($this->get('/sitemap.xml')->getContent())->toContain('/shop/local-p-1');
});

it('路由式域名模式按通配匹配（{tenant:slug}.example.com）', function () {
    Sitemap::config('sn-shop-pattern', ['domain' => '{tenant:slug}.shop.top'])
        ->registers('sn-shop-pattern', [
            ['key' => 'product', 'urls' => fn (): array => [['loc' => url('/shop/pattern-p-1')]]],
        ]);

    // 当前域名 localhost 不匹配
    Sitemap::flush();
    expect($this->get('/sitemap.xml')->getContent())->not->toContain('/shop/pattern-p-1');

    // 模拟匹配的域名请求
    Sitemap::flush();
    $xml = $this->get('http://tenant-a.shop.top/sitemap.xml')->getContent();
    expect($xml)->toContain('/shop/pattern-p-1');
});

it('域名匹配的模块 robots 规则也只在匹配域名时输出', function () {
    $host = parse_url(config('app.url'), PHP_URL_HOST);   // 测试请求的默认域名

    Sitemap::config('sn-shop-robots', ['domain' => 'shop.smallnews.top'])
        ->robots('sn-shop-robots', ['disallow' => ['shop/cart']]);

    // 当前域名不匹配 → shop/cart 不出现
    expect($this->get('/robots.txt')->getContent())->not->toContain('shop/cart');

    // 域名匹配当前请求时输出
    Sitemap::config('sn-shop-robots-local', ['domain' => $host])
        ->robots('sn-shop-robots-local', ['disallow' => ['shop/local-cart']]);

    expect($this->get('/robots.txt')->getContent())->toContain('Disallow: /shop/local-cart');
});

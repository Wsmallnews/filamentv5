<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Category\Enums\CategoryTypeStatus;
use Wsmallnews\Category\Models\CategoryType;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Cms\Settings\GeneralSettings;
use Wsmallnews\Support\Facades\Seo;

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

    // 列表页分类树查询 sn_categories，测试库需补建 category 包的表（迁移未发布到应用目录）
    foreach (glob(base_path('addons/category/database/migrations/*.php.stub')) ?: [] as $migrationFile) {
        (require $migrationFile)->up();
    }

    // 列表页分类组件挂载时解析 CategoryType（scopeable），缺失会 404
    CategoryType::create([
        'name' => 'Post 分类',
        'level' => 3,
        'status' => CategoryTypeStatus::Normal,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ]);
});

function createSeoPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'publisher_type' => 'user',
        'publisher_id' => User::factory()->create()->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => '默认标题',
        'slug' => 'seo-post-'.Str::random(8),
        'description' => '文章默认描述',
        'status' => 'published',
    ], $attributes));
}

/*
 * 首页
 */

it('首页服务端渲染 title 与基础 OG 标签（未设置时回退 app.name）', function () {
    $this->get('/cms')
        ->assertOk()
        ->assertSee('<title>'.config('app.name').'</title>', false)
        ->assertSee('<meta property="og:site_name" content="'.config('app.name').'">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<link rel="canonical" href="'.url('/cms').'">', false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertDontSee('og:image');
});

it('首页使用设置中的站点名、描述与默认分享图', function () {
    $settings = app(GeneralSettings::class);
    $settings->site_name = 'SEO 测试站点';
    $settings->seo_description = 'SEO 测试描述';
    $settings->default_og_image = 'sn/cms/settings/og-20260904.png';
    $settings->favicon = 'sn/cms/settings/favicon-20260904.png';
    $settings->save();

    $this->get('/cms')
        ->assertOk()
        ->assertSee('<title>SEO 测试站点</title>', false)
        ->assertSee('<meta name="description" content="SEO 测试描述">', false)
        ->assertSee('og:image" content="'.url(files_url('sn/cms/settings/og-20260904.png')), false)
        ->assertSee('<link rel="icon" href="'.url(files_url('sn/cms/settings/favicon-20260904.png')).'">', false);
});

it('未设置 seo_description 时描述回退站点口号', function () {
    $settings = app(GeneralSettings::class);
    $settings->site_slogan = '口号兜底';
    $settings->save();

    $this->get('/cms')
        ->assertOk()
        ->assertSee('<meta name="description" content="口号兜底">', false);
});

it('统计代码注入到页面底部', function () {
    $settings = app(GeneralSettings::class);
    $settings->analytics_code = '<script>var _hmt = _hmt || [];</script>';
    $settings->save();

    $this->get('/cms')
        ->assertOk()
        ->assertSee('<script>var _hmt = _hmt || [];</script>', false);
});

/*
 * 文章页
 */

it('文章页 SEO 使用文章自身标题描述并输出 Article 结构化数据', function () {
    $post = createSeoPost(['title' => 'SEO 文章标题', 'slug' => 'seo-article-post', 'description' => '文章自身的描述']);

    $this->get('/cms/posts/seo-article-post')
        ->assertOk()
        ->assertSee('<title>SEO 文章标题 - '.config('app.name').'</title>', false)
        ->assertSee('<meta name="description" content="文章自身的描述">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('"@type":"Article"', false)
        ->assertSee('"headline":"SEO 文章标题"', false)
        // schema.org author = 文章发布者（Person）
        ->assertSee('"author":{"@type":"Person","name":"'.$post->publisher->name.'"}', false)
        // schema.org publisher = 站点组织
        ->assertSee('"publisher":{"@type":"Organization","name":"'.config('app.name').'"}', false)
        ->assertSee('<link rel="canonical" href="'.url('/cms/posts/seo-article-post').'">', false);
});

it('文章未设置描述时回落站点默认描述', function () {
    $settings = app(GeneralSettings::class);
    $settings->seo_description = '站点兜底描述';
    $settings->save();

    createSeoPost(['title' => '无描述文章', 'slug' => 'no-desc-post', 'description' => null]);

    $this->get('/cms/posts/no-desc-post')
        ->assertOk()
        ->assertSee('<meta name="description" content="站点兜底描述">', false);
});

/*
 * 列表页 / 搜索页 / 认证页
 */

it('列表页输出带后缀标题且可收录', function () {
    $this->get('/cms/posts')
        ->assertOk()
        ->assertSee('<title>'.__('sn-cms::cms.frontend.posts_list').' - '.config('app.name').'</title>', false)
        ->assertDontSee('name="robots"');
});

it('搜索结果页与登录页标记 noindex', function () {
    $this->get('/cms/search')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex">', false);

    $this->get('/cms/login')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex">', false);
});

/*
 * scopeable：SEO 数据源走 scoped 查询，跨 scope 不串数据
 */

it('其他 scope 的文章不可访问（SEO 数据源保持 scope 隔离）', function () {
    createSeoPost(['title' => '默认范围文章', 'slug' => 'scope-default-post']);

    // 另一个 scope 下同名 slug 的文章
    createSeoPost([
        'title' => '其他范围文章',
        'slug' => 'scope-other-post',
        'scope_type' => 'sn-cms',
        'scope_id' => 5,
    ]);

    $this->get('/cms/posts/scope-default-post')
        ->assertOk()
        ->assertSee('<title>默认范围文章 - '.config('app.name').'</title>', false);

    // scope_id=5 的文章在默认 scope 页面不可见
    $this->get('/cms/posts/scope-other-post')->assertNotFound();
});

/*
 * 渲染器单元行为（init 重建页面上下文，渲染器读取当前上下文）
 */

it('Seo 渲染器转义标题中的 html 字符', function () {
    Seo::init('sn-cms')->title('标题<script>alert(1)</script>');

    expect(Seo::render()->toHtml())
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>alert');
});

it('init 重建页面上下文：上一页声明不泄漏到下一页', function () {
    Seo::init('sn-cms')->title('旧页面标题')->robots('noindex');

    // 新页面渲染：seo-init 中间件（或直调 init）重建上下文，旧页面的 title/noindex 全部清空
    Seo::init('sn-cms')->title('新页面标题');

    $html = Seo::render()->toHtml();

    expect($html)
        ->toContain('<title>新页面标题 - '.config('app.name').'</title>')
        ->not->toContain('旧页面标题')
        ->not->toContain('robots');
});

it('seo-init 路由中间件携带模块归属', function () {
    $this->get('/cms')->assertOk();

    expect(app(Wsmallnews\Support\Features\Seo\Seo::class)->getFor())->toBe('sn-cms');
});

/*
 * 多模块：模块键注册互不覆盖
 */

it('多模块注册互不覆盖：cms 页面取本模块默认值', function () {
    // 模拟 shop 模块注册了完全不同的站点默认值
    Seo::config('sn-shop', [
        'site_name' => '商店站点',
        'description' => '商店描述',
        'analytics_code' => '<script>shop-analytics</script>',
    ]);

    $settings = app(GeneralSettings::class);
    $settings->site_name = 'CMS 站点';
    $settings->save();

    $this->get('/cms')
        ->assertOk()
        ->assertSee('<title>CMS 站点</title>', false)
        ->assertSee('<meta property="og:site_name" content="CMS 站点">', false)
        ->assertDontSee('商店站点')
        ->assertDontSee('shop-analytics');
});

it('未声明归属的模块上下文回退全局兜底（app.name）', function () {
    Seo::config('sn-other', ['site_name' => '其他模块']);

    Seo::init(null);
    expect(Seo::defaults()['site_name'])->toBe(config('app.name'));

    Seo::init('sn-other');
    expect(Seo::defaults()['site_name'])->toBe('其他模块');
});

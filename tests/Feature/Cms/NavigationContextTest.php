<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Models\Navigation;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Support\NavigationContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('zh_CN');

    NavigationContext::flush();

    $this->typeId = NavigationType::create([
        'name' => 'Cms',
        'level' => 0,
        'status' => NavigationTypeStatus::Normal,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ])->id;
});

function createUrlNav(string $name, string $url, array $attributes = [], ?Navigation $parent = null): Navigation
{
    $attributes = array_merge([
        'name' => $name,
        'type' => 'url',
        'status' => 'normal',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'type_id' => test()->typeId,
        'options' => ['url' => $url],
    ], $attributes);

    // 经 children 关联创建才会正确建立嵌套集（直接 create 带 parent_id 会产生孤儿节点）
    return $parent ? $parent->children()->create($attributes) : Navigation::create($attributes);
}

it('精确匹配：当前请求命中节点 URL', function () {
    $nav = createUrlNav('新闻', '/cms/posts');

    $this->get('/cms/posts');

    expect(NavigationContext::current('sn-cms'))->toBeInstanceOf(Navigation::class)
        ->and(NavigationContext::current('sn-cms')->id)->toBe($nav->id);
});

it('前缀匹配：文章详情页命中列表节点，段边界对齐不误吃', function () {
    $nav = createUrlNav('新闻', '/cms/posts');
    createUrlNav('其他', '/cms/other');

    $this->get('/cms/posts/some-article');

    expect(NavigationContext::current('sn-cms')->id)->toBe($nav->id);

    // 段边界：/cms/posts-x 不是 /cms/posts 的子路径
    $this->get('/cms/posts-x');
    NavigationContext::flush();
    expect(NavigationContext::current('sn-cms'))->toBeNull();
});

it('最长前缀优先：更深路径的节点胜出', function () {
    createUrlNav('新闻', '/cms/posts');
    $deep = createUrlNav('专题', '/cms/posts/featured');

    $this->get('/cms/posts/featured/list');

    expect(NavigationContext::current('sn-cms')->id)->toBe($deep->id);
});

it('首页节点（URL = /）仅精确匹配，不吃全站前缀', function () {
    $home = createUrlNav('首页', '/');

    $this->get('/');
    expect(NavigationContext::current('sn-cms')->id)->toBe($home->id);

    $this->get('/cms/posts');
    NavigationContext::flush();
    expect(NavigationContext::current('sn-cms'))->toBeNull();
});

it('不可寻址节点（# / 隐藏状态 / 外域链接）不参与匹配', function () {
    createUrlNav('临时页', '#');
    createUrlNav('隐藏页', '/cms/hidden', ['status' => 'hidden']);
    createUrlNav('外链', 'https://example.com/somewhere');

    $this->get('/cms/hidden');
    NavigationContext::flush();
    expect(NavigationContext::current('sn-cms'))->toBeNull();
});

it('scope 隔离：其他模块 scope 的节点不参与匹配', function () {
    createUrlNav('隔壁', '/cms/posts', ['scope_type' => 'sn-shop']);

    $this->get('/cms/posts');
    expect(NavigationContext::current('sn-cms'))->toBeNull();
});

it('banner 继承：匹配节点无图时向上取最近祖先的图', function () {
    $root = createUrlNav('新闻', '/cms/posts');
    $child = $root->children()->create([
        'name' => '专题报道',
        'type' => 'url',
        'status' => 'normal',
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'type_id' => $this->typeId,
        'options' => ['url' => '/cms/reports'],
    ]);

    // 图挂在祖先（新闻分区）上；子页面自身无图 → 继承（字符串流挂媒体，避免测试环境外网下载）
    $root->addMediaFromString('fake-image-content')
        ->usingFileName('banner.jpg')
        ->toMediaCollection('navigation_banner');

    $this->get('/cms/reports');

    expect(NavigationContext::current('sn-cms')->id)->toBe($child->id)
        ->and(NavigationContext::bannerUrl('sn-cms'))->toContain('banner.jpg');
});

it('无匹配或无图时 banner 为空', function () {
    createUrlNav('新闻', '/cms/posts');

    $this->get('/cms/posts');

    expect(NavigationContext::bannerUrl('sn-cms'))->toBeNull();
});

it('同 URL 命中父子节点时取更深者（点击的是具体菜单项，可推导兄弟分组）', function () {
    $root = createUrlNav('新闻', '/cms/posts');
    $child = createUrlNav('推荐', '/cms/posts', parent: $root);

    $this->get('/cms/posts');

    $current = NavigationContext::current('sn-cms');
    expect($current->id)->toBe($child->id)
        ->and((int) $current->depth)->toBe(1);
});

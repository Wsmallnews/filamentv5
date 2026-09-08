<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Filament\Resources\Links\Pages\CreateLink;
use Wsmallnews\Cms\Models\Link;
use Wsmallnews\Cms\Models\NavigationType;

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

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\EngineManager;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Facades\Search;

uses(RefreshDatabase::class);

beforeEach(function () {
    // scout 为可选依赖：未安装时跳过本文件全部用例，安装后（composer require laravel/scout）自动恢复
    if (! class_exists(EngineManager::class)) {
        $this->markTestSkipped('laravel/scout 未安装，跳过 scout 引擎测试。');
    }

    // Scout collection 驱动：基于内存集合过滤，无需外部搜索服务
    config(['scout.driver' => 'collection']);
});

it('scout 引擎按关键词命中模型（scout 闭包过滤生效）', function () {
    User::factory()->create(['name' => '张三丰']);
    User::factory()->create(['name' => '李四']);

    Search::config('app', ['engine' => 'scout'])->registers('app', [
        [
            'key' => 'user',
            'model' => User::class,
            'scout' => fn ($builder) => $builder->where('name', '张三丰'),
        ],
    ]);

    $results = Search::search('app', '张三')->flatten();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('张三丰');
});

it('scout 引擎 limit 限制返回条数', function () {
    User::factory()->create(['name' => '搜索测试一号']);
    User::factory()->create(['name' => '搜索测试二号']);
    User::factory()->create(['name' => '搜索测试三号']);

    Search::config('app', ['engine' => 'scout'])->registers('app', [
        ['key' => 'user', 'model' => User::class],
    ]);

    expect(Search::search('app', '搜索测试', limit: 2)->flatten())->toHaveCount(2);
});

it('模型未 use Scout Searchable 时抛出异常', function () {
    // Post（包内模型）未 use Laravel\Scout\Searchable，模块声明 scout 引擎后整模块不可用
    Search::config('bad', ['engine' => 'scout'])->registers('bad', [
        ['key' => 'post', 'model' => Post::class],
    ]);

    Search::search('bad', '任意');
})->throws(SupportException::class);

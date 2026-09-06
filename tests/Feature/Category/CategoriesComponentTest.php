<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Wsmallnews\Category\Enums\CategoryTypeStatus;
use Wsmallnews\Category\Livewire\Components\Categories;
use Wsmallnews\Category\Models\Category;
use Wsmallnews\Category\Models\CategoryType;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// 分类表的迁移以 stub 形式存在（未发布到主应用），测试内直接执行
beforeEach(function () {
    foreach (['create_sn_category_types_table.php.stub', 'create_sn_categories_table.php.stub'] as $stub) {
        $migration = require addons_path("category/database/migrations/{$stub}");
        $migration->up();
    }
});

function addons_path(string $path): string
{
    return base_path("addons/{$path}");
}

it('分类类型缺失时组件不报错且不查询分类表（短路返回空树）', function () {
    DB::enableQueryLog();

    livewire(Categories::class, ['scopeType' => 'sn-test', 'scopeId' => 0])
        ->assertSet('categoryType', null)
        ->assertSet('categoryTypeId', null);

    $categoryQueries = collect(DB::getQueryLog())
        ->filter(fn ($log) => str_contains($log['query'], 'sn_categories'));

    expect($categoryQueries)->toBeEmpty();
});

it('分类类型存在时正常渲染分类树', function () {
    $type = CategoryType::create([
        'name' => '测试类型',
        'level' => 2,
        'status' => CategoryTypeStatus::Normal,
        'scope_type' => 'sn-test',
        'scope_id' => 0,
    ]);

    Category::create([
        'name' => '一级分类',
        'status' => 'normal',
        'scope_type' => 'sn-test',
        'scope_id' => 0,
        'type_id' => $type->id,
    ]);

    livewire(Categories::class, ['scopeType' => 'sn-test', 'scopeId' => 0])
        ->assertSet('categoryTypeId', $type->id)
        ->assertSee('一级分类');
});

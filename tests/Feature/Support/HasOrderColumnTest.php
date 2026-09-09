<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Wsmallnews\Support\Models\Concerns\HasOrderColumn;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::dropIfExists('order_column_test_posts');
    Schema::create('order_column_test_posts', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->unsignedInteger('order_column')->nullable();
        $table->unsignedBigInteger('team_id')->nullable();
        $table->timestamps();
    });
});

function makeOrderModel(array $attributes = []): Model
{
    $model = new class extends Model
    {
        use HasOrderColumn;

        protected $table = 'order_column_test_posts';

        protected $guarded = [];
    };

    return $model->fill($attributes);
}

function makeScopedOrderModel(array $attributes = []): Model
{
    $model = new class extends Model
    {
        use HasOrderColumn;

        protected $table = 'order_column_test_posts';

        protected $guarded = [];

        protected function modifyOrderColumnQuery(Builder $query): Builder
        {
            return filled($this->team_id) ? $query->where('team_id', $this->team_id) : $query;
        }
    };

    return $model->fill($attributes);
}

it('order_column 留空时自动填充 max+1', function () {
    $first = makeOrderModel(['title' => '第一条']);
    $first->save();
    expect($first->order_column)->toBe(1);

    $second = makeOrderModel(['title' => '第二条']);
    $second->save();
    expect($second->order_column)->toBe(2);
});

it('显式传入的 order_column 不被覆盖（含 0）', function () {
    $explicit = makeOrderModel(['title' => '置顶', 'order_column' => 100]);
    $explicit->save();
    expect($explicit->order_column)->toBe(100);

    $zero = makeOrderModel(['title' => '零值', 'order_column' => 0]);
    $zero->save();
    expect($zero->order_column)->toBe(0);

    // 显式值计入 max，后续自动填充接续
    $auto = makeOrderModel(['title' => '自动']);
    $auto->save();
    expect($auto->order_column)->toBe(101);
});

it('覆盖 modifyOrderColumnQuery 后按维度隔离序号', function () {
    $firstA = makeScopedOrderModel(['title' => 'A 团队一', 'team_id' => 1]);
    $firstA->save();
    expect($firstA->order_column)->toBe(1);

    $secondA = makeScopedOrderModel(['title' => 'A 团队二', 'team_id' => 1]);
    $secondA->save();
    expect($secondA->order_column)->toBe(2);

    $firstB = makeScopedOrderModel(['title' => 'B 团队一', 'team_id' => 2]);
    $firstB->save();
    expect($firstB->order_column)->toBe(1);
});

it('默认空 scope 按全表计数', function () {
    makeScopedOrderModel(['title' => 'A 团队', 'team_id' => 1])->save();
    makeScopedOrderModel(['title' => 'B 团队', 'team_id' => 2])->save();

    $plain = makeOrderModel(['title' => '无 scope']);
    $plain->save();
    expect($plain->order_column)->toBe(2);
});

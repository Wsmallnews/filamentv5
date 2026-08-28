<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Filament\Resources\Products\Pages\EditProduct;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Support\ProductSpecService;

uses(RefreshDatabase::class);

use function Pest\Livewire\livewire;

/**
 * 编辑流重排规格值：组合顺序重编、值与 item key 保留（Livewire DOM diff 依赖 key 稳定）。
 */
it('编辑时重排规格值即时重编组合顺序并保留值与 key', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    $product = Product::factory()->specType(ProductSpecType::Multiple)->create([
        'scope_type' => 'sn-product',
        'scope_id' => 0,
    ]);

    ProductSpecService::save($product, [
        'variant' => [],
        'specs' => [
            ['name' => '颜色', 'children' => [
                ['name' => '红', 'image' => null],
                ['name' => '蓝', 'image' => null],
            ]],
        ],
        'extra_specs' => [
            ['name' => '尺码', 'children' => [
                ['name' => 'M', 'image' => null],
            ]],
        ],
        'variants' => [
            ['spec_names' => ['红', 'M'], 'price' => '10.00', 'product_sn' => 'A1'],
            ['spec_names' => ['蓝', 'M'], 'price' => '20.00', 'product_sn' => 'A2'],
        ],
    ]);

    $component = livewire(EditProduct::class, ['record' => $product->id]);

    // 回填：组合按库中 order_column 顺序，携带记录 id 与 spec_ids
    $filled = $component->instance()->data['variants'];
    $filledKeys = array_keys($filled);

    expect(collect($filled)->pluck('product_spec_text')->all())->toBe(['红,M', '蓝,M'])
        ->and(collect($filled)->pluck('price')->map(fn ($price) => (float) $price)->all())->toBe([10.0, 20.0]);

    // 模拟用户拖动规格值重排（children 顺序反转，触发嵌套层重算钩子）
    $specs = $component->instance()->data['specs'];
    $specUuid = array_key_first($specs);
    $component->set("data.specs.{$specUuid}.children", array_reverse($specs[$specUuid]['children']));

    $reordered = $component->instance()->data['variants'];

    // 顺序按新规格值顺序重编（蓝在前）
    expect(collect($reordered)->pluck('product_spec_text')->all())->toBe(['蓝,M', '红,M'])
        // 值完整保留
        ->and(collect($reordered)->pluck('price')->map(fn ($price) => (float) $price)->all())->toBe([20.0, 10.0])
        ->and(collect($reordered)->pluck('product_sn')->all())->toBe(['A2', 'A1'])
        // item key 稳定（仅顺序移动，不整表换 key）
        ->and(array_keys($reordered))->toBe([$filledKeys[1], $filledKeys[0]]);
});

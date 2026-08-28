<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Filament\Resources\Products\Schemas\ProductSpecForm;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\Spec;
use Wsmallnews\Product\Support\ProductSpecService;

uses(RefreshDatabase::class);

/**
 * 金额断言辅助（cknow/money 的 getAmount() 返回 string）。
 */
function diffAmount(mixed $money): int
{
    return (int) $money->getAmount();
}

/**
 * 创建 颜色[红,蓝] × 尺码[M,L] 的多规格产品并保存。
 *
 * @return array{0: Product, 1: array<string, mixed>} [产品, 表单 state]
 */
function diffProductWithTwoByTwo(): array
{
    $product = Product::factory()->specType(ProductSpecType::Multiple)->create();

    $state = [
        'variant' => [],
        'specs' => [
            [
                'name' => '颜色',
                'children' => [
                    ['name' => '红', 'image' => null],
                    ['name' => '蓝', 'image' => null],
                ],
            ],
        ],
        'extra_specs' => [
            [
                'name' => '尺码',
                'children' => [
                    ['name' => 'M', 'image' => null],
                    ['name' => 'L', 'image' => null],
                ],
            ],
        ],
        'variants' => [
            ['spec_names' => ['红', 'M'], 'price' => '10.00', 'stock' => 1, 'product_sn' => 'A1'],
            ['spec_names' => ['红', 'L'], 'price' => '20.00', 'stock' => 2, 'product_sn' => 'A2'],
            ['spec_names' => ['蓝', 'M'], 'price' => '30.00', 'stock' => 3, 'product_sn' => 'A3'],
            ['spec_names' => ['蓝', 'L'], 'price' => '40.00', 'stock' => 4, 'product_sn' => 'A4'],
        ],
    ];

    ProductSpecService::save($product, $state);

    return [$product, $state];
}

/**
 * 按名称查找规格值模型。
 */
function diffChildByName(Product $product, string $name): Spec
{
    return $product->specs()->where('name', $name)->where('parent_id', '>', 0)->firstOrFail();
}

it('编辑时改名规格值保留记录 id 与变体数据', function () {
    [$product] = diffProductWithTwoByTwo();

    $red = diffChildByName($product, '红');
    $redM = $product->variants()->where('product_sn', 'A1')->first();

    // 模拟编辑：回填 state 后把「红」改名为「大红」
    $state = ProductSpecService::toFormState($product->fresh());

    foreach ($state['specs'] as $uuid => $spec) {
        foreach ($spec['children'] as $childUuid => $child) {
            if ($child['name'] === '红') {
                $state['specs'][$uuid]['children'][$childUuid]['name'] = '大红';
            }
        }
    }

    // 重算组合（改名后指纹基于 id，老值保留）
    $state['variants'] = ProductSpecForm::computeVariants(
        array_merge($state['specs'], $state['extra_specs']),
        $state['variants'],
        ProductSpecType::Multiple->value,
    );

    ProductSpecService::save($product->fresh(), $state);

    // 规格：同一条记录被改名，id 保留
    expect(diffChildByName($product, '大红')->id)->toBe($red->id)
        ->and($product->specs()->where('name', '红')->count())->toBe(0);

    // 变体：原 红×M 记录保留（id 不变），价格/货号保留，组合文本更新
    $variant = $product->variants()->where('product_sn', 'A1')->first();
    expect($variant->id)->toBe($redM->id)
        ->and(diffAmount($variant->price))->toBe(1000)
        ->and($variant->product_spec_text)->toBe(['大红', 'M']);
});

it('编辑时调整顺序不删除任何记录', function () {
    [$product] = diffProductWithTwoByTwo();

    $specIds = $product->specs()->pluck('id')->sort()->values()->all();
    $variantIds = $product->variants()->pluck('id')->sort()->values()->all();

    // 模拟编辑：回填后整体倒序（specs 顺序对调 + 规格值倒序）
    $state = ProductSpecService::toFormState($product->fresh());

    $specs = collect($state['specs'])->merge($state['extra_specs'])->map(function (array $spec) {
        $spec['children'] = collect($spec['children'])->reverse()->values()->toArray();

        return $spec;
    })->reverse()->values()->toArray();

    // 倒序后：第一项是原尺码（无属性），放回 specs 键位；原颜色进 extra_specs
    $state['specs'] = [$specs[0]];
    $state['extra_specs'] = [$specs[1]];

    $state['variants'] = ProductSpecForm::computeVariants(
        array_merge($state['specs'], $state['extra_specs']),
        $state['variants'],
        ProductSpecType::Multiple->value,
    );

    ProductSpecService::save($product->fresh(), $state);

    // 记录全部保留（id 集合不变），仅 order_column 重排
    expect($product->specs()->pluck('id')->sort()->values()->all())->toBe($specIds)
        ->and($product->variants()->pluck('id')->sort()->values()->all())->toBe($variantIds)
        ->and($product->parentSpecs()->get()->pluck('name')->all())->toBe(['尺码', '颜色'])
        // 变体按新组合序排列（尺码组在前、L 值在前）
        ->and($product->variants()->get()->first()->product_spec_text)->toBe(['L', '蓝']);
});

it('编辑时追加规格值保留老变体并追加新组合', function () {
    [$product] = diffProductWithTwoByTwo();

    $redSpecId = diffChildByName($product, '红')->id;
    $oldVariantIds = $product->variants()->pluck('id')->sort()->values()->all();

    // 模拟编辑：颜色新增「绿」（无 id）
    $state = ProductSpecService::toFormState($product->fresh());

    foreach ($state['specs'] as $uuid => $spec) {
        if ($spec['name'] === '颜色') {
            $state['specs'][$uuid]['children'][(string) Str::uuid()] = ['name' => '绿', 'image' => null];
        }
    }

    $state['variants'] = ProductSpecForm::computeVariants(
        array_merge($state['specs'], $state['extra_specs']),
        $state['variants'],
        ProductSpecType::Multiple->value,
    );

    ProductSpecService::save($product->fresh(), $state);

    // 老变体 id 全部保留，新组合（绿×M、绿×L）追加
    $newVariantIds = $product->variants()->pluck('id')->sort()->values()->all();

    expect(count($newVariantIds))->toBe(6)
        ->and(array_intersect($oldVariantIds, $newVariantIds))->toBe($oldVariantIds);

    // 老规格值记录不动，绿为新增记录
    expect(diffChildByName($product, '红')->id)->toBe($redSpecId)
        ->and($product->specs()->where('name', '绿')->where('parent_id', '>', 0)->exists())->toBeTrue();
});

it('编辑时删除规格值移除相关变体', function () {
    [$product] = diffProductWithTwoByTwo();

    $redSpecId = diffChildByName($product, '红')->id;
    $redVariantIds = $product->variants()->whereIn('product_sn', ['A1', 'A2'])->pluck('id')->all();

    // 模拟编辑：删除「蓝」
    $state = ProductSpecService::toFormState($product->fresh());

    foreach ($state['specs'] as $uuid => $spec) {
        if ($spec['name'] === '颜色') {
            $state['specs'][$uuid]['children'] = collect($spec['children'])
                ->reject(fn (array $child): bool => $child['name'] === '蓝')
                ->toArray();
        }
    }

    $state['variants'] = ProductSpecForm::computeVariants(
        array_merge($state['specs'], $state['extra_specs']),
        $state['variants'],
        ProductSpecType::Multiple->value,
    );

    ProductSpecService::save($product->fresh(), $state);

    // 红×2 变体保留，蓝×2 删除，蓝规格值删除
    expect($product->variants()->pluck('id')->sort()->values()->all())->toBe($redVariantIds)
        ->and($product->variants()->count())->toBe(2)
        ->and(diffChildByName($product, '红')->id)->toBe($redSpecId)
        ->and($product->specs()->where('name', '蓝')->count())->toBe(0);
});

it('编辑时新增规格项重建全部变体且不继承数据', function () {
    [$product] = diffProductWithTwoByTwo();

    $oldVariantIds = $product->variants()->pluck('id')->all();
    $oldSpecIds = $product->specs()->pluck('id')->sort()->values()->all();

    // 模拟编辑：新增规格项「材质：棉/丝」（无 id）
    $state = ProductSpecService::toFormState($product->fresh());

    $state['extra_specs'][] = [
        'name' => '材质',
        'children' => [
            ['name' => '棉', 'image' => null],
            ['name' => '丝', 'image' => null],
        ],
    ];

    $state['variants'] = ProductSpecForm::computeVariants(
        array_merge($state['specs'], $state['extra_specs']),
        $state['variants'],
        ProductSpecType::Multiple->value,
    );

    ProductSpecService::save($product->fresh(), $state);

    // 老规格记录全部保留；变体全部重建（8 条新记录，无老 id，数据不继承）
    expect($product->specs()->count())->toBe(9)  // 2 老父 + 4 老子 + 新父(材质) + 2 新子
        ->and(array_intersect($oldSpecIds, $product->specs()->pluck('id')->all()))->toBe($oldSpecIds)
        ->and($product->variants()->count())->toBe(8)
        ->and(array_intersect($oldVariantIds, $product->variants()->pluck('id')->all()))->toBe([])
        // 不继承：所有新组合价格为 0
        ->and($product->variants()->get()->every(fn ($variant) => diffAmount($variant->price) === 0))->toBeTrue();
});

it('编辑单规格时原地更新唯一变体', function () {
    $product = Product::factory()->specType(ProductSpecType::Single)->create();

    ProductSpecService::save($product, [
        'specs' => [],
        'extra_specs' => [],
        'variants' => [],
        'variant' => ['price' => '10.00', 'stock' => 5, 'product_sn' => 'S-1', 'weight' => 0.5],
    ]);

    $variantId = $product->variants()->first()->id;

    // 再次保存（改价格）
    ProductSpecService::save($product->fresh(), [
        'specs' => [],
        'extra_specs' => [],
        'variants' => [],
        'variant' => ['price' => '88.00', 'stock' => 6, 'product_sn' => 'S-1', 'weight' => 0.5],
    ]);

    $variant = $product->variants()->first();

    expect($variant->id)->toBe($variantId)
        ->and(diffAmount($variant->price))->toBe(8800)
        ->and((int) $variant->stock)->toBe(6)
        ->and($product->variants()->count())->toBe(1);
});

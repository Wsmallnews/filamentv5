<?php

/**
 * 一次性种子：T10 演示产品（全面规格/变体/附加项测试数据），跑完即删。
 *
 * php .t10-ref/tmp_seed_product7.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Wsmallnews\Product\Enums\ExtraStatus;
use Wsmallnews\Product\Enums\ExtraType;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Enums\ProductStockType;
use Wsmallnews\Product\Models\Extra;
use Wsmallnews\Product\Models\ExtraItem;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Support\ProductSpecService;

$scope = ['scope_type' => 'sn-shop', 'scope_id' => 0];
$publisher = App\Models\User::first() ?? App\Models\User::create(['name' => 'demo', 'email' => 'demo-t10@test.local', 'password' => bcrypt('demo-t10')]);

// ============ 附加项库 ============
$toppingGroup = Extra::firstOrCreate(
    ['parent_id' => 0] + $scope + ['name' => '加料（可多份）'],
    $scope + ['parent_id' => 0, 'name' => '加料（可多份）', 'type' => ExtraType::Multiple, 'is_require' => false, 'is_num' => true, 'status' => ExtraStatus::Up]
);
$pearl = Extra::firstOrCreate(['parent_id' => $toppingGroup->id, 'name' => '手熬黑糖珍珠（每日现熬）'], ['parent_id' => $toppingGroup->id, 'name' => '手熬黑糖珍珠（每日现熬）', 'price' => '3.00', 'status' => ExtraStatus::Up, 'options' => ['limit_min_num' => 1, 'limit_max_num' => 5]]);
$oat = Extra::firstOrCreate(['parent_id' => $toppingGroup->id, 'name' => '燕麦'], ['parent_id' => $toppingGroup->id, 'name' => '燕麦', 'price' => '2.00', 'status' => ExtraStatus::Up, 'options' => ['limit_min_num' => 1, 'limit_max_num' => 3]]);
$cream = Extra::firstOrCreate(['parent_id' => $toppingGroup->id, 'name' => '奶油顶'], ['parent_id' => $toppingGroup->id, 'name' => '奶油顶', 'price' => '4.00', 'status' => ExtraStatus::Up, 'options' => ['limit_min_num' => 1, 'limit_max_num' => 2]]);
$boba = Extra::firstOrCreate(['parent_id' => $toppingGroup->id, 'name' => '马蹄爆爆珠'], ['parent_id' => $toppingGroup->id, 'name' => '马蹄爆爆珠', 'price' => '3.00', 'status' => ExtraStatus::Up, 'sold_out_until' => now()->endOfDay(), 'options' => ['limit_min_num' => 1, 'limit_max_num' => 3]]);

$heroGroup = Extra::firstOrCreate(
    ['parent_id' => 0] + $scope + ['name' => '加一份主角'],
    $scope + ['parent_id' => 0, 'name' => '加一份主角', 'type' => ExtraType::Single, 'is_require' => false, 'is_num' => false, 'status' => ExtraStatus::Up]
);
$purple = Extra::firstOrCreate(['parent_id' => $heroGroup->id, 'name' => '紫薯波波'], ['parent_id' => $heroGroup->id, 'name' => '紫薯波波', 'price' => '3.00', 'status' => ExtraStatus::Up]);
$cheeseLid = Extra::firstOrCreate(['parent_id' => $heroGroup->id, 'name' => '厚切芝士奶盖（每日限量手作·只用进口芝士隔水现熬八小时）'], ['parent_id' => $heroGroup->id, 'name' => '厚切芝士奶盖（每日限量手作·只用进口芝士隔水现熬八小时）', 'price' => '3.00', 'status' => ExtraStatus::Up]);
$coconut = Extra::firstOrCreate(['parent_id' => $heroGroup->id, 'name' => '生椰冻'], ['parent_id' => $heroGroup->id, 'name' => '生椰冻', 'price' => '2.00', 'status' => ExtraStatus::Up, 'sold_out_until' => now()->endOfDay()]);

// ============ 产品（幂等：重复跑先删重建） ============
$old = Product::where('title', '手作黑糖鹿丸鲜奶茶')->first();
if ($old) {
    $old->variants()->each(fn ($v) => $v->specs()->detach());
    $old->specs()->delete();
    $old->variants()->delete();
    $old->extraItems()->delete();
    $old->delete();
}

$product = Product::create($scope + [
    'title' => '手作黑糖鹿丸鲜奶茶',
    'subtitle' => '虎纹挂壁 · 现熬黑糖 · 鹿丸弹韧',
    'spec_type' => ProductSpecType::Multiple,
    'stock_type' => ProductStockType::Stock,
    'status' => ProductStatus::Up,
    'price' => '12.00',
    'publisher_type' => (new App\Models\User)->getMorphClass(),
    'publisher_id' => $publisher->id,
]);

// 规格组：杯型（带图 5 值，含中长名与超长名）× 温度（4）× 糖度（5）
$cups = [
    ['中杯 500ml', '/demo/img/cup-mid.svg', 0],
    ['大杯 700ml', '/demo/img/cup-large.svg', 200],
    ['超大杯 1L', '/demo/img/cup-huge.svg', 400],
    ['纪念杯·情人节限定联名款', '/demo/img/cup-memory.svg', 600],
    ['周边联名保温杯·限定浮雕外观附赠卡通挂坠与全套贴纸礼盒（不可选热饮）', '/demo/img/cup-thermo.svg', 800],
];
$temps = [['正常冰'], ['少冰'], ['去冰'], ['热饮']];
$sugars = [['全糖'], ['七分糖'], ['五分糖'], ['三分糖'], ['无糖']];

// 变体：全组合去不可售 —— 纪念杯仅正常冰；保温杯/超大杯 × 热饮无；热饮 × 全糖无；超大杯+去冰 库存 2
$variants = [];
$vi = 1;
foreach ($cups as [$cupName, $cupImage, $surcharge]) {
    foreach ($temps as [$tempName]) {
        foreach ($sugars as [$sugarName]) {
            $isMemoryCup = $cupName === '纪念杯·情人节限定联名款';
            $isThermoCup = str_starts_with($cupName, '周边联名保温杯');
            $isHugeCup = $cupName === '超大杯 1L';
            if ($isMemoryCup && $tempName !== '正常冰') {
                continue;
            }
            if (($isThermoCup || $isHugeCup) && $tempName === '热饮') {
                continue;
            }
            if ($tempName === '热饮' && $sugarName === '全糖') {
                continue;
            }

            $stock = ($isHugeCup && $tempName === '去冰') ? 2 : 20;
            $variants['v' . $vi++] = [
                'spec_names' => [$cupName, $tempName, $sugarName],
                'product_spec_text' => $cupName . ',' . $tempName . ',' . $sugarName,
                'image' => null,
                'product_sn' => 'SN-T10-' . sprintf('%03d', $vi),
                'price' => number_format(12 + $surcharge / 100, 2, '.', ''),
                'stock' => $stock,
                'weight' => 0.5,
                'stock_convert_num' => 1,
            ];
        }
    }
}

ProductSpecService::save($product, [
    'variant' => [],
    'specs' => [
        'u-cup' => [
            'name' => '杯型',
            'children' => collect($cups)->mapWithKeys(fn ($c) => ['c-' . md5($c[0]) => ['name' => $c[0], 'image' => $c[1]]])->all(),
        ],
        'u-temp' => [
            'name' => '温度',
            'children' => collect($temps)->mapWithKeys(fn ($t) => ['t-' . md5($t[0]) => ['name' => $t[0], 'image' => null]])->all(),
        ],
        'u-sugar' => [
            'name' => '糖度',
            'children' => collect($sugars)->mapWithKeys(fn ($s) => ['s-' . md5($s[0]) => ['name' => $s[0], 'image' => null]])->all(),
        ],
    ],
    'variants' => $variants,
]);

// 附加项挂载（紫薯波波默认选中；珍珠覆盖价 3.50 演示覆盖）
$mounts = [
    [$pearl, '3.50', true], [$oat, null, false], [$cream, null, false], [$boba, null, false],
    [$purple, null, true], [$cheeseLid, null, false], [$coconut, null, false],
];
foreach ($mounts as [$extra, $override, $isDefault]) {
    ExtraItem::create([
        'product_id' => $product->id,
        'extra_id' => $extra->id,
        'price_override' => $override,
        'is_default' => $isDefault,
    ]);
}

// 主图（spatie media：从 public URL 添加，同磁盘引用）
$product->addMediaFromUrl('http://filamentv5.test/demo/img/product-main.svg')
    ->toMediaCollection('product_image');

echo 'seeded product id=' . $product->id
    . ' variants=' . count($variants)
    . ' specs=' . $product->specs()->where('parent_id', 0)->count()
    . PHP_EOL;

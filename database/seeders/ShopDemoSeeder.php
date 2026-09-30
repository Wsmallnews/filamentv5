<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Wsmallnews\Product\Enums\ProductSpecType;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Enums\ProductStockType;
use Wsmallnews\Product\Enums\VariantStatus;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Product\Models\Spec;
use Wsmallnews\Product\Models\Variant;
use Wsmallnews\Support\Enums\ContentType;

/**
 * Shop 演示数据：演示商品（单规格 / 多规格 / 多单位）+ 商品详情内容。
 *
 * 可重复执行（一键还原）：每次先清空商品相关表再重建。
 * 金额一律传十进制字符串（如 '79.00'），MoneyCast 自动转分存储；
 * 主表价格 = 最低 SKU 价（与 ProductSpecService::save 口径一致）。
 */
class ShopDemoSeeder extends Seeder
{
    /**
     * shop 模块主 scope（前台商品列表/详情均按此查询）
     */
    protected const SHOP_SCOPE = ['scope_type' => 'sn-shop', 'scope_id' => 0];

    protected User $publisher;

    /**
     * 商品序号（order_column / published_at 据此递增）
     */
    protected int $order = 1;

    public function run(): void
    {
        $this->clear();

        $this->publisher = User::first() ?? User::factory()->create();

        // ========================= 单规格（无规格选择，一口价） =========================
        $this->createSingleProduct(
            title: '便携保温杯 380ml',
            subtitle: '316 不锈钢内胆 · 12 小时保温 · 五色可选',
            price: '69.00',
            stockType: ProductStockType::Stock,
            stock: 500,
            detail: '食品级 316 不锈钢内胆，真空双层结构，保温 12 小时、保冷 24 小时。杯身直径 68mm，适配主流车载杯架，一键弹盖单手可开。',
        );

        $this->createSingleProduct(
            title: '极简桌面理线器',
            subtitle: '磁吸分线 · 硅胶材质 · 桌面清爽一步到位',
            price: '29.90',
            stockType: ProductStockType::Infinite,
            detail: '内置四组强磁分区，磁吸取放；加厚硅胶底座防滑不伤桌面，收纳数据线、耳机线、充电头刚刚好。',
        );

        $this->createSingleProduct(
            title: '无线机械键盘 68 键',
            subtitle: '热插拔轴体 · 三模连接 · PBT 键帽',
            price: '329.00',
            stock: 200,
            detail: '68 键紧凑配列，全键热插拔兼容三脚/五脚轴体；蓝牙 / 2.4G / Type-C 三模连接，PBT 二色成型键帽，4000mAh 电池续航约 6 周。',
        );

        // ========================= 多规格（规格组笛卡尔积 SKU） =========================
        // 纯棉基础 T 恤：颜色 × 尺码 = 3 × 3 = 9 SKU
        $this->createMultiSpecProduct(
            title: '纯棉基础 T 恤',
            subtitle: '260g 重磅新疆棉 · 落肩版型 · 不透不闷',
            specGroups: ['颜色' => ['黑色', '白色', '藏青'], '尺码' => ['S', 'M', 'L']],
            variantAttributes: [
                '黑色,S' => ['price' => '79.00', 'stock' => 120],
                '黑色,M' => ['price' => '79.00', 'stock' => 150],
                '黑色,L' => ['price' => '79.00', 'stock' => 90],
                '白色,S' => ['price' => '79.00', 'stock' => 110],
                '白色,M' => ['price' => '79.00', 'stock' => 160],
                '白色,L' => ['price' => '79.00', 'stock' => 80],
                '藏青,S' => ['price' => '85.00', 'stock' => 60],
                '藏青,M' => ['price' => '85.00', 'stock' => 95],
                '藏青,L' => ['price' => '85.00', 'stock' => 45],
            ],
            detail: '260g 重磅新疆长绒棉，高密织造不透光；落肩微廓形版型，领口三针五线工艺水洗不变形。',
        );

        // 手冲咖啡豆：焙度 × 规格 = 3 × 2 = 6 SKU
        $this->createMultiSpecProduct(
            title: '庄园手冲咖啡豆',
            subtitle: '单一产地 · 下单现烘 · 48 小时内发货',
            specGroups: ['焙度' => ['浅焙', '中焙', '深焙'], '规格' => ['250g', '500g']],
            variantAttributes: [
                '浅焙,250g' => ['price' => '45.00', 'stock' => 300],
                '浅焙,500g' => ['price' => '85.00', 'stock' => 150],
                '中焙,250g' => ['price' => '48.00', 'stock' => 260],
                '中焙,500g' => ['price' => '90.00', 'stock' => 130],
                '深焙,250g' => ['price' => '48.00', 'stock' => 240],
                '深焙,500g' => ['price' => '90.00', 'stock' => 120],
            ],
            detail: '云南保山单一庄园批次，日晒处理。浅焙柑橘花香、中焙焦糖坚果、深焙黑巧回甘；下单后现烘，养豆期 5 天风味最佳。',
        );

        // ========================= 多单位（同商品按单位计价） =========================
        // 鲜牛奶：瓶（基础单位）/ 箱（1 箱 = 12 瓶）
        $this->createUnitProduct(
            title: '巴氏鲜牛奶 950ml',
            subtitle: '每日直送 · 72 小时短保 · 冷链配送',
            units: [
                ['name' => '瓶', 'price' => '12.00', 'stock' => 999, 'convert_num' => 1],
                ['name' => '箱', 'price' => '136.80', 'stock' => 83, 'convert_num' => 12],
            ],
            detail: '自有牧场巴氏杀菌工艺，75℃ / 15s 温和杀菌保留活性蛋白；950ml 家庭装，4℃ 冷链直送，保质期 72 小时。',
        );
    }

    // ========================= 创建辅助 =========================

    /**
     * 创建商品主体 + 富文本详情
     */
    protected function makeProduct(string $title, string $subtitle, ProductSpecType $specType, string $price, string $detail, ?ProductStockType $stockType = null): Product
    {
        $product = Product::create([
            'publisher_type' => $this->publisher->getMorphClass(),
            'publisher_id' => $this->publisher->id,
            'title' => $title,
            'subtitle' => $subtitle,
            'spec_type' => $specType,
            'price' => $price,
            'stock_type' => $stockType ?? ProductStockType::Stock,
            'stock_unit' => '件',
            'status' => ProductStatus::Up,
            'published_at' => now()->subDays(10 - $this->order),
            'order_column' => $this->order * 10,
            ...self::SHOP_SCOPE,
        ]);

        $product->content()->create([
            'content' => '<h2>' . e($title) . '</h2><p>' . e($subtitle) . '</p><p>' . e($detail) . '</p>',
            'content_type' => ContentType::Richtext,
        ]);

        return $product;
    }

    /**
     * 单规格商品：一个默认 SKU，前台无规格选择
     */
    protected function createSingleProduct(string $title, string $subtitle, string $price, string $detail, ?int $stock = null, ?ProductStockType $stockType = null): void
    {
        $product = $this->makeProduct($title, $subtitle, ProductSpecType::Single, $price, $detail, $stockType);
        $this->order++;

        $this->makeVariant($product, specIds: [], specText: [], price: $price, stock: $stock ?? 0, sn: $this->sku($product, 'D'));
    }

    /**
     * 多规格商品：规格组（父）× 规格值（子）+ 笛卡尔积 SKU
     *
     * @param  array<string, array<int, string>>  $specGroups  规格组名 => 规格值列表
     * @param  array<string, array{price: string, stock: int}>  $variantAttributes  「值1,值2」 => 属性
     */
    protected function createMultiSpecProduct(string $title, string $subtitle, array $specGroups, array $variantAttributes, string $detail): void
    {
        // 主表价格占位，建完 SKU 后回填最低价
        $product = $this->makeProduct($title, $subtitle, ProductSpecType::Multiple, '0.01', $detail);

        // 规格树：父（组名）→ 子（规格值），组序 = 声明序
        $childIds = [];         // 规格值名 => spec id
        $groupIndex = 0;
        foreach ($specGroups as $groupName => $values) {
            $parent = Spec::create([
                'product_id' => $product->id,
                'parent_id' => 0,
                'name' => $groupName,
                'order_column' => $groupIndex++,
            ]);

            foreach (array_values($values) as $index => $value) {
                $childIds[$value] = Spec::create([
                    'product_id' => $product->id,
                    'parent_id' => $parent->id,
                    'name' => $value,
                    'order_column' => $index,
                ])->id;
            }
        }

        // SKU：笛卡尔积组合（键序与 specGroups 声明序一致）
        $minMinor = null;
        foreach ($this->cartesian(array_values($specGroups)) as $index => $combo) {
            $attributes = $variantAttributes[implode(',', $combo)] ?? ['price' => '0.01', 'stock' => 0];

            $variant = $this->makeVariant(
                $product,
                specIds: array_map(fn (string $value) => $childIds[$value], $combo),
                specText: $combo,
                price: $attributes['price'],
                stock: $attributes['stock'],
                sn: $this->sku($product, 'V' . ($index + 1)),
            );

            $minor = (int) $variant->price->getAmount();
            $minMinor = $minMinor === null ? $minor : min($minMinor, $minor);
        }

        // 主表价格 = 最低 SKU 价
        $product->forceFill(['price' => $this->toDecimal($minMinor ?? 1)])->saveQuietly();
        $this->order++;
    }

    /**
     * 多单位商品：单一「单位」规格组，规格值 = 计价单位（瓶/箱）
     *
     * @param  array<int, array{name: string, price: string, stock: int, convert_num: int}>  $units
     */
    protected function createUnitProduct(string $title, string $subtitle, array $units, string $detail): void
    {
        $minPrice = collect($units)->map(fn (array $unit) => $unit['price'])->map(fn (string $p) => (float) $p)->min();

        $product = $this->makeProduct($title, $subtitle, ProductSpecType::Unit, number_format($minPrice, 2, '.', ''), $detail);
        $this->order++;

        $parent = Spec::create([
            'product_id' => $product->id,
            'parent_id' => 0,
            'name' => Spec::UNIT_NAME,
            'order_column' => 0,
        ]);

        foreach ($units as $index => $unit) {
            $child = Spec::create([
                'product_id' => $product->id,
                'parent_id' => $parent->id,
                'name' => $unit['name'],
                'order_column' => $index,
            ]);

            $this->makeVariant(
                $product,
                specIds: [$child->id],
                specText: [$unit['name']],
                price: $unit['price'],
                stock: $unit['stock'],
                sn: $this->sku($product, 'U' . ($index + 1)),
                stockConvertNum: $unit['convert_num'],
            );
        }
    }

    /**
     * 创建 SKU 并挂接规格值
     *
     * @param  array<int, int>  $specIds  子规格 id 列表
     * @param  array<int, string>  $specText  规格值名列表（ImplodeCast 需要数组入参）
     */
    protected function makeVariant(Product $product, array $specIds, array $specText, string $price, int $stock, string $sn, ?int $stockConvertNum = null): Variant
    {
        $variant = Variant::create([
            'product_id' => $product->id,
            'product_spec_text' => $specText,
            'product_sn' => $sn,
            'spec_type' => $product->spec_type,
            'price' => $price,
            'stock' => $stock,
            'sales' => random_int(0, 60),
            'stock_unit' => '件',
            'stock_convert_num' => $stockConvertNum ?? 1,
            'status' => VariantStatus::Up,
            'order_column' => count($specText),
        ]);

        if ($specIds !== []) {
            $variant->specs()->attach($specIds);
        }

        return $variant;
    }

    /**
     * 笛卡尔积（保持组内声明顺序）
     *
     * @param  array<int, array<int, string>>  $groups
     * @return array<int, array<int, string>>
     */
    protected function cartesian(array $groups): array
    {
        $result = [[]];
        foreach ($groups as $group) {
            $append = [];
            foreach ($result as $combination) {
                foreach ($group as $value) {
                    $append[] = [...$combination, $value];
                }
            }
            $result = $append;
        }

        return $result;
    }

    /**
     * SKU 编码：DEMO-{商品id}-{后缀}
     */
    protected function sku(Product $product, string $suffix): string
    {
        return sprintf('DEMO-%03d-%s', $product->id, $suffix);
    }

    /**
     * 分（int）→ 十进制字符串（MoneyCast 按十进制入参转分存储）
     */
    protected function toDecimal(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }

    /**
     * 清空老数据（商品三表 + 关联透视 / 内容 / 媒体）
     */
    protected function clear(): void
    {
        collect(['sn_product_spec_variants', 'sn_product_variants', 'sn_product_specs', 'sn_products'])
            ->each(fn (string $table) => DB::table($table)->truncate());

        DB::table('sn_contents')->where('contentable_type', 'sn_product')->delete();
        DB::table('media')->where('model_type', 'sn_product')->delete();
    }
}

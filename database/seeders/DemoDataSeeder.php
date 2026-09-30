<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 演示数据一键还原入口。
 *
 * 清空并重建 CMS（导航类型 / 主导航 / 底部导航 / 站点页面 / 内容编排）
 * 与 Shop（演示商品：单规格 / 多规格 / 多单位 SKU）的演示数据：
 *
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CmsDemoSeeder::class,
            ShopDemoSeeder::class,
        ]);
    }
}

<?php

/**
 * 临时种子脚本：为「更多」按钮溢出折叠测试造数据
 *
 * 追加 8 个顶级导航（落在列表末尾，优先被折叠进「更多」）：
 * - 3 个多级父项（含 4 级深度链，测试级联/手风琴无限级）
 * - 1 个深层激活点（服务器系列 → /cms，访问首页时整条祖先链 has_active，
 *   产品中心被折叠 → 「更多」按钮高亮）
 * - 3 个叶子和 1 个超长名称项（撑宽主行，加快触发溢出）
 *
 * 用后可删除本脚本；测试数据可按 name 前缀（产品中心/解决方案/服务支持/合作生态
 * 及 客户案例/招贤纳士/投资者关系/测试超长）在后台识别清理。
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Wsmallnews\Cms\Models\Navigation;

$typeId = Navigation::whereNull('parent_id')->first()->type_id;
$appUrl = rtrim(config('app.url'), '/');

$base = [
    'type' => 'url',
    'status' => 'normal',
    'scope_type' => 'sn-cms',
    'scope_id' => 0,
    'type_id' => $typeId,
];

$mk = function (string $name, ?string $url = null, bool $container = false) use ($base): array {
    $attributes = array_merge($base, [
        'name' => $name,
        'type' => $container ? 'child' : 'url',
        'options' => $url !== null ? ['url' => $url] : [],
    ]);

    return $attributes;
};

$ext = fn (string $path) => 'https://example.com/'.ltrim($path, '/');

// 幂等：已存在同名顶级项则跳过
if (Navigation::whereNull('parent_id')->where('name', '产品中心')->exists()) {
    echo "测试数据已存在，跳过插入。\n";
    exit(0);
}

// 1. 产品中心（4 级链 + 激活点）
$product = Navigation::create($mk('产品中心', container: true));
$intro = $product->children()->create($mk('产品介绍', "{$appUrl}/cms/posts"));
$hardware = $intro->children()->create($mk('硬件产品', container: true));
$hardware->children()->create($mk('服务器系列', "{$appUrl}/cms"));
$hardware->children()->create($mk('网络设备', $ext('hardware/network')));
$intro->children()->create($mk('软件产品', $ext('software')));
$product->children()->create($mk('产品对比', $ext('compare')));
$product->children()->create($mk('定制开发', $ext('custom')));

// 2. 解决方案（4 级链）
$solution = Navigation::create($mk('解决方案', container: true));
$gov = $solution->children()->create($mk('政务方案', $ext('gov')));
$prov = $gov->children()->create($mk('省级政务', $ext('gov/province')));
$prov->children()->create($mk('政务云平台', $ext('gov/cloud')));
$gov->children()->create($mk('市级政务', $ext('gov/city')));
$solution->children()->create($mk('教育方案', $ext('edu')));
$solution->children()->create($mk('医疗方案', $ext('med')));

// 3. 服务支持（3 级）
$service = Navigation::create($mk('服务支持', container: true));
$service->children()->create($mk('文档中心', $ext('docs')));
$service->children()->create($mk('常见问题', $ext('faq')));
$booking = $service->children()->create($mk('预约支持', $ext('booking')));
$booking->children()->create($mk('售前支持', $ext('pre-sales')));
$booking->children()->create($mk('售后支持', $ext('after-sales')));

// 4-6. 简单父项与叶子
$eco = Navigation::create($mk('合作生态', container: true));
$eco->children()->create($mk('合作伙伴', $ext('partners')));
$eco->children()->create($mk('代理加盟', $ext('agents')));
Navigation::create($mk('客户案例', $ext('cases')));
Navigation::create($mk('招贤纳士', $ext('jobs')));

// 7. 投资者关系（叶子）
Navigation::create($mk('投资者关系', $ext('ir')));

// 8. 超长名称项（撑宽主行）
Navigation::create($mk('测试超长名称的导航项', $ext('long-name')));

$roots = Navigation::whereNull('parent_id')->count();
$total = Navigation::count();
echo "插入完成：顶级 {$roots} 项，总计 {$total} 条。\n";

// 校验树结构完整性
$broken = Navigation::isBroken();
echo $broken ? "警告：树结构损坏！\n" : "树结构完整性校验通过。\n";

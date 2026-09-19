<?php

use Wsmallnews\Category\Filament\Pages\Category\CategoryPage;
use Wsmallnews\Category\Filament\Resources\CategoryTypes\CategoryTypeResource;
use Wsmallnews\Category\Models;

return [
    /**
     * Default scopeable
     */
    'scopeable' => [
        'scope_type' => 'sn-category',
        'scope_id' => 0,
    ],

    /**
     * Custom models
     */
    'models' => [
        'category' => Models\Category::class,
        'category_type' => Models\CategoryType::class,
    ],

    /**
     * Panel register
     *
     * global_default 共享默认（非 FQCN 的 string key）会合并到所有条目：
     *   - navigation_group: 所有页面/资源的默认导航组
     *
     * 条目格式：
     *   - 简单 FQCN：ClassName::class（仅合并共享默认）
     *   - 键值对：ClassName::class => ['key' => 'value']（合并共享默认 + 自定义覆盖）
     *   - 配置项键名使用 snake_case（如 navigation_label、navigation_icon）
     */
    'panel_register' => [
        'global_default' => [
            'navigation_group' => 'sn-category::category.global_default.navigation_group',
        ],
        'resources' => [
            CategoryTypeResource::class,
        ],
        'pages' => [
            CategoryPage::class,
        ],
    ],

    /**
     * 文件基础目录，会自动拼接当前年月日 (仅用于 filament 默认上传组件 (Forms\Components\FileUpload))
     */
    'file_directory' => 'sn/categories/',

];

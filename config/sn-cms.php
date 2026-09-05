<?php

use Wsmallnews\Category\Filament\Pages\Category\CategoryPage as PostCategoryPage;
use Wsmallnews\Cms\Enums;
use Wsmallnews\Cms\Filament\Pages\GeneralSetting as GeneralSettingPage;
use Wsmallnews\Cms\Filament\Pages\Navigation\Footer\FooterNavigationPage;
use Wsmallnews\Cms\Filament\Pages\Navigation\NavigationPage;
use Wsmallnews\Cms\Filament\Resources\NavigationTypes\NavigationTypeResource;
use Wsmallnews\Cms\Filament\Resources\Posts\PostResource;
use Wsmallnews\Cms\Models;
use Wsmallnews\Comment\Enums\CommentStatus;
use Wsmallnews\Support\Enums\ContentType;

return [
    /**
     * Default scopeable
     */
    'scopeable' => [
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
    ],

    /**
     * Custom models
     */
    'models' => [
        'navigation' => Models\Navigation::class,
        'navigation_type' => Models\NavigationType::class,
        'post' => Models\Post::class,
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
            'navigation_group' => 'sn-cms::cms.global_default.navigation_group',
        ],
        'resources' => [
            NavigationTypeResource::class,
            PostResource::class,
        ],
        'pages' => [
            PostCategoryPage::class => [
                'key' => 'post-category',
                'navigation_parent_item' => 'sn-cms::cms.post_resource.navigation_label',

                // 需与 PostResource 的 scopeable 保持一致(PostResource 默认值为当前配置文件的 scopeable 配置)
                'scope_type' => 'sn-cms',
                'scope_id' => 0,
            ],
            GeneralSettingPage::class,
            NavigationPage::class,
            FooterNavigationPage::class,
        ],
    ],

    /**
     * auth guard
     */
    'guard' => 'web',

    /**
     * auth_user_type
     *
     * 默认为 wsmallnews/member 模块，可选 wsmallnews/user 模块, 示例值：member | user
     * 如果你使用多租户， 请设置为 member 模块
     */
    'auth_user_type' => 'member',

    /**
     * 2FA 配置
     */
    'two_factor' => [
        /**
         * 是否启用双因素认证
         */
        'enabled' => true,

        /**
         * 在启用双因素认证时，必须确认一次，否则启动失败
         * two_factor_confirmed_at: 记录启用确认时间，如果为null, two_factor_secret， two_factor_recovery_codes 会被清空，用户双因素启用失败
         */
        'confirm' => true,

        /**
         * 验证窗口，单位：分钟
         */
        'window' => 1,
    ],

    /**
     * 文件基础目录，会自动拼接当前年月日 (仅用于 filament 默认上传组件 (Forms\Components\FileUpload))
     */
    'file_directory' => 'sn/cms/',

    /**
     * 内容表单配置（FormComponents::contentTypeGroup）
     * types: 允许的内容类型；default_type: 默认内容类型
     */
    'contents' => [
        'post' => [
            'types' => null,
            'default_type' => ContentType::Richtext,
        ],
        'navigation' => [
            'types' => null,
            'default_type' => ContentType::Markdown,
        ],
    ],

    'routes' => [
        /**
         * Whether to enable the cms routes.
         */
        'enabled' => true,
        /**
         * The domain where the cms routes should be registered.
         * If you differentiate tenants by domain, you should set it like this: {tenant:slug}.example.com
         */
        'domain' => null,
        /**
         * the middleware you want to apply on all the cms routes
         * for example if you want to make your cms for users only, add the middleware 'auth'.
         */
        'middleware' => ['web'],
        /**
         * Default path for the blog homepage.
         * If you differentiate tenants by url, you should set it like this: cms/{tenant:slug}
         */
        'prefix' => 'cms',
        /**
         * Default name prefix for the cms routes.
         */
        'name' => 'sn-cms.',
        /**
         * Default route key name for the cms models.
         */
        'route_key_name' => [
            'navigation' => 'slug',
            'post' => 'slug',
        ],
        /**
         * default uri for the cms routes
         */
        'uri' => [
            'index' => '/',
            'navigation-show' => 'navigation/{slug}',
            'posts' => 'posts',
            'posts-show' => 'posts/{slug}',

            // 全局搜索结果页（search.display = 'page' 时搜索框回车跳转目标）
            'search' => 'search',

            'login' => 'login',
            'register' => 'register',
            'profile' => 'profile',
            'profile-views' => 'profile/views',
            'forgot-password' => 'forgot-password',
            'reset-password' => 'reset-password/{token}',
            'verify-email' => 'verify-email',
            'verify-email-verification' => 'verify-email/{id}/{hash}',
            'password-confirm' => 'password-confirm',

            // 用户设置
            'settings' => [
                'profile' => 'settings/profile',
                'password' => 'settings/password',
                'two-factor' => 'settings/two-factor',
            ],
        ],
    ],

    /**
     * 是否支持评论
     */
    'comments' => [
        /**
         * post comment
         */
        'post' => [
            /**
             * 是否启用评论
             */
            'enable' => true,
            /**
             * 是否启用添加评论
             */
            'can_add_comment' => true,
            /**
             * 评论内容类型
             */
            'content_type' => ContentType::Textarea,
            /**
             * 默认评论状态
             */
            'comment_status' => CommentStatus::Normal,
        ],
    ],

    /**
     * 全局搜索（前端搜索框仅在本模块启用时渲染，来源也仅在启用时参与搜索）
     */
    'search' => [
        /**
         * 是否启用本模块的全局搜索
         */
        'enabled' => true,

        /**
         * 本模块搜索引擎：'database' | 'scout' | 引擎类名；null 走全局兜底
         * （config('sn-support.search.engine')，默认 database）
         */
        'engine' => null,

        /**
         * 搜索结果的展示方式：'dropdown'（输入即搜，浮层展示）| 'page'（回车跳转独立搜索结果页）
         * null 走全局兜底（config('sn-support.search.display')，默认 dropdown）
         */
        'display' => 'page',
    ],

    'themes' => [
        // 是否启用暗黑模式
        'dark_mode' => true,

        // 默认主题模式
        'default_dark_mode' => 'system',

        // 强制暗黑主题
        'dark_mode_forced' => false,

        'layout' => 'sn-cms::components.layouts.app',

        'theme' => 'tradition',

        // 页面容器
        'page_container' => 'sn-cms::container.page',
    ],

    /**
     * 可自定义的 enum
     *
     * 替换类必须实现对应契约接口，并保证契约常量对应的值存在（值是数据库与查询逻辑的稳定契约）。
     * 只有"展示元数据型" enum 才登记在此（label/color/icon 随站点变化、业务行为不依赖 case 身份），
     * 业务状态机型 enum（如 PostStatus）不开放配置。
     */
    'enums' => [
        // 图文 flag，须实现 Wsmallnews\Cms\Contracts\PostFlagContract
        'post_flag' => Enums\PostFlag::class,
    ],
];

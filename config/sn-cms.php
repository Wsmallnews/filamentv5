<?php

use Filament\Support\Icons\Heroicon;
use Wsmallnews\Cms\Enums;
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
     * auth guard
     */
    'guard' => 'web',

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

            'login' => 'login',
            'register' => 'register',
            'profile' => 'profile',
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
     * 推荐标签配置
     * 用户可以自定义追加，比如精品、必看等
     */
    'flags' => [
        [
            'type' => 'hot',
            'label' => '热门',
            'color' => 'danger',
            'icon' => Heroicon::OutlinedFire,
        ],
        [
            'type' => 'new',
            'label' => '新',
            'color' => 'danger',
            'icon' => Heroicon::OutlinedSparkles,
        ],
        [
            'type' => 'recommend',
            'label' => '推荐',
            'color' => 'primary',
            'icon' => Heroicon::OutlinedStar,
        ],
        [
            'type' => 'top',
            'label' => '置顶',
            'color' => 'warning',
            'icon' => Heroicon::OutlinedArrowUp,
        ],
    ],

    // 'enums' => [
    //     'navigation_status' => Enums\NavigationStatus::class,
    //     'navigation_type_status' => Enums\NavigationTypeStatus::class,
    //     'post_status' => Enums\PostStatus::class,
    // ],
];

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Wsmallnews\Cms\Enums\NavigationStatus;
use Wsmallnews\Cms\Enums\NavigationType as NavigationTypeEnum;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Models\Navigation;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Enums\ContentType;
use Wsmallnews\Support\Enums\PageStatus;
use Wsmallnews\Support\Models\Composition;
use Wsmallnews\Support\Models\Page;

/**
 * CMS 演示数据：导航类型 / 主导航树 / 底部导航树 / 站点页面 / 内容编排。
 *
 * 可重复执行（一键还原）：每次先清空相关表再重建。文章（sn_posts）与
 * 分类（sn_categories）不在本 seeder 职责内，保留现场数据供编排组件展示。
 */
class CmsDemoSeeder extends Seeder
{
    /**
     * 主导航 scope（cms main 实例）
     */
    protected const MAIN_SCOPE = ['scope_type' => 'sn-cms', 'scope_id' => 0];

    /**
     * 底部导航 scope（cms footer 差异实例）
     */
    protected const FOOTER_SCOPE = ['scope_type' => 'sn-cms-footer', 'scope_id' => 0];

    public function run(): void
    {
        $this->clear();

        // ========================= 导航类型 =========================
        // level = 3：一级 + 下拉二级 + 三级（级联展开）
        $mainType = NavigationType::create([
            'name' => '主导航',
            'level' => 3,
            'status' => NavigationTypeStatus::Normal,
            'order_column' => 1,
            ...self::MAIN_SCOPE,
        ]);

        // level = 2：一级分组 + 子链接（前台 Footer 分组列形态）
        $footerType = NavigationType::create([
            'name' => '底部导航',
            'level' => 2,
            'status' => NavigationTypeStatus::Normal,
            'order_column' => 2,
            ...self::FOOTER_SCOPE,
        ]);

        // ========================= 内容编排 =========================
        $homeComposition = Composition::create([
            'title' => '首页编排',
            'purpose' => null,          // 通用展示编排（页面经 composition_id 绑定）
            'status' => CompositionStatus::Published,
            'components' => [
                [
                    'layout' => 'full',
                    'left' => [
                        ['type' => 'index-posts', 'label' => '最新动态', 'extras' => []],
                    ],
                    'right' => [],
                ],
                [
                    'layout' => '2-1',
                    'left' => [
                        ['type' => 'posts', 'label' => '图文动态', 'extras' => [
                            'categoryStyle' => 'select',
                            'categoryIds' => null,
                        ]],
                    ],
                    'right' => [
                        ['type' => 'posts', 'label' => '分类速览', 'extras' => [
                            'categoryStyle' => 'select',
                            'categoryIds' => null,
                        ]],
                    ],
                ],
            ],
            ...self::MAIN_SCOPE,
        ]);

        // 文章详情页侧栏（purpose 槽位：post-sidebar，stack 模式行强制通栏）
        Composition::create([
            'title' => '文章页侧栏',
            'purpose' => 'post-sidebar',
            'status' => CompositionStatus::Published,
            'options' => ['position' => 'right'],
            'components' => [
                [
                    'layout' => 'full',
                    'left' => [
                        ['type' => 'related-posts', 'label' => '相关推荐', 'extras' => ['limit' => 6]],
                    ],
                    'right' => [],
                ],
            ],
            ...self::MAIN_SCOPE,
        ]);

        // ========================= 站点页面 =========================
        $homePage = Page::create([
            'title' => '首页',
            'slug' => 'home',
            'composition_id' => $homeComposition->id,
            'status' => PageStatus::Published,
            ...self::MAIN_SCOPE,
        ]);

        $aboutPage = Page::create([
            'title' => '关于我们',
            'slug' => 'about',
            'status' => PageStatus::Published,
            ...self::MAIN_SCOPE,
        ]);
        $aboutPage->content()->create([
            'content' => <<<'HTML'
<h2>我们是谁</h2>
<p>小新闻科技成立于 2020 年，是一支专注内容管理与电商数字化的研发团队。我们相信好的工具应该开箱即用，让创作者把时间花在内容本身。</p>
<h2>我们在做什么</h2>
<p>围绕 Laravel 生态，我们打造了一套模块化的内容与商城系统：CMS 内容管理、商品中心、订单与支付、会员与积分，全部可插拔组合。</p>
<ul>
<li>内容驱动：多级导航、页面编排、定时发布</li>
<li>商业闭环：商品 / 订单 / 支付 / 虚拟钱包</li>
<li>多端适配：响应式主题，暗黑模式开箱即用</li>
</ul>
<h2>联系我们</h2>
<p>商务合作请发送邮件至 <a href="mailto:hello@example.com">hello@example.com</a>，或前往「联系我们」页面。</p>
HTML,
            'content_type' => ContentType::Richtext,
        ]);

        $contactPage = Page::create([
            'title' => '联系我们',
            'slug' => 'contact',
            'status' => PageStatus::Published,
            ...self::MAIN_SCOPE,
        ]);
        $contactPage->content()->create([
            'content' => <<<'HTML'
<h2>联系方式</h2>
<p>工作日 9:00 - 18:00 在线，以下渠道均可达：</p>
<ul>
<li>商务合作：hello@example.com</li>
<li>技术支持：support@example.com</li>
<li>办公地址：广东省深圳市南山区科技园</li>
</ul>
<h2>常见问题</h2>
<p>提交工单前建议先查阅「服务条款」与「隐私政策」，多数账号与订单问题在文档中已有解答。</p>
HTML,
            'content_type' => ContentType::Richtext,
        ]);

        $privacyPage = Page::create([
            'title' => '隐私政策',
            'slug' => 'privacy',
            'status' => PageStatus::Published,
            ...self::MAIN_SCOPE,
        ]);
        $privacyPage->content()->create([
            'content' => <<<'MD'
## 信息收集

我们仅收集为提供服务所必需的信息：账号标识、订单记录与内容偏好。浏览行为数据经匿名化处理，仅用于改进推荐质量。

## 信息使用

收集的信息用于：订单履行、内容推荐、安全风控。我们不会将你的个人信息出售给任何第三方。

## 数据安全

全部数据传输经 HTTPS 加密，支付信息由支付网关托管，本站不存储银行卡敏感信息。

## 政策更新

政策如有重大变更，将通过站内公告与邮件通知。
MD,
            'content_type' => ContentType::Markdown,
        ]);

        $termsPage = Page::create([
            'title' => '服务条款',
            'slug' => 'terms',
            'status' => PageStatus::Published,
            ...self::MAIN_SCOPE,
        ]);
        $termsPage->content()->create([
            'content' => <<<'MD'
## 服务说明

本站提供内容发布与在线商城服务。注册即表示你同意遵守本条款及所在地区法律法规。

## 账号规则

- 一个自然人仅可注册一个账号；
- 账号密码妥善保管，因泄露造成的损失由账号持有人承担；
- 发布违规内容，本站有权删除并限制账号功能。

## 订单与退款

虚拟商品一经发放除质量问题外不支持退款；实物商品支持签收后 7 天无理由退货，定制类商品除外。

## 条款修改

本站保留修改条款的权利，修改后的条款自发布时生效。
MD,
            'content_type' => ContentType::Markdown,
        ]);

        // ========================= 主导航树 =========================
        // 首页：is_home 标记（模型 saving 钩子强制 page 类型并互斥）
        Navigation::create([
            'name' => '首页',
            'type' => NavigationTypeEnum::Page,
            'page_id' => $homePage->id,
            'status' => NavigationStatus::Normal,
            'options' => $this->navigationOptions(['is_home' => true]),
            'type_id' => $mainType->id,
            ...self::MAIN_SCOPE,
        ]);

        // 新闻中心（child 父级 + 两个路由子项）
        $news = $this->createNavigation('新闻中心', $mainType, NavigationTypeEnum::Child, null, null);
        $this->createNavigation('公司新闻', $mainType, NavigationTypeEnum::Route, null, 'sn-cms.posts', $news);
        $this->createNavigation('行业新闻', $mainType, NavigationTypeEnum::Route, null, 'sn-cms.posts', $news);

        // 商城（跨模块路由）
        $this->createNavigation('商城', $mainType, NavigationTypeEnum::Route, null, 'sn-shop.index');

        // 静态页
        $this->createNavigation('关于我们', $mainType, NavigationTypeEnum::Page, $aboutPage);
        $this->createNavigation('联系我们', $mainType, NavigationTypeEnum::Page, $contactPage);

        // ========================= 底部导航树 =========================
        $products = $this->createNavigation('产品与服务', $footerType, NavigationTypeEnum::Child, null, null, null, self::FOOTER_SCOPE);
        $this->createNavigation('商城首页', $footerType, NavigationTypeEnum::Route, null, 'sn-shop.index', $products, self::FOOTER_SCOPE);
        $this->createNavigation('全部商品', $footerType, NavigationTypeEnum::Route, null, 'sn-shop.products', $products, self::FOOTER_SCOPE);

        $newsGroup = $this->createNavigation('新闻资讯', $footerType, NavigationTypeEnum::Child, null, null, null, self::FOOTER_SCOPE);
        $this->createNavigation('公司新闻', $footerType, NavigationTypeEnum::Route, null, 'sn-cms.posts', $newsGroup, self::FOOTER_SCOPE);
        $this->createNavigation('行业新闻', $footerType, NavigationTypeEnum::Route, null, 'sn-cms.posts', $newsGroup, self::FOOTER_SCOPE);

        $support = $this->createNavigation('支持', $footerType, NavigationTypeEnum::Child, null, null, null, self::FOOTER_SCOPE);
        $this->createNavigation('关于我们', $footerType, NavigationTypeEnum::Page, $aboutPage, null, $support, self::FOOTER_SCOPE);
        $this->createNavigation('联系我们', $footerType, NavigationTypeEnum::Page, $contactPage, null, $support, self::FOOTER_SCOPE);
        $this->createNavigation('服务条款', $footerType, NavigationTypeEnum::Page, $termsPage, null, $support, self::FOOTER_SCOPE);
        $this->createNavigation('隐私政策', $footerType, NavigationTypeEnum::Page, $privacyPage, null, $support, self::FOOTER_SCOPE);

        // 修复嵌套集结构（按各自 scope 重建 _lft/_rgt）
        Navigation::scoped([...self::MAIN_SCOPE, 'type_id' => $mainType->id])->fixTree();
        Navigation::scoped([...self::FOOTER_SCOPE, 'type_id' => $footerType->id])->fixTree();
    }

    /**
     * 创建导航节点（平铺创建 + fixTree 重建，规避嵌套集指针手工维护）
     */
    protected function createNavigation(
        string $name,
        NavigationType $type,
        NavigationTypeEnum $navType,
        ?Page $page = null,
        ?string $route = null,
        ?Navigation $parent = null,
        ?array $scope = null,
    ): Navigation {
        // 注意属性顺序：scope + type_id 必须先于 parent_id 赋值——
        // kalnoy 的 setParentIdAttribute 会按模型当前 scope 属性过滤查找父节点
        return Navigation::create([
            'name' => $name,
            'type' => $navType,
            'page_id' => $page?->id,
            'status' => NavigationStatus::Normal,
            'options' => $this->navigationOptions($route ? ['route' => $route] : []),
            'type_id' => $type->id,
            ...($scope ?? self::MAIN_SCOPE),
            'parent_id' => $parent?->id,
        ]);
    }

    /**
     * 导航 options 公共骨架 + 按类型附加字段
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function navigationOptions(array $extra = []): array
    {
        return array_merge([
            'icon_type' => 'none',
            'icon' => null,
            'active_icon' => null,
            'icon_src' => null,
            'active_icon_src' => null,
            'target' => '_self',
            '_url_params' => [
                'has_routes' => false,
                'has_queries' => false,
                'routes' => [],
                'queries' => [],
            ],
        ], $extra);
    }

    /**
     * 清空老数据（含软删除行；navigations 为旧模块遗留表一并清理）
     */
    protected function clear(): void
    {
        collect(['sn_navigations', 'sn_navigation_types', 'sn_pages', 'sn_compositions', 'navigations'])
            ->each(fn (string $table) => DB::table($table)->truncate());

        DB::table('sn_contents')->where('contentable_type', 'sn_page')->delete();
        DB::table('media')->whereIn('model_type', ['sn_page', 'sn_navigation', 'sn_composition'])->delete();
    }
}

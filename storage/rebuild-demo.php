<?php

/*
 * 演示数据重建脚本（可重复执行，仅在全新库上跑一次）
 * 用法：php artisan tinker --execute='require "storage/rebuild-demo.php";'
 */

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Wsmallnews\Cms\Enums\LinkStatus;
use Wsmallnews\Cms\Enums\NavigationStatus;
use Wsmallnews\Cms\Enums\NavigationType as NavTypeEnum;
use Wsmallnews\Cms\Enums\NavigationTypeStatus;
use Wsmallnews\Cms\Enums\PostStatus;
use Wsmallnews\Cms\Models\Link;
use Wsmallnews\Cms\Models\Navigation;
use Wsmallnews\Cms\Models\NavigationType;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Category\Models\Category;
use Wsmallnews\Category\Models\CategoryType;
use Wsmallnews\Category\Enums\CategoryStatus;
use Wsmallnews\Category\Enums\CategoryTypeStatus;
use Wsmallnews\Comment\Models\Comment;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Enums\ContentType;
use Wsmallnews\Support\Enums\PageStatus;
use Wsmallnews\Support\Models\Composition;
use Wsmallnews\Support\Models\Page;

// ---------- 用户 ----------
$admin = User::firstOrCreate(['email' => 'admin@admin.com'], ['name' => 'admin', 'password' => 'password']);
$xiaoxin = User::firstOrCreate(['email' => '1371606921@qq.com'], ['name' => '小新', 'password' => 'password']);

// ---------- 导航类型 ----------
$navType = NavigationType::create([
    'name' => 'Cms', 'level' => 0, 'status' => NavigationTypeStatus::Normal,
    'scope_type' => 'sn-cms', 'scope_id' => 0,
]);

// ---------- 分类类型 + 文章分类 ----------
$catType = CategoryType::create([
    'name' => 'Post', 'level' => 2, 'status' => CategoryTypeStatus::Normal,
    'scope_type' => 'sn-cms', 'scope_id' => 0,
]);
$catXingpin = Category::create(['name' => '星品新闻', 'type_id' => $catType->id, 'status' => CategoryStatus::Normal, 'scope_type' => 'sn-cms', 'scope_id' => 0]);
$catCompany = Category::create(['name' => '公司新闻', 'type_id' => $catType->id, 'status' => CategoryStatus::Normal, 'scope_type' => 'sn-cms', 'scope_id' => 0]);
$catRoute = Category::create(['name' => '路由咨询', 'type_id' => $catType->id, 'status' => CategoryStatus::Normal, 'scope_type' => 'sn-cms', 'scope_id' => 0]);

// ---------- 文章 ----------
$mkPost = function (string $title, string $slug, string $desc, array $flags, array $cats, string $date, string $contentHtml) use ($admin) {
    $post = Post::create([
        'title' => $title, 'slug' => $slug, 'description' => $desc,
        'flags' => $flags, 'status' => PostStatus::Published,
        'published_at' => $date, 'publisher_type' => 'user', 'publisher_id' => $admin->id,
        'scope_type' => 'sn-cms', 'scope_id' => 0,
        'counter' => ['view_num' => random_int(5, 80)],
    ]);
    $post->categories()->sync($cats);
    $post->content()->create(['content_type' => ContentType::Richtext, 'content' => $contentHtml]);

    return $post;
};

$post4 = $mkPost('太空莲4号', 'woshi-aaaa4', '阿斯蒂芬', ['hot', 'new', 'recommend'], [$catCompany->id], '2026-09-16 10:00:00', '<p>asdfasdf</p>');
$post2 = $mkPost('太空莲2号', 'woshi-aaaa2', '阿斯蒂芬', ['new', 'recommend'], [$catCompany->id], '2026-06-11 03:02:28', '<p>阿斯蒂芬</p><p>发大水大是大非</p>');
$mkPost('测试定时发布', 'ce-shi-ding-shi-fa-buasdf', 'dasdf', ['hot'], [$catXingpin->id], '2026-08-11 09:00:00', '<p>测试定时发布内容 A</p>');
$mkPost('测试定时发布', 'ce-shi-ding-shi-fa-bu', 'dasdf', [], [$catXingpin->id], '2026-08-11 09:30:00', '<p>测试定时发布内容 B</p>');

// ---------- 评论（挂太空莲2号）----------
$mkComment = function (?Comment $parent, string $content, string $date) use ($xiaoxin, $post2) {
    $comment = Comment::create([
        'parent_id' => $parent?->id ?: 0,
        'commentable_type' => 'sn_post', 'commentable_id' => $post2->id,
        'commenter_type' => 'user', 'commenter_id' => $xiaoxin->id,
        'commenter_name' => $xiaoxin->name,
        'content' => $content, 'content_type' => 'textarea', 'status' => 'normal',
        'scope_type' => 'sn-comment', 'scope_id' => 0,
        'counter' => ['comment_num' => 0, 'like_num' => 0],
        'created_at' => $date, 'updated_at' => $date,
    ]);
    if ($parent) {
        $parent->incrementJson('counter->comment_num');
    }

    return $comment;
};
$c1 = $mkComment(null, '来一条新评论啊', '2026-06-22 10:41:53');
$mkComment($c1, '再来一条新评论', '2026-06-22 10:30:08');
$mkComment($c1, '来条新评论', '2026-06-22 10:29:52');
$c2 = $mkComment(null, 'biubiubiu', '2026-06-12 10:34:46');
$mkComment($c2, '阿斯蒂芬', '2026-06-11 06:48:14');

// ---------- 编排 ----------
$homeComp = Composition::create([
    'title' => '演示首页编排', 'status' => CompositionStatus::Published,
    'scope_type' => 'sn-cms', 'scope_id' => 0, 'order_column' => 1,
    'components' => [
        [
            'layout' => '2-1',
            'left' => [[
                'type' => 'post-detail', 'label' => '焦点文章', 'description' => '同行右侧自动关联推荐',
                'show_header' => true, 'contained' => false, 'extras' => ['id' => $post2->id],
            ]],
            'right' => [[
                'type' => 'related-posts', 'label' => '相关推荐', 'description' => '自动关联左边的文章',
                'show_header' => true, 'contained' => true, 'extras' => [],
            ]],
        ],
    ],
]);

$topicComp = Composition::create([
    'title' => '详情推荐', 'status' => CompositionStatus::Published,
    'scope_type' => 'sn-cms', 'scope_id' => 0, 'order_column' => 2,
    'components' => [
        [
            'layout' => '2-1',
            'left' => [[
                'type' => 'posts', 'label' => '图文列表', 'description' => '阿拉山口都放假啦水电费',
                'show_header' => true, 'contained' => true, 'extras' => [],
            ]],
            'right' => [[
                'type' => 'related-posts', 'label' => '相关文章推荐', 'description' => '自动关联左边的文章',
                'show_header' => true, 'contained' => true, 'extras' => [],
            ]],
        ],
    ],
]);

// purpose 侧栏：文章详情页右栏（C2 演示；position=left 可切换左侧）
Composition::create([
    'title' => '详情页侧栏', 'status' => CompositionStatus::Published,
    'scope_type' => 'sn-cms', 'scope_id' => 0, 'purpose' => 'post-sidebar', 'order_column' => 10,
    'options' => ['position' => 'right'],
    'components' => [
        ['layout' => 'full', 'left' => [['type' => 'related-posts', 'label' => '相关推荐', 'description' => '自动关联当前文章', 'show_header' => true, 'contained' => true, 'extras' => []]], 'right' => []],
        ['layout' => 'full', 'left' => [['type' => 'related-posts', 'label' => '热门文章', 'description' => null, 'show_header' => true, 'contained' => true, 'extras' => ['limit' => 3]]], 'right' => []],
    ],
]);

// ---------- 页面 ----------
$home = Page::create(['title' => '演示首页', 'slug' => 'home', 'status' => PageStatus::Published, 'scope_type' => 'sn-cms', 'scope_id' => 0, 'composition_id' => $homeComp->id]);
$topic = Page::create(['title' => '专题推荐', 'slug' => 'topic', 'status' => PageStatus::Published, 'scope_type' => 'sn-cms', 'scope_id' => 0, 'composition_id' => $topicComp->id]);
$about = Page::create(['title' => '关于我们', 'slug' => 'about', 'status' => PageStatus::Published, 'scope_type' => 'sn-cms', 'scope_id' => 0]);
$about->content()->create(['content_type' => ContentType::Richtext, 'content' => '<h2>关于我们</h2><p>这是页面自有内容通道的演示：一段富文本正文，不经过内容编排。</p>']);
$contact = Page::create(['title' => '联系我们', 'slug' => 'contact', 'status' => PageStatus::Published, 'scope_type' => 'sn-cms', 'scope_id' => 0]);
$contact->content()->create(['content_type' => ContentType::Markdown, 'content' => "## 联系我们\n\n这是 **Markdown** 内容通道的演示。\n\n- 邮箱：demo@example.com\n- 电话：400-000-0000"]);

// ---------- 导航树 ----------
$mkNav = fn (array $attrs, ?Navigation $parent = null) => Navigation::create(array_merge([
    'type_id' => $navType->id, 'status' => NavigationStatus::Normal,
    'scope_type' => 'sn-cms', 'scope_id' => 0,
], $attrs, ['parent_id' => $parent?->id]));

$navHome = $mkNav(['name' => '首页', 'type' => NavTypeEnum::Page, 'page_id' => $home->id, 'options' => ['is_home' => true]]);
$news = $mkNav(['name' => '新闻中心', 'type' => NavTypeEnum::Route, 'options' => ['route' => 'sn-cms.posts']]);
$mkNav(['name' => '全部文章', 'type' => NavTypeEnum::Route, 'options' => ['route' => 'sn-cms.posts']], $news);
$mkNav(['name' => '站内搜索', 'type' => NavTypeEnum::Route, 'options' => ['route' => 'sn-cms.search']], $news);
$aboutNav = $mkNav(['name' => '关于我们', 'type' => NavTypeEnum::Page, 'page_id' => $about->id]);
$mkNav(['name' => '公司简介', 'type' => NavTypeEnum::Page, 'page_id' => $about->id], $aboutNav);
$topicNav = $mkNav(['name' => '专题推荐', 'type' => NavTypeEnum::Page, 'page_id' => $topic->id], $aboutNav);
$mkNav(['name' => '子分类 A', 'type' => NavTypeEnum::Url, 'options' => ['url' => 'https://laravel.com']], $topicNav);
$subB = $mkNav(['name' => '子分类 B', 'type' => NavTypeEnum::Url, 'options' => ['url' => 'https://laravel.com/docs']], $topicNav);
$mkNav(['name' => '来个四级', 'type' => NavTypeEnum::Url, 'options' => ['url' => 'https://laravel.com/docs'], 'description' => '阿斯蒂芬'], $subB);
$mkNav(['name' => '联系我们', 'type' => NavTypeEnum::Page, 'page_id' => $contact->id], $aboutNav);
$more = $mkNav(['name' => '更多', 'type' => NavTypeEnum::Child]);
$mkNav(['name' => '专题页直达', 'type' => NavTypeEnum::Page, 'page_id' => $topic->id], $more);
$mkNav(['name' => 'Laravel 官网', 'type' => NavTypeEnum::Url, 'options' => ['url' => 'https://laravel.com']]);

// ---------- 友情链接 ----------
foreach ([
    ['新华网', 'https://www.xinhuanet.com'],
    ['人民网', 'http://www.people.com.cn'],
    ['澎湃新闻', 'https://www.thepaper.cn'],
    ['虎嗅网', 'https://www.huxiu.com'],
    ['36氪', 'https://www.36kr.com'],
    ['星品', 'https://shopro.top'],
    ['阿斯蒂芬', 'https://m.v3.shopro.top'],
] as [$name, $url]) {
    Link::create(['name' => $name, 'url' => $url, 'status' => LinkStatus::Normal, 'scope_type' => 'sn-cms', 'scope_id' => 0]);
}

echo "演示数据重建完成：pages=" . Page::count() . " compositions=" . Composition::count()
    . " navigations=" . Navigation::count() . " posts=" . Post::count()
    . " comments=" . Comment::count() . " links=" . Link::count() . PHP_EOL;

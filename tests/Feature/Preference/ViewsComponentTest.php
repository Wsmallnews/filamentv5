<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Preference\Livewire\Components\Views;
use Wsmallnews\Preference\Models\Preference;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

afterEach(function () {
    // 清理 panel 语境模拟，避免影响其他用例
    Filament::setCurrentPanel(null);
});

function createViewedPost(User $admin, string $title = '测试文章'): Post
{
    return Post::create([
        'publisher_type' => $admin->getMorphClass(),
        'publisher_id' => $admin->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => $title,
        'slug' => 'viewed-post-'.Str::random(6),
        'status' => 'published',
    ]);
}

function createViewPreference(User $user, Post $post): Preference
{
    return Preference::create([
        'type' => 'view',
        'preferencer_type' => $user->getMorphClass(),
        'preferencer_id' => $user->id,
        'preferenceable_type' => 'sn_post',
        'preferenceable_id' => $post->id,
        // 组件默认 scope（scopeType = default / scopeId = 0），render() 重新查询时按此过滤
        'scope_type' => 'default',
        'scope_id' => 0,
    ]);
}

it('views 列表行保持 flex 布局，窄屏不换行不溢出（回归）', function () {
    Filament::setCurrentPanel('admin');

    $admin = User::factory()->create();
    $post = createViewedPost($admin);
    $preference = createViewPreference($admin, $post);

    $html = (string) livewire(Views::class, [
        'preferencer' => $admin,
        'views' => collect([$preference]),
        'listType' => 'preferencer',
    ])->html();

    // 行类必须包含 flex（丢掉 flex 会导致复选框/操作按钮换行、长内容溢出容器）
    expect($html)->toContain('sn-list-row flex items-center gap-3');
});

it('views 组件正常渲染浏览记录条目', function () {
    Filament::setCurrentPanel('admin');

    $admin = User::factory()->create();
    $post = createViewedPost($admin);
    $preference = createViewPreference($admin, $post);

    $html = (string) livewire(Views::class, [
        'preferencer' => $admin,
        'views' => collect([$preference]),
        'listType' => 'preferencer',
    ])->html();

    expect($html)->toContain($post->title)
        ->and($html)->toContain('sn-preference-list');
});

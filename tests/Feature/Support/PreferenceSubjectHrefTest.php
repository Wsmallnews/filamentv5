<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Preference\Models\Preference;

uses(RefreshDatabase::class);

afterEach(function () {
    // 清理 panel 语境模拟，避免影响其他用例
    Filament::setCurrentPanel(null);
});

function createTestPost(User $admin, string $title = '测试文章'): Post
{
    return Post::create([
        'publisher_type' => $admin->getMorphClass(),
        'publisher_id' => $admin->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => $title,
        'slug' => 'test-post-'.Str::random(6),
        'status' => 'published',
    ]);
}

function createTestPreference(User $user, ?Post $post): Preference
{
    return Preference::create([
        'type' => 'like',
        'preferencer_type' => $user->getMorphClass(),
        'preferencer_id' => $user->id,
        'preferenceable_type' => 'sn_post',
        'preferenceable_id' => $post?->id ?? 999999,
    ]);
}

it('preferenceable 组件直传 href 时渲染为 a 标签', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    $html = (string) $this->blade(
        '<x-sn-preference::preferenceable :preference="$preference" :preferenceable="$preferenceable" :has-link="true" href="/posts/1" />',
        ['preference' => $preference, 'preferenceable' => $post],
    );

    expect($html)->toContain('<a')
        ->and($html)->toContain('href="/posts/1"');
});

it('preferenceable 组件的 href 闭包会收到模型实例', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    $html = (string) $this->blade(
        '<x-sn-preference::preferenceable :preference="$preference" :preferenceable="$preferenceable" :has-link="true" :href="$href" />',
        ['preference' => $preference, 'preferenceable' => $post, 'href' => fn ($record) => '/posts/'.$record->id],
    );

    expect($html)->toContain('href="/posts/'.$post->id.'"');
});

it('preferenceable 组件未传 href 时渲染为 div 并分发点击事件', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    $html = (string) $this->blade(
        '<x-sn-preference::preferenceable :preference="$preference" :preferenceable="$preferenceable" :has-link="true" />',
        ['preference' => $preference, 'preferenceable' => $post],
    );

    expect($html)->not->toContain('<a')
        ->and($html)->toContain('sn-preference-preferenceable-click');
});

it('preference 组件的 preferenceable 分支同样支持 href 直传', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    $html = (string) $this->blade(
        '<x-sn-preference::preference :preference="$preference" :has-link="true" :href="$href" />',
        ['preference' => $preference, 'href' => fn ($record) => '/posts/'.$record->id],
    );

    expect($html)->toContain('href="/posts/'.$post->id.'"');
});

it('preference 组件的 href 闭包在 preferenceable 缺失时收到 preferencer', function () {
    $admin = User::factory()->create();
    // preferenceable 指向不存在的记录，闭包回退接收 preferencer
    $preference = createTestPreference($admin, null);

    $html = (string) $this->blade(
        '<x-sn-preference::preference :preference="$preference" :has-link="true" :href="$href" />',
        ['preference' => $preference, 'href' => fn ($record) => '/users/'.$record->id],
    );

    expect($html)->toContain('href="/users/'.$admin->id.'"');
});

it('preference 组件未传 href 时渲染为 div 并分发点击事件', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    $html = (string) $this->blade(
        '<x-sn-preference::preference :preference="$preference" :has-link="true" />',
        ['preference' => $preference],
    );

    expect($html)->not->toContain('<a')
        ->and($html)->toContain('sn-preference-preference-click');
});

it('preferencer 组件直传 href 时渲染为 a 标签', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    $html = (string) $this->blade(
        '<x-sn-preference::preferencer :preference="$preference" :preferencer="$preferencer" :has-link="true" href="/users/1" />',
        ['preference' => $preference, 'preferencer' => $admin],
    );

    expect($html)->toContain('<a')
        ->and($html)->toContain('href="/users/1"');
});

it('preferencer 组件未传 href 时渲染为 div 并分发点击事件', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    $html = (string) $this->blade(
        '<x-sn-preference::preferencer :preference="$preference" :preferencer="$preferencer" :has-link="true" />',
        ['preference' => $preference, 'preferencer' => $admin],
    );

    expect($html)->not->toContain('<a')
        ->and($html)->toContain('sn-preference-preferencer-click');
});

it('panel 语境未传 href 时兜底后台资源链接', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    Filament::setCurrentPanel('admin');

    $html = (string) $this->blade(
        '<x-sn-preference::preferenceable :preference="$preference" :preferenceable="$preferenceable" :has-link="true" />',
        ['preference' => $preference, 'preferenceable' => $post],
    );

    expect($html)->toContain('<a')
        ->and($html)->toContain('/admin/posts');
});

it('panel 语境传入 href 时优先使用调用方链接', function () {
    $admin = User::factory()->create();
    $post = createTestPost($admin);
    $preference = createTestPreference($admin, $post);

    Filament::setCurrentPanel('admin');

    $html = (string) $this->blade(
        '<x-sn-preference::preferenceable :preference="$preference" :preferenceable="$preferenceable" :has-link="true" href="/custom/link" />',
        ['preference' => $preference, 'preferenceable' => $post],
    );

    expect($html)->toContain('href="/custom/link"')
        ->and($html)->not->toContain('/admin/posts');
});

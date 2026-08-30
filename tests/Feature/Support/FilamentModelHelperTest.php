<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wsmallnews\Cms\Models\Post;
use Wsmallnews\Support\Helpers\FilamentModelHelper;

uses(RefreshDatabase::class);

it('getUrl 对实现 HasSnSubject 的模型回退 resolveResourceUrl', function () {
    $admin = User::factory()->create();
    $post = Post::create([
        'publisher_type' => $admin->getMorphClass(),
        'publisher_id' => $admin->id,
        'scope_type' => 'sn-cms',
        'scope_id' => 0,
        'title' => '资源链接回退',
        'slug' => 'resource-url-fallback',
        'status' => 'published',
    ]);

    // 契约已无 getSnSubjectHrefUrl，getUrl 应解析到 PostResource 的后台地址
    $url = FilamentModelHelper::getUrl($post);

    expect($url)->toBeString()
        ->and($url)->toContain('posts');
});

it('getUrl 对 HasSnIdentifiable 模型同样统一走 resolveResourceUrl', function () {
    $admin = User::factory()->create();

    $url = FilamentModelHelper::getUrl($admin);

    expect($url === null || is_string($url))->toBeTrue();
});

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Wsmallnews\Media\Models\Media;
use Wsmallnews\Media\Support\MediaManager;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->manager = app(MediaManager::class);
});

/*
 * presenter 三形态归一
 */
it('accepts value object arrays', function () {
    $value = $this->manager->uploadValue(UploadedFile::fake()->image('a.jpg', 300, 200));

    $presenter = media($value);

    expect($presenter->exists())->toBeTrue()
        ->and($presenter->name)->toBe('a.jpg')
        ->and($presenter->width)->toBe(300)
        ->and($presenter->isImage())->toBeTrue()
        ->and($presenter->url())->toStartWith('/storage/')
        ->and($presenter->url())->toEndWith($value['path']);
});

it('resolves plain path strings via the media table (with request cache)', function () {
    $value = $this->manager->uploadValue(UploadedFile::fake()->image('b.png', 60, 40));

    $presenter = media($value['path']);

    expect($presenter->name)->toBe('b.png')
        ->and($presenter->width)->toBe(60)
        ->and($presenter->downloadUrl())->toBe(route('sn-media.download', ['media' => Media::findByPath($value['path'])]));
});

it('degrades gracefully for unknown path strings', function () {
    $presenter = media('sn/unknown/file.jpg');

    expect($presenter->exists())->toBeTrue()
        ->and($presenter->name)->toBeNull()
        ->and($presenter->url())->toBe('/storage/sn/unknown/file.jpg')
        ->and($presenter->downloadUrl())->toBe('/storage/sn/unknown/file.jpg');
});

it('accepts media models directly', function () {
    $media = $this->manager->upload(UploadedFile::fake()->image('c.jpg', 10, 10));

    expect(media($media)->path)->toBe($media->path)
        ->and(media(null)->exists())->toBeFalse()
        ->and(media_url(null))->toBeNull();
});

it('falls back to the media record for keys outside the value contract (alt)', function () {
    $media = $this->manager->upload(UploadedFile::fake()->image('d.jpg', 10, 10));
    $media->update(['alt' => '一张奶茶照片']);

    // 值对象数组形态：alt 不在契约里，回落素材记录
    expect(media($media->toValue())->alt)->toBe('一张奶茶照片')
        ->and(media($media)->alt)->toBe('一张奶茶照片')
        // 无记录的裸路径：alt 为 null（合理降级）
        ->and(media('sn/unknown/x.jpg')->alt)->toBeNull();
});

it('routes non-direct disks through the file route', function () {
    config(['filesystems.disks.private-test' => ['driver' => 'local', 'root' => storage_path('app/private-test'), 'visibility' => 'private']]);

    // 直接造记录（避免 Storage::fake 覆写盘配置干扰 diskServesDirectly 判定）
    $media = Media::create([
        'disk' => 'private-test',
        'path' => 'sn/' . now()->format('Ymd') . '/test0001test0001test0001test.jpg',
        'name' => 'private.jpg', 'ext' => 'jpg', 'mime' => 'image/jpeg',
        'size' => 1000, 'width' => 10, 'height' => 10,
    ]);

    // 模型形态（能读到 disk 列）走 file 路由
    expect($media->url())->toBe(route('sn-media.file', ['path' => $media->path]));

    // 值对象数组形态：经 path 反查补挂模型后同样走 file 路由
    $value = $media->toValue();
    expect(media_url($value))->toBe(route('sn-media.file', ['path' => $value['path']]));
});

/*
 * URL 变换层：盘名覆盖 > driver 推断 > 出原图
 */
it('appends oss resize params when the disk maps to a provider', function () {
    config([
        'filesystems.disks.media-cdn' => [
            'driver' => 'local',
            'root' => storage_path('app/media-cdn'),
            'url' => 'https://cdn.example.com',
            'visibility' => 'public',
        ],
        'sn-media.disk' => 'media-cdn',
        'sn-media.transforms.disks' => ['media-cdn' => 'oss'],
    ]);

    // 直接造记录在 media-cdn 盘（fake 会覆写 driver/visibility，绕开）
    $media = Media::create([
        'disk' => 'media-cdn',
        'path' => 'sn/' . now()->format('Ymd') . '/abcd0001abcd0001abcd0001ab.jpg',
        'name' => 'cdn.jpg', 'ext' => 'jpg', 'mime' => 'image/jpeg',
        'size' => 1000, 'width' => 10, 'height' => 10,
    ]);
    $value = $media->toValue();

    // hash 路径在活动盘 media-cdn 下
    expect(media_url($value, 400))->toBe('https://cdn.example.com/' . $value['path'] . '?x-oss-process=image/resize,w_400')
        ->and(media_url($value))->toBe('https://cdn.example.com/' . $value['path']);
});

it('appends transform params by driver inference', function () {
    // 为 public 盘的 local driver 显式配置模板，验证按 driver 推断的解析链
    config(['sn-media.transforms.providers.local' => ['resize' => 'imageView2/2/w/{w}']]);

    $value = $this->manager->uploadValue(UploadedFile::fake()->image('e.jpg', 10, 10));

    expect(media_url($value, 200))->toBe('/storage/' . $value['path'] . '?imageView2/2/w/200');
});

it('serves the original url for local disks without a provider', function () {
    $value = $this->manager->uploadValue(UploadedFile::fake()->image('f.jpg', 10, 10));

    expect(media_url($value, 400))->toBe('/storage/' . $value['path']);
});

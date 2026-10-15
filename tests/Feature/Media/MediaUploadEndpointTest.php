<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Wsmallnews\Media\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

/*
 * FilePicker 直传端点：multipart → 统一管道 → 值对象 + 预览 URL
 */
it('uploads via the endpoint and returns a value object', function () {
    $file = UploadedFile::fake()->image('contract-scan.png', 300, 200);

    $response = $this->post(route('sn-media.upload'), [
        'file' => $file,
    ]);

    $response->assertOk();

    $json = $response->json();
    expect($json['value']['path'])->toStartWith('sn/' . now()->format('Ymd') . '/')
        ->and($json['value']['name'])->toBe('contract-scan.png')
        ->and($json['value']['width'])->toBe(300)
        ->and($json['url'])->toBeString();

    Storage::disk('public')->assertExists($json['value']['path']);
});

it('uploads to the specified disk via the endpoint', function () {
    $file = UploadedFile::fake()->image('cert.png', 60, 60);

    $response = $this->post(route('sn-media.upload'), [
        'file' => $file,
        'disk' => 'local',
    ]);

    $response->assertOk();

    $path = $response->json('value.path');
    Storage::disk('local')->assertExists($path);
    expect(Media::findByPath($path)->disk)->toBe('local');
});

it('rejects non-whitelisted files with 422', function () {
    $file = UploadedFile::fake()->createWithContent('shell.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    $this->post(route('sn-media.upload'), ['file' => $file, 'directory' => 'sn/test'])
        ->assertStatus(422);
});

/*
 * 私有盘 inline 读路由
 */
it('streams private disk files inline via the file route', function () {
    config(['filesystems.disks.private-test' => ['driver' => 'local', 'root' => storage_path('app/private-test')]]);
    Storage::fake('private-test');

    $file = UploadedFile::fake()->image('secret.jpg', 40, 40);
    $path = 'sn/private/' . now()->format('Ymd') . '/abc.jpg';
    Storage::disk('private-test')->put($path, file_get_contents($file->getRealPath()));

    $media = Media::create([
        'disk' => 'private-test', 'path' => $path, 'name' => 'secret.jpg',
        'ext' => 'jpg', 'mime' => 'image/jpeg', 'size' => filesize($file->getRealPath()),
        'width' => 40, 'height' => 40,
    ]);

    $this->get(route('sn-media.file', ['path' => $path]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    $this->get(route('sn-media.file', ['path' => 'sn/unknown.jpg']))->assertNotFound();
});

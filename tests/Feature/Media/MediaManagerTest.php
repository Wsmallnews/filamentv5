<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Wsmallnews\Media\Exceptions\MediaException;
use Wsmallnews\Media\Models\Media;
use Wsmallnews\Media\Models\MediaDirectory;
use Wsmallnews\Media\Support\MediaManager;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->manager = app(MediaManager::class);
});

/*
 * 上传管道：内容哈希路径 + 当天秒传
 */
it('uploads an image with hash path and records metadata', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $value = $this->manager->uploadValue($file);

    $today = now()->format('Ymd');
    expect($value['path'])->toStartWith('sn/' . $today . '/')
        ->and($value['path'])->toEndWith('.jpg')
        // 内容寻址：文件名是 md5 内容哈希（32 位十六进制）
        ->and(preg_match('/\/([a-f0-9]{32})\.jpg$/', $value['path']))->toBe(1)
        ->and($value['name'])->toBe('photo.jpg')
        ->and($value['mime'])->toBe('image/jpeg')
        ->and($value['width'])->toBe(800)
        ->and($value['height'])->toBe(600)
        ->and($value['size'])->toBeGreaterThan(0);

    Storage::disk('public')->assertExists($value['path']);

    $media = Media::findByPath($value['path']);
    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('public')
        ->and($media->directory_id)->toBeNull()
        ->and($media->toValue())->toBe($value);
});

it('reuses the same record for identical content on the same day', function () {
    $file = UploadedFile::fake()->image('same.jpg', 100, 100);
    $file2 = UploadedFile::fake()->image('renamed-different-name.jpg', 100, 100);

    // 两个不同文件名，但 Laravel fake()->image 同参数内容不同——需要用同内容
    // 用 createWithContent 保证内容一致
    // 用 fake()->image() 的 GD 输出保证内容相同（不同文件名同二进制）
    ob_start();
    $img = imagecreatetruecolor(80, 80);
    imagejpeg($img, null, 80);
    $binary = ob_get_clean();
    $a = UploadedFile::fake()->createWithContent('a.jpg', $binary);
    $b = UploadedFile::fake()->createWithContent('b.jpg', $binary);

    $one = $this->manager->upload($a);
    $two = $this->manager->upload($b);

    // 当天同内容 = 同 path = 秒传复用（一条记录）
    expect($one->path)->toBe($two->path)
        ->and($one->id)->toBe($two->id)
        ->and(Media::count())->toBe(1);
});

it('creates separate records for same content on different days', function () {
    ob_start();
    $img = imagecreatetruecolor(60, 60);
    imagejpeg($img, null, 80);
    $binary = ob_get_clean();
    $a = UploadedFile::fake()->createWithContent('a.jpg', $binary);
    $b = UploadedFile::fake()->createWithContent('b.jpg', $binary);

    $one = $this->manager->upload($a);

    // 时间旅行到明天
    $this->travel(1)->days();
    $two = $this->manager->upload($b);

    expect($one->path)->not->toBe($two->path)
        ->and(Media::count())->toBe(2);
});

it('records null dimensions for non-image files', function () {
    $file = UploadedFile::fake()->createWithContent('report.pdf', '%PDF-1.4 fake pdf content');

    $media = $this->manager->upload($file);

    expect($media->width)->toBeNull()
        ->and($media->height)->toBeNull()
        ->and($media->mime)->toBe('application/pdf')
        ->and($media->ext)->toBe('pdf')
        ->and($media->isImage())->toBeFalse();
});

it('categorizes uploads into the specified directory', function () {
    $dir = MediaDirectory::create(['name' => '商城', 'team_id' => null]);

    $media = $this->manager->upload(UploadedFile::fake()->image('cat.jpg', 20, 20), directory: $dir->id);

    expect($media->directory_id)->toBe($dir->id);
});

it('rejects files exceeding the size limit', function () {
    config(['sn-media.max_size' => 1]); // 1 KB

    $file = UploadedFile::fake()->createWithContent('big.jpg', str_repeat('x', 500 * 1024));

    $this->manager->upload($file);
})->throws(MediaException::class, 'maximum allowed size');

it('rejects disallowed extensions when whitelist configured', function () {
    config(['sn-media.mimes' => ['jpg', 'png']]);

    $file = UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4 content');

    $this->manager->upload($file);
})->throws(MediaException::class, 'not allowed');

it('rejects files whose sniffed mime contradicts the extension', function () {
    $file = UploadedFile::fake()->createWithContent('shell.jpg', '<?php echo "pwn";');

    $this->manager->upload($file);
})->throws(MediaException::class);

it('uploads to the specified disk', function () {
    config(['filesystems.disks.private-test' => ['driver' => 'local', 'root' => storage_path('app/private-test')]]);
    Storage::fake('private-test');

    $media = $this->manager->upload(UploadedFile::fake()->image('secret.jpg', 20, 20), disk: 'private-test');

    expect($media->disk)->toBe('private-test');
    Storage::disk('private-test')->assertExists($media->path);
    Storage::disk('public')->assertMissing($media->path);
});

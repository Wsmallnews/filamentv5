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
 * 下载：以原始文件名（name 列）返回
 */
it('downloads a file with its original name on local disks', function () {
    $media = $this->manager->upload(UploadedFile::fake()->createWithContent('research-report.pdf', '%PDF-1.4 fake pdf content'));

    $response = $this->get(route('sn-media.download', ['media' => $media]));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('Content-Disposition'))->toContain('research-report.pdf');
});

it('returns 404 for missing model', function () {
    $this->get(route('sn-media.download', ['media' => 999]))->assertNotFound();
});

it('keeps the physical file after deleting the record', function () {
    $media = $this->manager->upload(UploadedFile::fake()->image('keep.jpg', 20, 20));
    $path = $media->path;

    $media->delete();

    expect(Media::findByPath($path))->toBeNull();
    Storage::disk('public')->assertExists($path);
});

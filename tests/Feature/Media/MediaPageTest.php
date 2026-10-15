<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Wsmallnews\Media\Filament\Pages\MediaPage;
use Wsmallnews\Media\Livewire\Components\MediaDirectories;
use Wsmallnews\Media\Models\Media;
use Wsmallnews\Media\Models\MediaDirectory;
use Wsmallnews\Media\Support\MediaManager;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->manager = app(MediaManager::class);
    $this->user = User::factory()->create();
});

/**
 * 直接造记录（不经上传管道，测试页查询逻辑）
 */
function makePageMedia(array $attributes = []): Media
{
    return Media::create(array_merge([
        'disk' => 'public',
        'path' => 'sn/' . now()->format('Ymd') . '/' . Str::random(32) . '.jpg',
        'name' => '素材-' . Str::random(4) . '.jpg',
        'ext' => 'jpg',
        'mime' => 'image/jpeg',
        'size' => 102400,
        'width' => 600,
        'height' => 600,
    ], $attributes));
}

it('renders the media page with grid view by default', function () {
    makePageMedia(['name' => '主图.jpg']);

    livewire(MediaPage::class)
        ->assertSee('主图.jpg')
        ->assertSeeHtml('snMediaManager')
        ->assertSuccessful();
});

it('renders the details view when switched', function () {
    makePageMedia(['name' => '文档.pdf', 'mime' => 'application/pdf', 'ext' => 'pdf', 'width' => null, 'height' => null]);

    livewire(MediaPage::class)
        ->call('setViewMode', 'list')
        ->assertSet('viewMode', 'list')
        ->assertSee('文档.pdf')
        ->assertSee('PDF');
});

it('filters by media type (combined with directory, not clearing it)', function () {
    $dir = MediaDirectory::create(['name' => '商城', 'team_id' => null]);
    makePageMedia(['name' => '图片甲.jpg', 'directory_id' => $dir->id]);
    makePageMedia(['name' => '文档乙.pdf', 'mime' => 'application/pdf', 'ext' => 'pdf', 'width' => null, 'height' => null]);
    makePageMedia(['name' => '图片丙.jpg']);

    $component = livewire(MediaPage::class)
        ->call('filterByType', 'pdf')
        ->assertSet('libraryType', 'pdf')
        ->assertSee('文档乙.pdf')
        ->assertDontSee('图片甲.jpg');

    // 组合：类型 pdf + 目录（商城）→ 无 PDF 在商城 → 空
    $component->call('filterByDir', $dir->id)
        ->assertSet('libraryDir', $dir->id)
        ->assertSet('libraryType', 'pdf') // 类型未被目录清空
        ->assertDontSee('文档乙.pdf')
        ->assertDontSee('图片甲.jpg');
});

it('filters by directory (including descendants)', function () {
    $shop = MediaDirectory::create(['name' => '商城', 'team_id' => null]);
    $spec = MediaDirectory::create(['name' => '规格图', 'parent_id' => $shop->id, 'team_id' => null]);
    makePageMedia(['name' => '商品图.jpg', 'directory_id' => $spec->id]);
    makePageMedia(['name' => '横幅图.jpg']);

    livewire(MediaPage::class)
        ->call('filterByDir', $shop->id)
        ->assertSee('商品图.jpg')
        ->assertDontSee('横幅图.jpg');
});

it('filters uncategorized media', function () {
    $dir = MediaDirectory::create(['name' => '商城', 'team_id' => null]);
    makePageMedia(['name' => '已归类.jpg', 'directory_id' => $dir->id]);
    makePageMedia(['name' => '未归类.jpg']);

    livewire(MediaPage::class)
        ->call('filterByDir', 0)
        ->assertSee('未归类.jpg')
        ->assertDontSee('已归类.jpg');
});

it('searches by name and path', function () {
    makePageMedia(['name' => '获奖证书.jpg']);

    livewire(MediaPage::class)
        ->set('librarySearch', '获奖')
        ->assertSee('获奖证书.jpg');

    livewire(MediaPage::class)
        ->set('librarySearch', '不存在的关键字')
        ->assertDontSee('获奖证书.jpg');
});

it('supports all sort options', function () {
    makePageMedia(['name' => 'B图.jpg', 'size' => 100]);
    makePageMedia(['name' => 'A图.jpg', 'size' => 500]);
    makePageMedia(['name' => 'C图.jpg', 'size' => 300]);

    $component = livewire(MediaPage::class);

    // name_asc：A 在前
    $component->set('sortBy', 'name_asc');
    expect($component->instance()->medias()->first()->name)->toBe('A图.jpg');

    // size_desc：500 在前
    $component->set('sortBy', 'size_desc');
    expect($component->instance()->medias()->first()->size)->toBe(500);

    // created_asc：最后创建的排最后
    $component->set('sortBy', 'created_asc');
    expect($component->instance()->medias()->last()->name)->toBe('C图.jpg');
});

it('deletes only the record and keeps the physical file', function () {
    $media = $this->manager->upload(UploadedFile::fake()->image('delete-me.jpg', 30, 30));

    livewire(MediaPage::class)
        ->call('delete', [$media->id]);

    expect(Media::find($media->id))->toBeNull();
    Storage::disk('public')->assertExists($media->path);
});

it('uploads files via the page (hash path + directory categorization)', function () {
    $dir = MediaDirectory::create(['name' => '商城', 'team_id' => null]);
    $file = UploadedFile::fake()->image('via-page.png', 120, 90);

    $component = livewire(MediaPage::class);
    $component->set('libraryDir', $dir->id);
    $component->set('uploads', [$file]);

    $media = Media::query()->where('name', 'via-page.png')->first();
    expect($media)->not->toBeNull()
        ->and($media->directory_id)->toBe($dir->id)
        ->and($media->path)->toStartWith('sn/' . now()->format('Ymd') . '/');
});

it('moves files to another directory (path unchanged)', function () {
    $dirA = MediaDirectory::create(['name' => 'A目录', 'team_id' => null]);
    $dirB = MediaDirectory::create(['name' => 'B目录', 'team_id' => null]);
    $media = makePageMedia(['directory_id' => $dirA->id]);
    $originalPath = $media->path;

    livewire(MediaPage::class)
        ->call('move', [$media->id], $dirB->id);

    $media->refresh();
    expect($media->directory_id)->toBe($dirB->id)
        ->and($media->path)->toBe($originalPath);
});

it('creates a directory via the sidebar tree component (depth limit enforced)', function () {
    $tree = livewire(MediaDirectories::class);

    // 根部创建 → 一级 → 二级 → 三级
    $tree->mountAction('create')
        ->setActionData(['name' => '一级目录'])
        ->callMountedAction();
    expect(MediaDirectory::where('name', '一级目录')->exists())->toBeTrue();

    $level1 = MediaDirectory::where('name', '一级目录')->first();
    $tree->mountAction('createChild', ['parentId' => $level1->id])
        ->setActionData(['name' => '二级目录'])
        ->callMountedAction();
    $level2 = MediaDirectory::where('name', '二级目录')->first();
    expect($level2)->not->toBeNull();

    $tree->mountAction('createChild', ['parentId' => $level2->id])
        ->setActionData(['name' => '三级目录'])
        ->callMountedAction();
    $level3 = MediaDirectory::where('name', '三级目录')->first();
    expect($level3)->not->toBeNull();

    // 四级超限（MAX_DEPTH = 3）：拒绝创建
    $tree->mountAction('createChild', ['parentId' => $level3->id])
        ->setActionData(['name' => '四级目录'])
        ->callMountedAction();
    expect(MediaDirectory::where('name', '四级目录')->exists())->toBeFalse();
});

it('deletes a directory via the tree with files falling back to uncategorized', function () {
    $dir = MediaDirectory::create(['name' => '待删目录', 'team_id' => null]);
    $media = makePageMedia(['directory_id' => $dir->id]);

    livewire(MediaDirectories::class)
        ->mountAction('delete', ['id' => $dir->id])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    expect(MediaDirectory::find($dir->id))->toBeNull()
        ->and($media->fresh()->directory_id)->toBeNull();
});

it('filters the library when the tree selection event fires', function () {
    $dir = MediaDirectory::create(['name' => '商城', 'team_id' => null]);
    makePageMedia(['name' => '已归类.jpg', 'directory_id' => $dir->id]);
    makePageMedia(['name' => '未归类.jpg']);

    // MediaPage 监听树组件的 leaf/node-click 事件（payload recordId）
    livewire(MediaPage::class)
        ->dispatch('sn-filament-nestedset-node-click', recordId: $dir->id)
        ->assertSet('libraryDir', $dir->id)
        ->assertSee('已归类.jpg')
        ->assertDontSee('未归类.jpg')
        ->dispatch('sn-filament-nestedset-leaf-click', recordId: 0)
        ->assertSet('libraryDir', 0)
        ->assertDontSee('已归类.jpg');
});

it('previews and saves meta (display name and alt)', function () {
    $media = makePageMedia(['name' => '原名.jpg']);

    $component = livewire(MediaPage::class)
        ->call('openPreview', $media->id)
        ->assertSet('metaName', '原名.jpg');

    $component
        ->set('metaName', '新显示名.jpg')
        ->set('metaAlt', '一段替代文本')
        ->call('saveMeta');

    expect($media->fresh()->name)->toBe('新显示名.jpg')
        ->and($media->fresh()->alt)->toBe('一段替代文本');
});

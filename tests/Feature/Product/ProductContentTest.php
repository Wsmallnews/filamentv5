<?php

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Wsmallnews\Product\Filament\Resources\Products\Pages\CreateProduct;
use Wsmallnews\Product\Filament\Resources\Products\Pages\EditProduct;
use Wsmallnews\Product\Models\Product;
use Wsmallnews\Support\Enums\ContentType;

uses(RefreshDatabase::class);

use function Pest\Livewire\livewire;

beforeEach(function () {
    $admin = User::factory()->create();

    $this->actingAs($admin, 'admin');

    Storage::fake();
});

it('ContentType 枚举包含纯图类型', function () {
    expect(ContentType::tryFrom('images'))->toBe(ContentType::Images)
        ->and(ContentType::Images->getLabel())->toBe('纯图')
        ->and(ContentType::Images->getColor())->toBe('info')
        ->and(ContentType::Images->getIcon())->toBe(Heroicon::OutlinedPhoto);
});

it('product 表单支持全部内容类型', function () {
    $html = livewire(CreateProduct::class)->html();

    // product 配置 types => null，提供全部四种类型
    expect($html)->toContain('富文本')
        ->and($html)->toContain('纯图')
        ->and($html)->toContain('Markdown')
        ->and($html)->toContain('纯文本');
});

it('调用方未配置类型时回退到 support 全局默认', function () {
    config([
        'sn-product.contents.product.types' => null,
        'sn-product.contents.product.default_type' => null,
        'sn-support.form_components.content.types' => [ContentType::Richtext],
        'sn-support.form_components.content.default_type' => ContentType::Richtext,
    ]);

    $html = livewire(CreateProduct::class)->html();

    // 回退为单一富文本类型：切换按钮隐藏（无“编辑器类型”），仅富文本编辑器可用
    expect($html)->toContain('商品详情')
        ->and($html)->not->toContain('编辑器类型')
        ->and($html)->not->toContain('纯图')
        ->and($html)->not->toContain('Markdown');
});

it('商品详情必填：不填内容时报校验错误', function () {
    livewire(CreateProduct::class)
        ->fillForm([
            'title' => '无详情产品',
            'params' => ['产地' => '上海'],
            'scheduledTasks' => [],
            'product_image' => [UploadedFile::fake()->image('cover.jpg')],
            'product_images' => [UploadedFile::fake()->image('gallery.jpg')],
            'spec_type' => 'single',
            'variant' => [
                'price' => '10.00',
                'stock' => 1,
                'product_sn' => 'SN-CONTENT',
                'weight' => '0.1',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['content.content_richtext']);

    expect(Product::where('title', '无详情产品')->first())->toBeNull();
});

it('商品详情编辑器带内容预览操作', function () {
    $html = livewire(CreateProduct::class)->html();

    expect($html)->toContain('预览');
});

it('预览操作弹窗渲染当前编辑器内容', function () {
    livewire(CreateProduct::class)
        ->fillForm([
            'content.content_type' => ContentType::Richtext->value,
            'content.content_richtext' => '<p>预览的详情内容</p>',
        ])
        ->mountAction(TestAction::make('previewContent')->schemaComponent('content.content_richtext', 'form'))
        ->assertMountedActionModalSeeHtml('<p>预览的详情内容</p>');
});

it('富文本详情写入 sn_contents 关联', function () {
    livewire(CreateProduct::class)
        ->fillForm([
            'title' => '富文本详情产品',
            'params' => ['产地' => '上海'],
            'scheduledTasks' => [],
            'product_image' => [UploadedFile::fake()->image('cover.jpg')],
            'product_images' => [UploadedFile::fake()->image('gallery.jpg')],
            'spec_type' => 'single',
            'variant' => [
                'price' => '10.00',
                'stock' => 1,
                'product_sn' => 'SN-CONTENT',
                'weight' => '0.1',
            ],
            'content.content_type' => ContentType::Richtext->value,
            'content.content_richtext' => '<p>商品详情正文</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('title', '富文本详情产品')->first();

    expect($product->content)->not->toBeNull()
        ->and($product->content->content_type)->toBe(ContentType::Richtext)
        ->and($product->content->content)->toBe('<p>商品详情正文</p>');
});

it('纯图模式详情以 JSON 路径数组写入并落盘文件', function () {
    livewire(CreateProduct::class)
        ->fillForm([
            'title' => '纯图详情产品',
            'params' => ['产地' => '上海'],
            'scheduledTasks' => [],
            'product_image' => [UploadedFile::fake()->image('cover.jpg')],
            'product_images' => [UploadedFile::fake()->image('gallery.jpg')],
            'spec_type' => 'single',
            'variant' => [
                'price' => '10.00',
                'stock' => 1,
                'product_sn' => 'SN-CONTENT',
                'weight' => '0.1',
            ],
            'content.content_type' => ContentType::Images->value,
            'content.content_images' => [
                UploadedFile::fake()->image('detail-1.jpg'),
                UploadedFile::fake()->image('detail-2.jpg'),
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('title', '纯图详情产品')->first();

    expect($product->content)->not->toBeNull()
        ->and($product->content->content_type)->toBe(ContentType::Images);

    $paths = json_decode($product->content->content, true);

    expect($paths)->toHaveCount(2);

    foreach ($paths as $path) {
        Storage::assertExists($path);
    }
});

it('编辑产品时回填纯图内容并可切换为富文本', function () {
    // FileUpload 回填时会校验文件在磁盘上存在，先写入 fake 盘
    Storage::put('sn/product/contents/old-detail.jpg', 'fake-content');

    $product = Product::factory()->create([
        'scope_type' => 'sn-product',
        'scope_id' => 0,
    ]);
    $product->content()->create([
        'content' => json_encode(['sn/product/contents/old-detail.jpg']),
        'content_type' => ContentType::Images->value,
    ]);

    livewire(EditProduct::class, ['record' => $product->getKey()])
        ->assertFormSet([
            'content.content_type' => ContentType::Images,
            'content.content_images' => ['sn/product/contents/old-detail.jpg'],
        ])
        ->fillForm([
            'params' => ['产地' => '上海'],
            'variant' => [
                'price' => '10.00',
                'stock' => 1,
                'product_sn' => 'SN-CONTENT',
                'weight' => '0.1',
            ],
            'product_image' => [UploadedFile::fake()->image('cover.jpg')],
            'product_images' => [UploadedFile::fake()->image('gallery.jpg')],
            'content.content_type' => ContentType::Richtext->value,
            'content.content_richtext' => '<p>切换为富文本详情</p>',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh()->content->content_type)->toBe(ContentType::Richtext)
        ->and($product->content->content)->toBe('<p>切换为富文本详情</p>');
});

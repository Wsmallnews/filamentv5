<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;
use Wsmallnews\Media\Models\Media;
use Wsmallnews\Media\Models\MediaDirectory;

/**
 * SidebarNestedset 组件验证页（临时，未提交）：
 * 左侧 TestDirectoriesTree 子类（哨兵行由宿主自建——组件只输出树行），
 * 右侧内容区接收 leaf/node-click 事件联动，选中态经 activeId 回传高亮。
 */
class SidebarNestedsetTestPage extends Page
{
    /** null = 全部；0 = 未归类；>0 = 目录 id */
    public ?int $selectedDir = null;

    protected static ?string $slug = 'sidebar-nestedset-test';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string | \UnitEnum | null $navigationGroup = '素材库';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'SidebarNestedset 测试';

    protected string $view = 'filament.pages.sidebar-nestedset-test';

    #[On('sn-filament-nestedset-leaf-click')]
    #[On('sn-filament-nestedset-node-click')]
    public function onTreeClick(int $recordId): void
    {
        // 切换选中：再次点击同一目录取消选中（回「全部」）
        $this->selectedDir = ($this->selectedDir === $recordId) ? null : $recordId;
    }

    #[On('sn-filament-nestedset-updated')]
    public function refreshFromTree(): void
    {
        // 树 CRUD 后整页重渲染（计数新鲜）
    }

    protected function dirMedias()
    {
        $query = Media::query();

        if ($this->selectedDir === 0) {
            $query->whereNull('directory_id');
        } elseif ($this->selectedDir !== null && $this->selectedDir > 0) {
            $directory = MediaDirectory::find($this->selectedDir);
            if ($directory) {
                $query->whereIn('directory_id', $directory->selfAndDescendantIds());
            }
        }

        return $query->latest('id')->limit(20)->get();
    }
}

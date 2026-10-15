@php
    use Filament\Support\Icons\Heroicon;
@endphp

<x-filament-panels::page>
    <div class="flex min-h-[480px] gap-4">
        {{-- 左侧：宿主自建容器/哨兵行 + SidebarNestedset 子类（组件只输出树行） --}}
        <aside class="flex w-64 shrink-0 flex-col gap-0.5 overflow-y-auto rounded-xl border border-gray-200 p-2 text-sm dark:border-gray-700">
            {{-- 哨兵行：宿主筛选状态 UI，由宿主自己实现 --}}
            <button wire:click="$set('selectedDir', null')"
                class="flex w-full items-center justify-between rounded-md px-2 py-1.5 {{ $selectedDir === null ? 'bg-primary-50 font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5' }}">
                <span>全部文件</span>
                <span class="text-xs text-gray-400">{{ Wsmallnews\Media\Models\Media::count() }}</span>
            </button>
            <button wire:click="$set('selectedDir', 0)"
                class="flex w-full items-center justify-between rounded-md px-2 py-1.5 {{ $selectedDir === 0 ? 'bg-primary-50 font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'text-gray-400 italic hover:bg-gray-50 dark:text-gray-500 dark:hover:bg-white/5' }}">
                <span>未归类</span>
                <span class="text-xs text-gray-400">{{ Wsmallnews\Media\Models\Media::whereNull('directory_id')->count() }}</span>
            </button>

            {{-- 类名实例化：snapshot 存完整类名，子组件独立 update 请求按类解析，无需名字注册 --}}
            @livewire(\App\Filament\Components\TestDirectoriesTree::class, ['activeId' => $selectedDir])
        </aside>

        {{-- 右侧：内容区（接收 leaf/node-click 事件联动） --}}
        <div class="min-w-0 flex-1 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ $selectedDir === null ? '全部文件' : ($selectedDir === 0 ? '未归类' : '目录 #' . $selectedDir) }}
                <span class="ml-1 text-xs text-gray-400">（共 {{ $this->dirMedias()->count() }} 条，最多显示 20）</span>
            </p>
            <ul class="mt-2 space-y-1 text-sm">
                @forelse ($this->dirMedias() as $media)
                    <li class="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-gray-50 dark:hover:bg-white/5">
                        <x-filament::icon :icon="Heroicon::OutlinedDocument" class="size-4 shrink-0 text-gray-400" />
                        <span class="truncate">{{ $media->name }}</span>
                    </li>
                @empty
                    <li class="px-2 py-6 text-center text-sm text-gray-400">暂无素材</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-filament-panels::page>

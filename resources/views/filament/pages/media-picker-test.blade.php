@php
    use Filament\Support\Icons\Heroicon;
@endphp

<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-4 flex items-center gap-3">
            <x-filament::button type="submit" :icon="Heroicon::OutlinedPaperAirplane">
                提交（测试页不落库）
            </x-filament::button>
            <span class="text-xs text-gray-500">提交仅在顶部出通知；字段保存值见下方 JSON 面板，实时联动</span>
        </div>
    </form>

    {{-- 字段保存值（JSON 契约） --}}
    <div class="mt-8 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold">
            <x-filament::icon :icon="Heroicon::OutlinedClipboardDocument" class="size-4 text-gray-400" />
            字段保存值（JSON 契约，实时联动）
        </h2>
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($this->valueFields() as $label => $value)
                <div>
                    <p class="mb-1 font-mono text-xs text-gray-400">{{ $label }}</p>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-3 text-[11px] leading-5 text-emerald-300">{{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs leading-5 text-gray-500">
            键含义：<b>path</b> 相对路径（生成后不可变）· <b>name</b> 原始文件名 · <b>mime / size</b> 类型与字节数 · <b>width / height</b> 仅图片。
            FilePicker（直接上传）与 MediaPicker 的值契约完全一致，差异只在选择交互与目标盘（支持私有盘）。
        </p>
    </div>
</x-filament-panels::page>

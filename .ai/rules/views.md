---
paths:
  - 'addons/cms/resources/views/**'
---

# Views

## 前台链接� 须用 x-filament::button 或 generate_href_html
前台需要跳转的元素:优先 `<x-filament::button tag="a" href="...">`(按钮样式统一,且 href 自带 SPA/wire:navigate 模式判断);若用裸 `<a>` 标签,href 必须经 `\Filament\Support\generate_href_html($url, $target)` 输出(自带转义与 target 处理),不要手写 href 属性拼接。纯展开/无跳转语义的占位链接固定 `href="javascript:;"`。

## 侧栏 + 内容布局统一用比例分栏（lg 起 1:3，xl 起 1:4）

页面左侧（或右侧）有侧栏（用户菜单、分类树、同级导航卡片等）+ 主内容区的布局，**不要写死侧栏宽度（`w-72` 之类）**，统一用 grid 比例分栏：

- 断点 `lg`（1024px）起并排：`lg:grid lg:grid-cols-4 xl:grid-cols-5`（侧栏 1 格、内容 3/4 格，即 lg 1:3、xl 起 1:4）；lg 以下 `flex flex-col` 上下堆叠，侧栏 DOM 在前 = 堆叠时在上
- 内容列 `lg:col-span-3 xl:col-span-4`；侧栏是条件渲染时，内容列必须兜底占满整行（`lg:col-span-4 xl:col-span-5`），避免 grid 留空轨道
- 两列都加 `min-w-0`（防内容撑破轨道）；区块间距用 `sn-gap`
- 右侧栏 = 内容 div 写在前、侧栏 div 写在后（grid 按源顺序自动放置 = 内容左、侧栏右；堆叠时内容在上）

```blade
<div class="w-full flex flex-col lg:grid lg:grid-cols-4 xl:grid-cols-5 items-start sn-gap">
    <div class="w-full min-w-0">
        {{-- 侧栏 --}}
    </div>

    {{-- 侧栏条件渲染时，内容列兜底占满整行 --}}
    <div @class([
        'w-full min-w-0 flex flex-col sn-gap',
        'lg:col-span-3 xl:col-span-4' => $hasSidebar,
        'lg:col-span-4 xl:col-span-5' => ! $hasSidebar,
    ])>
        {{-- 主内容 --}}
    </div>
</div>
```

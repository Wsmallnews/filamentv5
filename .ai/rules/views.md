---
paths:
  - 'addons/cms/resources/views/**'
---

# Views

## 前台链接必须用 x-filament::button 或 generate_href_html
前台需要跳转的元素:优先 `<x-filament::button tag="a" href="...">`(按钮样式统一,且 href 自带 SPA/wire:navigate 模式判断);若用裸 `<a>` 标签,href 必须经 `\Filament\Support\generate_href_html($url, $target)` 输出(自带转义与 target 处理),不要手写 href 属性拼接。纯展开/无跳转语义的占位链接固定 `href="javascript:;"`。

## 侧栏 + 内容布局统一用比例分栏（lg 起 1:3，xl 起 1:4）

页面左侧（或右侧）有侧栏（用户菜单、分类树、同级导航卡片等）+ 主内容区的布局，**不要写死侧栏宽度（`w-72` 之类）**，统一用 grid 比例分栏：

- 断点用**等宽容器断点**（换算表见下方容器体系规则）：`@5xl:grid @5xl:grid-cols-4 @7xl:grid-cols-5`（侧栏 1 格、内容 3/4 格）；窄容器 `flex flex-col` 上下堆叠，侧栏 DOM 在前 = 堆叠时在上
- 内容列 `lg:col-span-3 xl:col-span-4`；侧栏是条件渲染时，内容列必须兜底占满整行（`lg:col-span-4 xl:col-span-5`），避免 grid 留空轨道
- 两列都加 `min-w-0`（防内容撑破轨道）；区块间距用 `sn-gap`
- 右侧栏 = 内容 div 写在前、侧栏 div 写在后（grid 按源顺序自动放置 = 内容左、侧栏右；堆叠时内容在上）

```blade
<div class="sn-split">
    <div class="w-full min-w-0">{{-- 侧栏 --}}</div>
    <div class="sn-split-main">{{-- 主内容 --}}</div>
</div>
```
- `.sn-split`：窄容器 flex 上下堆叠（侧栏 DOM 在前 = 堆叠时在上）；`@4xl`（56rem）起 grid 1:3，`@6xl`（72rem）起 1:4
- `.sn-split-main`：窄容器全宽，宽容器占 3/4 格；侧栏列直接 `w-full min-w-0`

## 前台空态用 x-sn-support::empty，Filament 面板内用 x-filament::empty-state，不混用
前台（Livewire 组件/主题视图）的空状态一律 `<x-sn-support::empty>`（sn-empty 令牌族：icon/heading/description/footer/actions/compact/contained）；Filament 面板语境（资源页、面板组件）用官方 `<x-filament::empty-state>`。两个体系的图标/配色/间距规范不同，不要在面板里用 sn 系空态或在前台用 fi 系空态。

## sn-page（骨架唯一）/ sn-content（页面内容）双层容器体系

- `.sn-page`（页面级容器：container 对齐 + sn-page-x + 页面上下 my + 分栏承载）**只能由页面骨架（components/container/page.blade.php）渲染**，页面视图禁止再写 sn-page——页面级注入（brothers/未来的侧栏组件）只动骨架。
- 页面视图用 `.sn-content`（内容容器：flex flex-col grow + 页面 gap，**自带 `@container` 容器查询**）。无侧栏时 sn-content 独占 sn-page，视觉等价旧 sn-page。
- **断点规则：sn-content 内部的内容级响应式一律用容器断点，不用视口断点（`lg:` 等）。两套刻度宽度不同，且容器宽 = 视口 − sn-page-x 留白（lg+ 为 3rem），必须按「留白补偿映射」取档**：`md:`(视口 768px)→`@2xl:`(42rem)、`lg:`(1024px)→`@4xl:`(56rem)、`xl:`(1280px)→`@6xl:`(72rem)——保证原视口断点位恰好触发。两个经典错误：直接 `lg:`→`@lg:`（@lg: 仅 512px，提前一倍触发）；按像素等宽取 `@5xl:`(64rem)（容器被留白吃掉 3rem，触发晚一档，实测视口 1072+ 才横向）。
- 原因：sn-content 实际宽度随兄弟栏/侧栏有无变化（sidebar 场景 ≈ 3/4 宽），视口断点会在有无侧栏时表现不一致；容器断点按自身实际宽度响应，两侧场景一致。
- 容器断点消费**最近祖先容器**（sn-content / sn-page / 骨架槽均声明 @container，内容按所处层就近消费）。
- **全屏模式适配**：若未来骨架去掉 sn-page 限宽（内容全屏），容器 = 视口宽，现有断点方向自动正确（更宽只会更早满足，不会坏）；超宽容器（> 80rem）需要更多列数等增强时用任意值断点（如 `@min-[100rem]:`）补充，不预加刻度。
- 页面骨架层（头部/导航条/banner/footer 的布局断点）是视口级布局，**保持视口断点**不迁移。
- footer 组件内部的 sn-page 是组件自身对齐容器，与页面级无关，不参与本体系。

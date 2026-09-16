---
paths:
  - 'addons/*/resources/views/**'
---

# Container Queries

## 可复用内容组件一律用容器查询断点

凡是不占满整个页面容器的内容组件——会被放进内容编排槽（composition block）、可能出现在侧栏旁边（如文章详情页将支持挂侧栏）、或未来可能被编排收窄的一切组件——内部布局断点必须用容器查询变体（Tailwind v4：`@2xl:`/`@3xl:`/`@5xl:` 等），禁止再用视口断点（`sm:`/`md:`/`lg:`/`xl:`）。视口断点只看浏览器宽度，组件进了窄槽不会回退为上下结构；容器断点看组件实际宽度，放哪都对。

## 组件自含容器优先

组件不应依赖外部提供容器上下文（跨模块复用时对方页面未必有 `@container` 祖先；没有祖先容器时容器查询永不匹配）。注意 CSS 语义：**元素的容器查询看祖先容器，`@container` 加在自己身上对自己的类无效**——响应类通常在组件根元素上，因此自含的标准做法是外包一层 `<div class="w-full @container">`（posts / index-posts 即此形态）；根元素本身不带响应类的可直接在根上加 `@container`（product detail）；组件调用支持 class 透传时优先透传（product index 经 paginators.container 的 class 传 `@container`，不额外套层）。

编排槽（support 包 `components/composition/block.blade.php`）与 cms 页面容器（`components/container/page.blade.php` slot 包裹层）的 `@container` 作为兜底保留——服务于未自含的场景与块头。

## 视口断点仅限页面骨架与密度令牌

- 页面级布局（宽度本就跟视口走）：页面容器/页头/页脚/主导航、编排行分栏（composition rows 三等分栅格）、views.md 的侧栏+主内容 1:3/1:4、账户/设置整页骨架。
- 密度类设计令牌（`sn-gap`/`sn-margin`/`sn-padded` 家族，定义在 tokens.css 的 `:root` 变量 + 单点视口 media query）**保持视口**：间距不破坏布局（窄槽里 gap-6 只是稍宽松）；且令牌集中单点定义，容器化需在每个容器元素重复声明变量、页面骨架大量使用处又无容器祖先会导致桌面间距永久回落 4 档。只有结构性布局（列数、分栏方向、固定宽度）才容器化。

## 断点换算

容器刻度与视口不同（`@lg`=32rem ≠ 视口 `lg`=64rem）。换算原则：**像素对齐**——容器宽度达到原视口断点的像素值时切换同一布局，这样全页场景（容器 ≈ 视口 - 页边距）行为与迁移前一致，窄槽自然降档：视口 sm(640)→`@2xl`(672)、md(768)→`@3xl`(768)、lg(1024)→`@5xl`(1024)、xl(1280)→`@6xl`(1152)。上限档下调一档的原因：容器恒比视口窄 1-2rem 边距，取同值（`@7xl`=1280）时全页容器（约 1248）永远达不到，高档布局会凭空消失。个别组件可按槽宽语义微调（如 index-posts 的轮播形态取 `@4xl`=896：全页与 3/4 列启用、2/3 槽及更窄堆叠）。

改完必须重建 CSS（包 @source 扫描 → 应用根目录 `npm run build` + `php artisan filament:assets`），新容器变体类才会生成；改的是已发布主题时浏览器需强刷。

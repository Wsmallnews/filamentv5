---
paths:
  - 'addons/support/resources/css/**'
---

# Resources Css

## support 包 CSS 分层：公共 sn-* 类在 utilities.css，包自有在 index.css
support 包 CSS 分层：utilities.css = 公共 sn-* 工具类（跨包/前后台通用，@import tokens.css 设计令牌）；index.css = 入口（@source 扫描 + @import utilities/content + support 组件自有样式如 sn-search-submit）。新增公共工具类放 utilities.css，包组件自有样式放 index.css；其他扩展包的 index.css 只放包自有样式。改动后需应用根目录 npm run build 重建。

## 键盘焦点环全局统一：base 层 primary inset ring，视图禁止手写 focus 系列
utilities.css 的 `@layer base` 已全局统一 `a/button/summary/[tabindex]:focus-visible` 为 `outline-none + ring-2 ring-inset ring-primary-500`（替代浏览器默认黑色 outline；元素选择器优先级低于 Filament 等组件类，不误伤组件库自带 focus）。视图里**不要再手写** `focus:outline-none focus-visible:ring-*` 系列——a/button 上的 sn-link 等类自动被全局规则覆盖；非 a/button 的可点击元素（div role="button" 等）显式加 `.sn-focus`；文字型次级链接 sn-link-more 保留其自带 offset 版焦点环。新增可聚焦元素无需任何 focus 类。

## sn-* 文字类的 hover 变色必须用 sn-hover，手写 hover:text-* 会被 layer 压死不生效
utilities.css 的 sn-* 类是**顶层规则（无 @layer）**，Tailwind 生成的 utilities（含 `hover:text-primary-600` 等变体）在 `@layer utilities` 内——CSS 规范中无 layer 的声明优先级恒高于任何 layer 内声明。因此 `class="sn-tip-text hover:text-primary-600"` 的 hover 永远不生效（被 sn-tip-text 的顶层 color 压死）。sn-* 文字类（sn-content-text/sn-descript-text/sn-tip-text/sn-h*-text 等）需要 hover 变色时一律加 `sn-hover`（同文件组合选择器 `.xxx.sn-hover:hover`，特异性更高能赢）；非 sn-* 元素才可用 Tailwind 的 hover: 变体。

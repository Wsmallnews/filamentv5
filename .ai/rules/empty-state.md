---
paths:
  - 'addons/*/resources/views/**'
---

# Empty State

## 前台空状态一律用 support 的 empty 组件（x-sn-support::empty），禁止手搓空态版式
列表/数据为空时的空状态展示统一走 `addons/support/resources/views/components/empty.blade.php`（sn-empty 令牌族：图标位 + 标题 + 描述 + footer/actions），禁止在业务视图里手写 icon+文案+间距的自拼空态。参数速查：`heading` / `description` / `icon`（heroicon 别名如 `heroicon-o-map-pin`）/ `iconColor` / `iconSize` / `actions` / `footer`；**嵌在列表卡或组件卡内时必须传 `:contained="false"`**（默认 contained=true 自带卡片皮，嵌套会双卡）；独立空态页（如搜索无结果整页）才用默认 contained。紧凑场景（下拉面板内等）加 `:compact="true"`。范本：`addons/profile/resources/views/livewire/components/address/addresses.blade.php`。

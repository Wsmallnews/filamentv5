---
paths:
  - 'addons/*/src/Livewire/**'
  - 'addons/*/resources/views/**'
---

# Component Events（扩展包组件通信）

## 组件关联的三层决策（先判层，再动手）

扩展包组件（内嵌组件）与宿主页/父组件通信前，按序判断：

1. **渲染期能定的 → props 传参**（构建期）：数据在服务端 render 时已知的（scopeable、hrefRoute、commentable、limit 等）一律属性注入，不用事件传数据。
2. **交互强耦合的 → 复合组件**：两个组件的交互需要共享中间状态/实时联动（分类树过滤列表、规格选择+价格联动）→ 合成一个 Livewire 组件内部解决，不拆成两个组件再通信。**优先在组件内部用本地状态（Alpine）承载纯展示态，减少 Livewire 请求**——但不必为了节约请求把代码写乱，服务端有校验/写库的动作必须走组件方法。
3. **仅松耦合通知才用事件**：「子组件完成某业务动作，宿主决定后续」（下单完成跳转、地址选中回填）——子组件不关心宿主要干什么。

## PHP dispatch vs JS dispatch（谁触发、怎么触发）

- **业务动作归子组件的服务端方法，一次请求内完成「动作 + dispatch 结果事件」**：`wire:click="buy"` → 后端 buy() 校验/处理 → `$this->dispatch('sn-product-buy', ...)`。禁止「JS 先 fetch → PHP 再 dispatch → 前端再请求」的三段式重复请求。
- **JS dispatch（Alpine `$wire.dispatch` / `$dispatch`）只用于纯前端交互的转发**：不含服务端逻辑的选择/输入，且页面无 SPA 依赖时才可用。
- **监听侧（2026-10-09 定稿）**：宿主对事件的**跳转/页面级响应一律 PHP `#[On]` + `$this->redirect()`**（shop 确认页 gotoCashier / 详情页 gotoConfirm 范式）。**禁止用页面内联 Alpine `$wire.on` 监听跳转**——SPA（wire:navigate）站内跳转经 morph 更新页面，**内联 `@once`/`@assets` 脚本一律不执行**（整页加载时偶尔正常是运气，勿以整页加载验证通过为准）。纯局部 UI 动作（不跳转、不开新请求）才可用 Alpine 监听，且组件脚本必须走资产管线（见 livewire.md）。
- 事件 payload 一律标量/数组（跳转场景经 http_build_query/URLSearchParams 拼参），复杂结构 JSON 序列化后传字符串（如 relate_items）。**payload 键名与监听方法参数名精确匹配**（order_sn → `string $order_sn`，不自动驼峰转换）。

## 事件命名规范：`sn-{域}-{动作}`

- 前缀 `sn-` + 派发方域名（product/order/pay/profile…，跨包事件用包名，包内组件间事件用组件名如 `sn-comment-created`）；无 `sn-` 前缀的裸事件名禁止新增。
- **结果事实用过去式**（created/finished/selected/refunded/received/cancelled），**意图用现在式**（buy）。
- 现行事件登记表（改动时同步更新本表）：

| 事件 | 派发方 → 监听方 | 时机/响应 | payload |
|---|---|---|---|
| `sn-product-buy` | product 详情组件 buy() → 宿主页（shop detail `#[On]` gotoConfirm → redirect） | 立即购买意图 → 跳转确认页 | type, from, relate_items(json) |
| `sn-order-created` | order Confirm submitOrder() → 宿主页（shop 确认页 `#[On]` gotoCashier → redirect） | 落单完成 → 跳转收银台 | order_sn |
| `sn-pay-method-selected` | pay PayMethods choose()（wire:click 服务端派发）→ 宿主收银台页（`#[On]` payStart） | 选中支付方式 → 发起支付 | channel, method |
| `sn-pay-finished` | shop Cashier → 前端按 method 消费（结果页路由就绪后改 `#[On]` redirect） | 支付完成 | pay_sn |
| `sn-pay-prepay-result` | shop Cashier → 前端按 method 消费 | 三方 prepay 结果透传（扫码/JSAPI 唤起） | pay_sn, channel, method, result |
| `sn-profile-address:selected` | profile ChooseAddress → order Confirm（`#[On]` selectAddress） | 地址选中/新增回填 | addressId |

- **将来订单操作事件**（确认收货/取消/退款按钮在 order 包组件中完成状态流转后派发，宿主页监听后刷新或跳转，同一模式扩展）：`sn-order-received` / `sn-order-cancelled` / `sn-order-refunded`。

## 参考实现（模式范本）

- 商品详情「立即购买」：product 组件 `wire:click="buy"`（一次 Livewire 请求：服务端校验选中规格）→ dispatch `sn-product-buy` → shop 详情页 Alpine `$wire.on` → `window.location` 跳转确认页（零额外请求）。
- 确认页地址回填：profile ChooseAddress（子组件）dispatch `sn-profile-address:selected` → order Confirm `#[On]` 更新 addressId 并重跑试算。

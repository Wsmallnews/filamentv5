---
paths:
  - 'addons/*/src/**'
---

# Composition

## 编排组件的上下文元数据（构建期注入）

注册到 CompositionRegistry 的组件类型可声明两个可选键，渲染时静态打通组件关联（无运行时通信）：

- `provides => fn (array $extras): array`：上下文提供者——从自身 extras 计算产物并入**行上下文袋**（如 post-detail 提供 `['post' => PostModel]`）。闭包经 `app()->call` 调用，返回空数组安全；传值形态由作者决定（模型实例是仓库既有实践，如 `:commentable="$post"`）。
- `context => ['post', ...]`：上下文消费者——声明的键在自身 extras 未显式配置时从袋子注入（**extras 显式配置优先，不被覆盖**）。

流动规则：行内左→右（左槽提供物流到右槽）；**跨行隔离**（每行以 `resolveRows(..., $pageContext)` 的页面种子重新开始）；页面级上下文（如详情页路由解析的文章）经 `$pageContext` 每行可用。机制在 `Wsmallnews\Support\Features\Composition\CompositionRenderer::resolveRows()`。

## 组件关联的三层决策规则

1. **渲染时能定的 → 构建期上下文**（provides/context，覆盖"推荐列表知道当前文章"类）；
2. **交互强耦合的（分类树过滤列表）→ 优先做成一个复合组件**（posts 内置 categoryStyle=tree 即此思路），不要拆成两个编排组件再通信；
3. **只有通知类松耦合才用事件**（`sn-composition::{slotKey}.{event}` 作用域约定）——尚未实现，等真实场景再上，勿提前抽象。

## 注册新组件类型时

- 表单 forms 闭包的 `$fields` 参数收到的是整表根状态（Livewire `->data`，注入方式见 CompositionForm 的 extras Fieldset）；
- **forms 返回字段数组平铺，禁止嵌 Group/Section 等自带列数的布局组件**：外层 extras Fieldset 已统一 `columns(1)`（编排槽位有宽窄，管理表单列数无容器查询，内层 `->columns(['md' => 2])` 视口断点在窄槽会把选择器挤成半列）；
- CompositionForm 的类型切换 afterStateUpdated 里用 `getTypeForms` 显式构造 extras 重置值（字段名为键的关联数组 + `getDefaultState()` 默认值）：**不要 `$set('extras', [])`**（空索引数组会让前端 entangle 的字符串键在 JSON 序列化时丢失，参数保存不上），也不依赖 `getComponent()->getChildSchema()->fill()`（其产物随注册表单是否包 Group 而变，不可控）；extras Fieldset 用 `->key('dynamicExtrasFields')` 固定相对 key（随机 uuid key 会导致下拉往返时 DOM 重建、闪关）；
- 新组件视图遵循 container-queries.md（自含 `@container` + 容器断点）；
- scopeable 由注册时的固定参数 / 调用处传入，组件不读模块默认 scope（livewire.md）。

## 组件间事件（D 期预留草案，未实施）

出现运行期松耦合通知需求时按此约定实施（当前无场景，勿提前抽象）：优先 Alpine `$dispatch`（同页 DOM 局部、零网络），跨页面/跨 Livewire 组件才用 `dispatch`；事件名统一前缀 `sn-composition::`，payload 必带 `blockKey`（编排条目键）供监听方过滤同源；只做通知类松耦合——交互强耦合做复合组件，渲染期能定的走 provides/context。

---
paths:
  - 'addons/*/src/**'
---

# Addons Src

## Eloquent 查询链顺序：无参 scope → 带参 scope → with → where → 终结方法
Eloquent 查询链统一顺序：① 无参数的模型 scope（normal()/published()/hidden()/ordered() 等）最前；② 带参数的 scope（snScope()/scopeable()/自定义带参 scope）其次；③ with()/withDepth() 等预加载修饰；④ where()/whereHas() 条件；⑤ 终结方法（first()/find()/get()/paginate()）。例：Model::query()->normal()->snScope(...$scopeable)->where('type', $type)->first();

## purpose 槽位注册 meta 形态与跨包翻译时机陷阱
purpose 槽位注册（CompositionRegistry::registerPurposes）用 meta 数组形态：['label' => ..., 'positions' => ['left', 'right', '自定义' => '标签或翻译键'], 'default' => ..., 'context' => fn (array $params, array $scopeable): array => ['post' => ...]]。positions 标准位置（left/right/top/bottom）label 存 sn-support::composition.position.* 翻译键，自定义位置 键=>标签；重复注册：字符串仅覆盖 label（保留 meta），数组整体替换。**关键陷阱：注册时机禁止调用 __() 跨包命名空间**——provider boot 顺序不定（cms 早于 support），过早翻译会在翻译 hint 注册前触发分组加载并缓存空组，整个 sn-support::composition.* 组全请求失效；label/position 标签一律延迟到消费方（表单/渲染）翻译。resolveForPurpose(purpose, module, scopeType, scopeId, $params) 的 $params 是调用方路由参数（如 ['slug' => ...]），经槽位 context 提供者映射为 pageContext（null 值剔除=未命中不注入），路由壳因此不感知业务模型（薄壳）。purpose=null 仍是通用展示编排（Page 绑定通道），槽位机制互不干扰。

## purpose 槽位注册 meta 形态（闭包标签/layout_mode/context 提供者）与跨包翻译时机陷阱
purpose 槽位注册（CompositionRegistry::registerPurposes）用 meta 数组形态：['label' => fn () => __('...'), 'positions' => ['left', 'right', '自定义' => '标签|翻译键|闭包'], 'default' => ..., 'layout_mode' => 'stack'|'rows'（可选，未声明按位置语义推导：左/右=stack 单列堆叠、上/下=rows 行式、无槽位=rows）, 'context' => fn (array $params, array $scopeable): array => [...]]。**标签一律闭包或纯文本（消费时经 resolveLabel 求值），禁止注册时跨包 __()**——provider boot 顺序不定（cms 早于 support），过早翻译会抢在翻译 hint 注册前触发分组加载并缓存空组，整个 sn-support::composition.* 组全请求失效。标准位置 label 由 Registry 自动生成翻译键闭包，注册方无需接触 support 键。resolveForPurpose(purpose, module, scopeType, scopeId, $params) 的 $params 是调用方路由参数（如 ['slug' => ...]），经槽位 context 提供者映射为 pageContext（null 值剔除），路由壳保持薄壳。表单侧：CompositionForm 堆叠模式隐藏分栏开关与右栏、按钮文案「添加侧栏块」、位置/槽位切换经 syncRowsToLayoutMode 强制行通栏；purpose=null 仍是通用展示编排（Page 绑定通道）。

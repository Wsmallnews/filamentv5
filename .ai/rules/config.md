---
paths:
  - 'addons/*/config/*.php'
---

# Config

## scopeables 实例声明：main 必须 + 差异显式 + 引用可校验
模块 config 用 `scopeables`（复数）声明实例：`main` 为默认实例且必须存在（缺失抛异常），只有需要差异分区的实例（如 cms footer）才声明。panel_register 条目以 `'scopeable' => '实例键'` 显式引用（省略 = main）；解析链 CanBeConfigured::getScopeable()：实例键 → 显式 pair（scope_type/scope_id）→ main，全空抛 RuntimeException（'default' 兜底已删）。SupportUtils::getScopeFromInstances() 对未知实例键、多实例同分区、scope_type 缺失均抛异常。旧的 `'scopeable' => [pair]` 单数键与 `-footer` 字符串拼接派生已废弃，不要恢复。

## scopeables 实例声明 + module_id 注册即归属
模块 config 用 `scopeables`（复数）声明实例：`main` 为默认实例且必须存在（缺失抛异常），只有需要差异分区的实例（如 cms footer）才声明。panel_register 条目规范：用 main 的资源写裸 FQCN（省略即 main），差异实例才写 `['scopeable' => '键']`；**module_id 禁止手工声明**——RegistersConfigurable::buildConfiguration 注册时自动注入注册插件的 getId()（注册即归属，自动值覆盖手工值）。解析链 CanBeConfigured::getScopeable()：实例键 → 显式 pair（scope_type/scope_id）→ main，全空抛 InvalidScopeException（英文消息）。SupportUtils::getScopeFromInstances() 对未知实例键、多实例同分区、scope_type 缺失均抛异常。

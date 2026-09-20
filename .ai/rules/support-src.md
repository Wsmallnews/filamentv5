---
paths:
  - 'addons/cms/src/**, addons/support/src/**'
---

# Support Src

## 模块标识（module）与 scope 正交：registry 寻址用 module，数据隔离用 scopeable
CompositionRegistry（Wsmallnews\Support\Features\Composition\CompositionRegistry，Facade 为 Wsmallnews\Support\Facades\CompositionRegistry）的注册/读取 key = 模块标识（插件 id，如 app(CmsPlugin::class)->getId()），参数与集合已用 module 词汇（$modules/register(string $module)），与 scope 语义正交：scope_type/scope_id 管数据归属隔离（Composition 恒用模块 main scope，不派生），module 管组件由哪个模块提供；footer 差异实例（config scopeables.footer，scope_type 为 sn-cms-footer，仅导航树实例分区用）禁止充当 registry key（footer 曾因此查空组件）。零代码注册编排资源无需声明 module_id（RegistersConfigurable 注册时自动注入注册插件的 id，CanBeConfigured::getModuleId() 直接读取）。注意 livewire() 直连测试不经 resource-configuration 路由中间件，需显式 setCurrentPanel + setCurrentResourceConfigurationKey（见 CompositionTest 的 withCompositionConfiguration 助手）。

## scopeable 解析链（CanBeConfigured）与 scopeables 实例声明
CanBeConfigured::getScopeable() 解析优先级：配置实例键（scopeable）→ 显式 pair（scope_type/scope_id）→ 模块 main 实例；模块语境与显式 pair 均缺失时抛 InvalidScopeException——不再回落 'default'。module_id **注册时自动注入**（RegistersConfigurable::buildConfiguration 把注册插件的 getId() 烤进 configuration，「注册即归属」，跨模块注册无需也不应手工声明 module_id，自动值覆盖手工值）；无面板语境直接抛异常（不再回落资源自有插件 id）。实例经 SupportUtils::getScopeFromInstances("{$configRoot}.scopeables", $key) 解析：main 必须存在、未知实例键 / 多实例同分区 / scope_type 缺失均抛 InvalidScopeException。模块 config 用 `scopeables`（复数）声明实例，旧的 `'scopeable' => [pair]` 单数键与 `-footer` 拼接派生已废弃。panel_register 条目规范：用 main 实例的资源写裸 FQCN（省略即 main），差异实例才写 `['scopeable' => '键']`。注意 livewire() 直连测试不经 IdentifyResourceConfiguration/IdentifyPageConfiguration 中间件，需显式面板上下文：全局助手 withAdminPanelContext（tests/Pest.php）或文件级 beforeEach/afterEach（见 SlugTest/PostContentTest 等）。注意面板语境下 Post::getRouteKeyName 返回主键（is_in_panel 门控），测试 record 参数须传主键而非 slug。

## CompositionRegistry 寻址用 module（与 scope 正交）+ 包匿名组件标签不带 components. 段
CompositionRegistry（Wsmallnews\Support\Features\Composition\CompositionRegistry，Facade 为 Wsmallnews\Support\Facades\CompositionRegistry；原 CompositionRegistry 已改名）的注册/读取 key = 模块标识（插件 id，如 app(CmsPlugin::class)->getId()），与 scope 正交：scope_type/scope_id 管数据归属（Composition 恒用模块 main scope，不派生），module 管组件由哪个模块提供；footer 差异实例（scopeables.footer）禁止充当 registry key。零代码注册编排资源无需声明 module_id（注册时自动注入）。命名空间匿名组件标签不要带 components. 段：Blade 会自动前缀 namespace::components.，写 <x-sn-support::composition.rows>（对应 views/components/composition/rows.blade.php），写成 components.composition.rows 会解析成双层 components 而报 Unable to locate。livewire() 直连测试不经 resource-configuration 中间件，需显式 setCurrentPanel + setCurrentResourceConfigurationKey（CompositionTest 的 withCompositionConfiguration 助手）。

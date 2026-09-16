---
paths:
  - 'addons/cms/src/**, addons/support/src/**'
---

# Support Src

## 模块标识（module）与 scope 正交：registry 寻址用 module，数据隔离用 scopeable
CompositionRegistry（Wsmallnews\Support\Features\Composition\CompositionRegistry，Facade 为 Wsmallnews\Support\Facades\CompositionRegistry）的注册/读取 key = 模块标识（插件 id，如 app(CmsPlugin::class)->getId()），参数与集合已用 module 词汇（$modules/register(string $module)），与 scope 语义正交：scope_type/scope_id 管数据归属隔离（Composition 恒用模块主 scope，不派生），module 管组件由哪个模块提供；派生 scope（sn-cms-footer，仅导航树实例分区用）禁止充当 registry key（footer 曾因此查空组件）。零代码注册编排资源时 config 声明 scope_type/scope_id + module_id 三键（CanBeConfigured::getModuleId()：module_id 配置 → getEssentialsPlugin()->getId() → null）。注意 livewire() 直连测试不经 resource-configuration 路由中间件，需显式 setCurrentPanel + setCurrentResourceConfigurationKey（见 CompositionTest 的 withCompositionConfiguration 助手）。

## CompositionRegistry 寻址用 module（与 scope 正交）+ 包匿名组件标签不带 components. 段
CompositionRegistry（Wsmallnews\Support\Features\Composition\CompositionRegistry，Facade 为 Wsmallnews\Support\Facades\CompositionRegistry；原 CompositionRegistry 已改名）的注册/读取 key = 模块标识（插件 id，如 app(CmsPlugin::class)->getId()），与 scope 正交：scope_type/scope_id 管数据归属（Composition 恒用模块主 scope，不派生），module 管组件由哪个模块提供；派生 scope（sn-cms-footer，仅导航树实例分区）禁止充当 registry key。零代码注册编排资源时 config 声明 scope_type/scope_id + module_id 三键（CanBeConfigured::getModuleId()）。命名空间匿名组件标签不要带 components. 段：Blade 会自动前缀 namespace::components.，写 <x-sn-support::composition.rows>（对应 views/components/composition/rows.blade.php），写成 components.composition.rows 会解析成双层 components 而报 Unable to locate。livewire() 直连测试不经 resource-configuration 中间件，需显式 setCurrentPanel + setCurrentResourceConfigurationKey（CompositionTest 的 withCompositionConfiguration 助手）。

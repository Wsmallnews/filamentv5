---
paths:
  - 'addons/cms/src/**'
---

# Src

## CompositionRegistry key 与内容实体查询用模块归属，不用页面实例 scope
CompositionRegistry（已下沉 support，Facade 为 Wsmallnews\Support\Facades\CompositionRegistry）的注册/读取 key = 模块标识插件 id（app(CmsPlugin::class)->getId()），禁止用页面实例 scope_type——footer 差异实例页面（scopeables.footer，scope_type 为 sn-cms-footer）曾因拿页面 scope 当 registry key 而查空组件。同理，内容实体（Composition 等）的查询一律用模块归属 Utils::getScopeable()（main 默认实例），不用页面实例 scope。

## scopeables 实例声明：main 必须存在，差异才配置，引用必须显式
模块 config 用 `scopeables`（复数）声明实例：`main` 为默认实例（必须存在，缺失抛异常），只有需要差异分区的实例（如 footer）才声明。消费方在 panel_register 条目以 `'scopeable' => '实例键'` 显式引用（解析器对未知实例键、多实例指向同一分区均抛异常）；页面/组件代码经 `Utils::getScopeable('footer')` 取用。旧的 `'scopeable' => [pair]` 单数键与 `-footer` 字符串拼接派生已废弃，不要恢复。

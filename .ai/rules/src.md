---
paths:
  - 'addons/cms/src/**'
---

# Src

## CompositionRegistry key 与内容实体查询用模块归属，不用页面实例 scope
CompositionRegistry（已下沉 support，Facade 为 Wsmallnews\Support\Facades\CompositionRegistry）的注册/读取 key = 模块标识插件 id（app(CmsPlugin::class)->getId()），禁止用页面实例 scope_type——footer 等派生 scope 页面（sn-cms-footer）曾因拿页面 scope 当 registry key 而查空组件。同理，内容实体（Composition 等）的查询一律用模块归属 Utils::getScopeable()，不用页面实例 scope。

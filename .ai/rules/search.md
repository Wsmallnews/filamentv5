---
paths:
  - 'addons/support/src/Features/Search/**'
---

# Search

## � �局搜索模块� �置经 Search::config 通道，引擎用 ConfigurableEngine 接收
模块级搜索选项统一经 Search::config($module, [...]) 声明、由 SearchRegistry 集中管理：null 值键视为未声明、回退全局 sn-support.search.*。引擎要消费模块配置必须实现 ConfigurableEngine 接口（resolveEngine 解析后注入 setSearchConfig），只实现 Engine 的自定义引擎不受影响。terms_operator（'and'|'or'）与 show_search_button（仅 display=page 生效，优先级：组件属性 showButton > 模块 > 全局）都走此通道，新增选项扩展键名即可，勿在引擎里直接读 config()。

## � �局搜索模块� �置：sn-support.search 任意键可模块覆盖，经 Search::config 整节透传
sn-support.search.* 的任意键都可作为模块声明键，统一经 Search::config($module, [...]) 声明、SearchRegistry::resolveConfig 解析（模块声明 > 全局 > 默认，null = 未声明）。扩展包在 ServiceProvider 中整节透传接入：collect(Utils::getConfig('search'))->except('enabled')，想覆盖哪个键就写哪个；page 用 $config['page'] ??= 闭包 兜底包内结果页。引擎要消费模块配置必须实现 ConfigurableEngine 接口（resolveEngine 注入 setSearchConfig），只实现 Engine 的自定义引擎不受影响；不要在引擎里绕过注入直接读 config()。组件属性（display/debounce/showButton/limit）优先于模块声明。

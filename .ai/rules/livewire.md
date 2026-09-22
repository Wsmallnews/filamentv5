---
paths:
  - 'addons/*/src/Livewire/**'
---

# Livewire

## Livewire 组件分层：Components/ 为内嵌组件由调用方传 scopeable，目录外为整页组件走 Base 模块 scope
Livewire 目录分层约定：Components/ 目录下全部是内嵌组件——不可直接访问（不配路由），所需 scopeable 参数一律由调用处显式传入（blade 里 :scope-type/:scope-id 或父组件传递），组件自身不读模块默认 scope；Components/ 之外的类都是整页组件——必须配置路由直接访问，默认继承模块 Base（cms 的 Base 覆写了 getScopeable()/getScopeType()/getScopeId() 返回 Utils::getScopeable() 即 config scopeables 的 main 默认实例，差异实例如 footer 经 Utils::getScopeable('footer')），页面级查询直接用 $this->getScopeable()。新建组件时按此判断放哪个目录：要传参内嵌 → Components/；要路由直达 → 目录外 + Base 子类 + 路由注册。

## CanBeContained 视图按子元素形态选容器模式：内容贴卡型边距在容器，行式列表型边距在行
内嵌组件 use `Wsmallnews\Support\Livewire\Concerns\CanBeContained`（`public bool $contained = true`）后，视图用 `@class` 写「恒定基线 + 条件卡片」：基线项（数组无键项）恒渲染布局类，`=> $contained` 项按需追加卡片外观。`contained=false` 只去掉卡片皮（sn-container），组件内部结构和行边距原样保留。按形态二选一：

- **内容贴卡型**（Post、Comments）：子元素是标题/正文/按钮等，sn-* 文字类不带 margin → 基线 `w-full flex flex-col sn-gap` + 条件 `sn-container sn-padded`。padded 管内容与卡片边缘的间距，flex gap 管子元素互相间距。
- **行式列表型**（posts、related-posts、search-results）：行的 `sn-padded`/`sn-px` 是行自身的边距语义（**无条件保留，嵌套时也在**），`sn-list-header` 自带 `px-(--sn-space-card)` + border-b → 容器只要条件 `sn-container overflow-hidden`（卡片皮 + 裁 divide-y 分割线圆角，**禁加 sn-padded**——会与行/header 双重缩进且分割线断开）。

判据：纯内容块 → 内容贴卡型；带行/工具条的列表 → 行式列表型。页面骨架组件（Navigation/Footer/Breadcrumb）与无自有卡片外观的组件（分类树、同级导航）不适用 CanBeContained。注意 `@class` 数组无键项是恒定基线不是条件回退，别把基线类写成 `=> ! $contained` 互斥分支。

## 跨模块导航复用：直接嵌入 cms 导航组件 + :module 配置寻址（视图副本是最后退路）
复用 cms 导航（shop 已落地）：调用方在自己的页面容器里直接 `<livewire:sn-cms::components.navigation.navigation :scope-type :scope-id :module="app(本包Plugin)->getId()" />`——**同一个组件、同一套视图与 sn-cms-nav CSS**。组件经 `Wsmallnews\Support\Livewire\Concerns\HasModuleContext`（通用能力，沉淀在 support） 解析 module 上下文：navigationConfig/moduleConfig 优先读消费模块的 config（{module}.navigation / themes / search / guard 节），moduleRoute 走 {module}.routes.name 前缀，未声明时回落组件所有方默认——两个模块的导航形态互不影响。Brothers 同法接入。Footer 为 cms 自用组件（不复用）：其 scopeable 由调用处在嵌 入处解析（Utils::getScopeable('footer')）后显式传入，组件内部禁止解析 scope——内嵌组件的数据上下文（scope/module）一律经 props 注入，此为通用原则。primary 风格文字为白色，容器必须给深色背景（sn-primary-bg）。theme-view prop 是视图覆盖的最后退路（仅在复用视图确实无法满足时才做自定义副本）。

## 扩展包分层与组件复用契约：support=基础设施，领域包可独立可复用，Components/=内嵌组件经 props 注入
分层：support=跨领域基础设施（search/feed/主题令牌/编排/定时任务/组件工厂），不含业务域；领域包（cms=内容域：导航/文章/页面/链接，shop=交易域，order=订单域，product/category/member/user 各自域）既可独立安装访问，也设计为被其他领域包引用——把功能拆成可复用扩展包是核心思想，导航和 post 留在 cms 不拆包（拆分无尽头+与页面/编排/footer 联动紧+scopeable 已解决数据隔离，复用靠机制不靠拆包）。复用三要素契约：① src/Livewire/Components/ 目录=内嵌组件（可复用单元），外部依赖一律经 props 注入，不读模块默认 scope/config；目录外=整页组件，各模块用自己的整页组件设路由；② :scope-type/:scope-id 数据隔离；③ :module 配置与注册表寻址（HasModuleContext 模式：消费模块 config 优先回落组件所有方默认）。跨包引用即在 composer 声明依赖（cms 补声明了 user）。

## Livewire 命名空间组件命名：目录单数 + 列表复数，逐段大写反查类
组件名 `sn-xxx::components.<子目录>.<类名短名>` 经 Livewire Finder 逐段首字母大写反查类：components.product.products → Components\Product\Products。目录名用单数领域名（Product），列表组件名用复数（product.products）、详情单数（product.product），照 cms post 模式。写成 components.products.products（复数目录）会反查 Components\Products\Products 直接 ComponentNotFoundException，且报错只在整页渲染时出现（组件测试传类名不经过名称解析）。

## 基础扩展包资源由消费模块注册：数据落消费模块 main scope，嵌入组件传消费模块 scopeable
基础扩展包（product 等不独立访问的领域包）自身不从 panel_register 注册后台资源——由消费模块（shop）在自己的 panel_register.resources 声明 ProductResource，注册即归属 module_id = sn-shop，后台创建/查询的产品落 sn-shop main scopeable（与前台一致）。跨模块嵌入组件时 :scope-type/:scope-id 传消费模块 scopeable（shop 页面直接传 shop Base 的 getScopeable()，搜索注册同理）；详情/跳转链接由消费方以路由名 prop 传入（products 组件 hrefRoute，参数名与 preference 包一致），领域包自身不设路由、不在组件里生成跳转链接；购买等交易动作用事件派发（product-buy），由消费页面监听后跳转。

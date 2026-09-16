---
paths:
  - 'addons/*/src/Livewire/**'
---

# Livewire

## Livewire 组件分层：Components/ 为内嵌组件由调用方传 scopeable，目录外为整页组件走 Base 模块 scope
Livewire 目录分层约定：Components/ 目录下全部是内嵌组件——不可直接访问（不配路由），所需 scopeable 参数一律由调用处显式传入（blade 里 :scope-type/:scope-id 或父组件传递），组件自身不读模块默认 scope；Components/ 之外的类都是整页组件——必须配置路由直接访问，默认继承模块 Base（cms 的 Base 覆写了 getScopeable()/getScopeType()/getScopeId() 返回 Utils::getScopeable() 即当前模块配置的 scopeable），页面级查询直接用 $this->getScopeable()。新建组件时按此判断放哪个目录：要传参内嵌 → Components/；要路由直达 → 目录外 + Base 子类 + 路由注册。

## CanBeContained 视图按子元素形态选容器模式：内容贴卡型边距在容器，行式列表型边距在行
内嵌组件 use `Wsmallnews\Support\Livewire\Concerns\CanBeContained`（`public bool $contained = true`）后，视图用 `@class` 写「恒定基线 + 条件卡片」：基线项（数组无键项）恒渲染布局类，`=> $contained` 项按需追加卡片外观。`contained=false` 只去掉卡片皮（sn-container），组件内部结构和行边距原样保留。按形态二选一：

- **内容贴卡型**（Post、Comments）：子元素是标题/正文/按钮等，sn-* 文字类不带 margin → 基线 `w-full flex flex-col sn-gap` + 条件 `sn-container sn-padded`。padded 管内容与卡片边缘的间距，flex gap 管子元素互相间距。
- **行式列表型**（posts、related-posts、search-results）：行的 `sn-padded`/`sn-px` 是行自身的边距语义（**无条件保留，嵌套时也在**），`sn-list-header` 自带 `px-(--sn-space-card)` + border-b → 容器只要条件 `sn-container overflow-hidden`（卡片皮 + 裁 divide-y 分割线圆角，**禁加 sn-padded**——会与行/header 双重缩进且分割线断开）。

判据：纯内容块 → 内容贴卡型；带行/工具条的列表 → 行式列表型。页面骨架组件（Navigation/Footer/Breadcrumb）与无自有卡片外观的组件（分类树、同级导航）不适用 CanBeContained。注意 `@class` 数组无键项是恒定基线不是条件回退，别把基线类写成 `=> ! $contained` 互斥分支。


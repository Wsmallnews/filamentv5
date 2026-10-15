---
paths:
  - 'addons/*/resources/views/**'
---

# Blade Attributes（属性绑定语法）

## 组件标签上的 Alpine 动态属性用双冒号（::class），原生 HTML 元素用单冒号（:class）；区分依据是属性值由谁求值

Blade 组件标签（`<x-*>`）上单个 `:attr` 会被 Blade 当 **PHP 绑定**求值——属性值若是 Alpine 表达式（如 `isExpanded ? 'rotate-90' : ''`）会因 Undefined constant 报错或静默输出错误值。Alpine 动态属性写在组件标签上必须用双冒号 `::class` / `::aria-expanded`（Blade 的转义写法，渲染为单个 `:class`，见 [Blade 文档 escaping-attribute-rendering](https://laravel.com/framework/docs/blade#escaping-attribute-rendering)）：

```blade
{{-- 组件标签：PHP 求值绑定单冒号，Alpine 绑定双冒号 --}}
<x-filament::icon :icon="Heroicon::ChevronRight" ::class="isExpanded ? 'rotate-90' : ''" />

{{-- 原生元素：Blade 不解析属性，Alpine 绑定单冒号即可 --}}
<li x-data="{ isExpanded: true }" :aria-expanded="isExpanded ? 'true' : 'false'">
```

原生 HTML 元素（div/li/a/button 等）上的 `:class` / `:aria-*` 不经 Blade 解析，单冒号直接可用；完整前缀 `x-bind:class` 在任何标签上都合法。速记：**`:xxx` = PHP 求值（组件 props），`::xxx` = Alpine 求值但标签是组件**，原生元素上二者等价取单冒号。

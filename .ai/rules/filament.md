---
paths:
  - 'addons/*/src/Filament/**'
---

# Filament

## 完全自定义 Page 的交互优先级：Filament Action > Livewire wire:click > Alpine.js
素材管理这种非 Resource 的完全自定义 Filament Page，交互按以下优先级规划：
1. **Filament Action（最高优先）**：确认弹框/表单 modal/通知一律用 Action（`->requiresConfirmation()` / `->schema()` / `mountAction()`），与 panel 资源风格统一；
2. **Livewire wire:click**：服务端可承载的业务方法（查询/删除/上传入账）直接 `wire:click`；
3. **Alpine.js（最低优先）**：只承载纯客户端能力——事件监听（如 `livewire-upload-progress`）、本地状态管理（如多选 Set）、浏览器 API（clipboard/drag-drop）。参照：Filament table 的选中也是 Alpine Set + entangle（`$wire.set(..., false)` 不触发请求），不是每次点选发请求。禁止在 Alpine 里写业务逻辑或 modal。

## 类引入规范：Forms/Schemas 用目录导入，其余顶部 use 后直用
Filament 的 `Forms` 与 `Schemas` 两个目录类数量庞大，**用目录导入**避免 use 块爆炸：`use Filament\Forms;` + `use Filament\Schemas;`，正文以 `Forms\Components\TextInput` / `Schemas\Components\Section` 前缀引用。其他类（Action/枚举/图标/工厂等）一律顶部 `use` 后直接短名引用。同一目录需引用**多个类**时也用目录导入（如模型 casts 引多个枚举：`use Wsmallnews\Product\Enums;` 后 `Enums\ExtraStatus::class`）。**Get/Set 的正确类**（易错，勿凭记忆写）：`use Filament\Schemas\Components\Utilities\Get;` + `use Filament\Schemas\Components\Utilities\Set;`——两者都在 **Schemas** 的 Utilities 下（不存在 `Forms\Set`/`Actions\Set`，闭包参数写 `fn (Set $set)` / `fn (Get $get)`）。

## scope 字段唯一校验用 scopedUnique 而非 unique
scopeable 资源（page/post 等）的 slug 类唯一字段必须用 Filament 内置 `scopedUnique()`（不是 `unique()`——后者全表唯一会跨 scope 误拦），统一写法（PostForm 与 PageForm 已一致）：

```php
->scopedUnique(modifyQueryUsing: function (Builder $query, Component $livewire): Builder {
    return $query->scopeable($livewire::getScopeType(), $livewire::getScopeId());
})
```

用 scopeable 不用 snScope（ settles 2026-09-18）：多租户 panel 下 Filament 会给 resource 模型注册租户全局 scope（见下条），team 隔离自动生效，snScope 的 scopeTenant 是重复条件；ignoreRecord 也无需显式传（框架默认 true，编辑自动排除自身主键）。scopedUnique 走模型查询，软删除由 SoftDeletes 全局 scope 自动排除，无需手写 whereNull('deleted_at')。注意它是 Closure 规则：测试断言用 assertHasFormErrors(['slug'])，不能带 'unique' 规则名。

## scopedUnique 多租户机制：panel 全局 scope 自动隔离 team，ignoreRecord 默认 true
scopedUnique 的多租户行为（已用探针测试验证）：panel 注册 tenant 后，Panel::boot 会给每个 resource 的模型注册全局 scope（scope 名如 admin_tenancy），scopedUnique 用的 $model::query() 自动带 team 条件——此时 modifyQueryUsing 里 scopeable 与 snScope 行为等价（snScope 的 scopeTenant 只是重复条件，双保险）。无租户 panel（现状）下全局 scope 不存在，snScope 的 scopeTenant 退化为 whereNull('team_id')，比 scopeable 更防御。ignoreRecord 无需显式传：Filament 默认 shouldUniqueValidationIgnoreRecordByDefault = true（编辑自动排除自身主键）。注意 livewire() 直连测试不走 IdentifyTenant 中间件，组件 mount 后须手动重设 Filament::setCurrentPanel + setTenant($tenant, isQuiet: true)；且 Model::create 在 panel 上下文会触发 observeTenancyModelCreation 把显式传的 team_id 覆盖为当前租户，构造跨租户测试数据需 withoutEvents。

## Blade 视图图标一律 Heroicon 枚举传参，禁止字符串图标名

`<x-filament::icon :icon="Heroicon::OutlinedPhoto" />`，不用 `icon="heroicon-o-photo"`（可读性/重构安全/静态检查）。枚举两种形态：`Outlined*` case 值即 `o-*`（恒定描边风格）；**基础 case**（Check/Minus/Plus 等）按尺寸自动映射——`<x-filament::icon>` 默认 Medium → `heroicon-m-*`，传 `size` 可切 c-/s-（Heroicon::getIconForSize）。注意 `:icon` 是组件 prop 绑定（PHP 侧），枚举常量合法；含 Alpine 表达式的动态属性才需 x-bind: 前缀。

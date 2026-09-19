---
paths:
  - 'addons/*/src/Filament/**'
---

# Filament

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

---
paths:
  - 'addons/*/src/Models/**'
---

# Models

## 模型成员顺序（定规格）：traits → 框架属性 → 自定义属性 → boot → 自定义方法 → Attribute → scope → 关联
模型类成员按以下顺序书写：① `use` traits；② 框架自有属性（$table / $timestamps / $guarded / $fillable / $casts / $hidden 等）；③ 自定义属性与常量（业务常量 const、公开属性）；④ `boot()` / `booted()` 等模型事件方法；⑤ 自定义业务方法；⑥ Laravel Attribute（getXxxAttribute / mutator，新写法 Attribute::make）；⑦ scope 查询范围方法（scopeXxx）；⑧ 模型关联（belongsTo / hasMany / morphTo 等放最后）。新模型一律照此；存量模型在触碰时顺手重排（2026-09-27 定，profile 包 Address/Region/OrderAddress 已按此重排）。

## 计算属性一律用 Laravel 新写法（Attribute::get/make），禁止 getXxxAttribute 旧写法
模型 accessor / mutator 统一用 `protected function xxx(): Attribute { return Attribute::get(fn () => ...); }` 新写法（Illuminate\Database\Eloquent\Casts\Attribute），不再写 `getXxxAttribute()` 旧式方法（存量触碰时顺手迁移）。新写法优点：非公有方法不占模型 API、可内联 cast/with 复合。范本：`addons/profile/src/Models/Address.php` 的 regionLabel / fullAddress。

## is_home 互斥限定在同一导航树（scope + type_id）内
Navigation is_home 互斥范围 = scope_type + scope_id + type_id（Navigation::booted 的 saving 钩子）：同 scope 下每棵导航树（不同 type_id）各自持有首页标记互不干扰；footer 差异实例（scopeables.footer）天然隔离，无需额外条件。改动互斥范围前先核对该语义。

# addons Filament Resource 风格统一改造计划

> 本文档自包含全部背景、决策与规范，供后续会话（无此前对话上下文、可能更换电脑）直接继续执行。
> 状态：**计划已制定，尚未开始执行**（2026-09-08 制定；已按用户第二轮反馈修订，未动任何代码）。
> 范围：**只处理 `addons/` 目录下的扩展包**（cms、category、comment、member、product、support、user），不涉及主应用与 vendor。
> 执行模式：**按 §7 步骤顺序、每天一个或多个步骤**，每步控制在半天内、独立可提交；换电脑后从第一个未勾选步骤继续。

---

## 1. 背景

各扩展包的 Filament Resource（Table / Form / Enum / 状态字段 / order_column）风格已经大体趋同，但存在一批不一致：

- **order_column**：迁移统一为 `unsignedInteger()->nullable()`，但表单手填无自动填充；表格 `reorderable('order_column')` 拖拽可用，`defaultSort` 方向各表不一（product 用 desc，其余 asc），且未传 `direction` 参数。
- **status 字段**：表格列有的 badge 有的纯文本（LinksTable、PostsTable 是纯文本）；表单组件 Radio / ToggleButtons 混用；各表筛选补齐程度不一（CommentTable、MemberTable、ActivityLogTable、ScheduledTaskTable 无时间筛选）。
- **Enum 颜色/图标**："正常"态 success / primary 混用（NavigationStatus 已改 primary，其余 7 个还是 success）；图标 outlined / solid / 字符串三种写法混用（CommentStatus 用 solid、AttributeStatus 用字符串）；Hidden 色 gray / info 不一致（ProductStatus.Hidden=info）。
- **Form 布局**：PostForm、CategoryTypeForm 用 `Flex` 主+侧栏，侧栏宽度大、浪费空间；所有表单一律平铺。
- **Table 方法顺序**：事实上已基本统一（LinksTable/PostsTable 是范本），但未成文。

用户要求把这批规则沉淀到 **`addons/support/resources/boost/guidelines/core.blade.php`**（Laravel Boost 规则源文件，support 包被其他项目引用时可发布进它们的 AGENTS.md），并按包逐步改造对齐。

## 2. 已确认决策（与用户确认过，不要重新讨论）

1. **只处理 addons 目录下的扩展包**。
2. **status 表单组件统一 `ToggleButtons` + `->inline()` + `->grouped()`**，options 用枚举类。
3. **所有表单一律不用 Flex 侧栏**（含 PostForm 等复杂表单）。布局规则：**表单字段必须在 Section 中**；只有一个 Section 时，排序/状态字段放该 Section；多个 Section 时，排序/状态作为基础字段放**最上面的 Section**。
4. **"正常"类状态颜色从 success 改为 primary**（2026-09-08 拍板；NavigationStatus 已是 primary 作为范本）。success 色保留给"流程成功完成态"（如 Executed）。
5. **相同状态语义 → 相同颜色 + 相同图标**。
6. **表格排序以拖拽（reorderable）为主**修改 order_column，表单字段保留用于微调。
7. **时间（created/updated）和状态筛选默认加到每个表的 filters**。
8. **table 各方法顺序、封装方法（recordActions/toolbarActions/时间筛选）的用法固化成规范**。
9. **规范写入 `addons/support/resources/boost/guidelines/core.blade.php`**（不是直接改主应用 AGENTS.md；主应用 AGENTS.md 的 `wsmallnews/support rules` 区段即由该文件同步而来，改完需重新同步）。
10. **scopeable 按资源性质区分**：公用全局资源（member、user 等，一个站点只有一份）**不需要** scopeable 合并；可能多份存在的资源（post、product、link、navigation 等）**必须**在 CreatePage `mutateFormDataBeforeCreate` 合并 scopeable。
11. **迁移可直接改老文件（仅限本次计划的执行原则，不写入 core 规范）**：本项目开发库可 `migrate:fresh` 重建，调整 order_column 等字段定义时直接修改原迁移 stub（及 app 侧副本），**不创建 alter 迁移**。core.blade.php 面向外部引用方（有存量数据库），规范中只定义新表的标准字段写法，不含"改老迁移"的说法。
12. **order_column 逻辑变化后，必须核对所有前端查询排序**（尤其各包 `src/Livewire/` 下的组件查询），见 §3 前端排序盘点表。
13. 本次先出计划；规范正文（§4）落地是第一个执行步骤。
14. **资源创建规范**：新资源一律采用五层结构（Posts/Links 为范本，详见 §4.6）；**「继承即自建 Pages」**写入规范（Pages 的 `$resource` 写死绑定 final 类）；ViewPage + widgets 属 post 特有增强，规范中标注为**按资源类型可选**；**tag 相关（`getTagType()` / TagResource 委托）不写入规范**——support 现有 tags 基于 spatie，不符合项目规范、待重写，当前视为不可用；**软删除相关配置仅在表有软删除字段时遵守**（Links 为无软删除范本：无 `withoutGlobalScopes`、无 TrashedFilter、无 ForceDelete/Restore）。

## 3. 技术调研结论（源码已验证，直接引用勿再查）

> 调研基于 `vendor/filament/tables` 当前安装版本源码，执行时无需重新验证。

- **`reorderable(string $column, ?bool $condition, ?string $direction)`** 有第三个参数 `direction`（`CanReorderRecords.php`）。拖拽保存时 `reorderTable()` 用 CASE 表达式把**当前查询命中的全部记录**按视觉顺序批量重写为 `1..N`。
- **`direction` 必须与 `defaultSort` 方向一致**：desc 时 Filament 会 reverse 写入序号（视觉第一行得 N），否则拖完顺序颠倒。当前所有表都漏传 direction（默认 asc）——**defaultSort 为 desc 的表（ProductTable）拖拽语义实际是错的**，必须补 `direction: 'desc'`。
- 拖拽重排时分页自动失效（一次性展示全部记录），全量重编号，因此旧数据的"100/60"刻度与拖拽后的"1..N"刻度不会长期混用。
- **`defaultSort($column, $direction)`** 支持 'asc'/'desc'，任意列可用。
- **`ToggleButtons` 的 `->inline()` / `->grouped()`** 在 v5 均存在，LinkForm 已在用。
- 现有迁移统一为 `$table->unsignedInteger('order_column')->nullable()->comment('排序')` + 索引，共 5 张表（category_types、links、posts、navigation_types、products）。

**前端 order_column 排序盘点表**（S4/S9 改造时逐一核对，不得破坏）：

| 位置 | 排序方式 | 与后台 desc 方案的关系 |
|---|---|---|
| `cms/Livewire/Components/Post/Posts.php:91` | `orderBy('order_column', 'desc')` | ✅ 一致 |
| `cms/Livewire/Components/Post/IndexPosts.php:19` | `orderBy('order_column', 'desc')` | ✅ 一致 |
| `cms/Livewire/Components/Footer.php:27`（links） | `Link::ordered()` = order desc + id desc | ✅ 一致 |
| `cms/Models/Link.php:64`（ordered scope） | order desc + id desc | ✅ 一致 |
| `product/Models/Attribute.php:17`（children） | order **desc** + id asc | ⚠️ 与 product 包内其他关联排序方向不一致（S9 核对统一） |
| `product/Models/Spec.php:30` / `Product.php:135,140`（specs/variants/children） | order asc + id asc | ⚠️ 同上 |
| `category/Livewire/Components/Categories.php:61`、`cms/Livewire/{Footer,Navigation}` | nestedset `defaultOrder()` | 按 `_lft` 排序，与 order_column 无关，不受影响 |

**结论**：cms 前端全部已是 order desc，后台 defaultSort 改 desc 前后台一致，S4 放心改；product 包内 Attribute(desc) 与 Spec/Product(asc) 方向分裂，S9 一并核对。

## 4. 目标规范正文（S0 原样写入 `addons/support/resources/boost/guidelines/core.blade.php`）

> 本节即规则草案。写入位置：core.blade.php 内、「组件工厂」小节之后，作为新的 `### Filament Resource 风格统一` 小节（面向所有引用 support 包的项目，措辞通用化）。写完后同步主应用 AGENTS.md（Boost 同步命令用 `php artisan list` 查 `boost:*`；若无自动命令则把同内容贴进 AGENTS.md 的 `wsmallnews/support rules` 区段）。

### 4.1 order_column 统一处理

| 层 | 规则 |
|---|---|
| 迁移 | 新表标准定义：`unsignedInteger('order_column')->nullable()->comment('排序')` + 索引 |
| 模型 | use `Wsmallnews\Support\Models\Concerns\HasOrderColumn`（新增，见 §5.1）：`creating` 时若 order_column 为空，在同一 scope 内自动填充 `max(order_column) + 1`。**不要**在 CreatePage 用 id 回填（id 保存前不可得、与拖拽 1..N 刻度不一致） |
| 表单 | 用 `FormComponents::orderColumnInput()`（新增）：integer + min:0 + helperText"留空自动分配到末尾"。保留字段供微调，**不隐藏** |
| 表格 | 必须同时设置：`->reorderable('order_column', direction: <方向>)` + `->defaultSort('order_column', <方向>)`，**两处方向必须一致** |

**默认排序方向按业务域**：

| 业务域 | 方向 | 理由 | 适用表 |
|---|---|---|---|
| 内容型（新内容优先展示） | `desc` | 与前端 `ordered` scope / Livewire 组件查询（order desc）一致，新记录（max+1）排最前 | posts、links、products |
| 配置型（顺序稳定敏感） | `asc` | 新记录追加到末尾，不扰动既有顺序 | category_types、navigation_types、tags |

> 想按时间倒序看内容时：order_column desc 即可（自动填充保证新=大）；不要切到 id/created_at 排序再拖拽（拖拽只按当前 defaultSort 列重写，混排会乱）。
> 改动 order_column 逻辑时，必须核对前端各 Livewire 组件的排序查询（见本计划 §3 盘点表）。

### 4.2 Table 规范

**方法顺序**（LinksTable/PostsTable 为范本，全 addon 固化）：

```php
return $table
    ->columns([...])
    ->reorderable('order_column', direction: '...')   // 仅含 order_column 的表
    ->defaultSort('...', '...')
    ->searchPlaceholder(__(...))
    ->filtersFormWidth(Width::Medium)
    ->filters([...])
    ->recordActions([...ActionComponents::recordActions([...])])
    ->toolbarActions([...ActionComponents::toolbarActions([...])]);
```

**列顺序**：`id` → 主体识别列（modelColumn / 标题）→ 业务属性列 → 关联 / badge 列 → counter → `order_column` → `status` → `published_at` → `created_at` → `updated_at`。

**列内方法顺序**：`label()` → 业务增强（limit/copyable/badge/formatStateUsing）→ `searchable()/sortable()` → `alignCenter()` → `toggleable()`。

**列规范**：
- `id` 列统一 `->label('ID')->searchable()->sortable()->alignCenter()->toggleable()`。
- **status 列必须 `->badge()`**（枚举实现 HasColor/HasIcon 自动带色带图标）；时间列用默认格式，不额外定制。
- order_column 列 `->alignCenter()->toggleable()`，不加 sortable（排序由 defaultSort 承担）。

**筛选规范**（每个表默认具备）：
- 有状态枚举的表：`FilterComponents::statusFilter(XxxStatus::class)`（新增工厂，见 §5.2）放最前；
- 一律追加 `...FilterComponents::createUpdateRangeFilter()`；
- 软删除表最后加 `TrashedFilter::make()`。
- filters 顺序：业务筛选（status/flag/自定义）→ 时间区间 → Trashed。

### 4.3 Form 规范

**布局规则（统一平铺，无例外）**：
- **禁止使用 `Schemas\Components\Flex` 侧边栏布局**（含复杂表单）。
- **表单所有字段必须在 Section 中**（不裸放 schema 顶层）。
- **只有一个 Section** 时：order_column、status 等基础字段直接放该 Section 内。
- **多个 Section** 时：order_column、status 作为基础字段放**最上面的 Section**（业务主体 Section）。
- Section 用 `->columns(2)->columnSpanFull()`，长内容字段（富文本/编辑器/上传）`->columnSpanFull()` 或 `->columnSpan(1)` 按宽窄安排。
- 范本：`LinkForm`（改造后）；复杂表单平铺参考 S5 改造后的 `PostForm`。

**组件规范**：
- status 一律 `FormComponents::statusToggleButtons(XxxStatus::class)`（新增工厂 = `ToggleButtons::make('status')->inline()->grouped()->options($enum)->default(枚举默认)`）；**禁止** Radio/Select 做状态字段。
- order_column 一律 `FormComponents::orderColumnInput()`。

### 4.4 Enum 状态色板与图标（核心映射表）

**色板语义原则**：primary = 默认正常态；success = 流程成功完成态；gray = 隐藏/草稿/取消（弱化）；warning = 待处理/待审核；danger = 禁用/拒绝/失败；info = 中间通知态（少用）。

| 语义 | 颜色 | 图标（一律 `Heroicon::Outlined*` 枚举常量） |
|---|---|---|
| 正常 Normal | `primary` | `OutlinedCheckCircle` |
| 已发布 Published | `primary` | `OutlinedEye` |
| 上架 Up | `primary` | `OutlinedArrowUp` |
| 隐藏 Hidden | `gray` | `OutlinedEyeSlash` |
| 草稿 Draft | `gray` | `OutlinedClipboardDocumentList` |
| 禁用 Disabled | `danger` | `OutlinedNoSymbol` |
| 下架 Down | `danger` | `OutlinedArrowDown` |
| 未审核 Unaudited | `warning` | `OutlinedDocumentCheck` |
| 已拒绝 Rejected | `danger` | `OutlinedShieldExclamation` |
| 已执行 Executed | `success` | （维持现状） |
| 待执行 Pending / 已取消 Cancelled / 失败 Failed | warning / gray / danger | （维持现状） |

**硬性规则**：
- 图标禁止 solid 常量（`Heroicon::Eye`）与字符串写法（`'heroicon-m-arrow-long-up'`），一律 `Heroicon::Outlined*`。
- 新枚举结构照抄 LinkStatus：`enum XxxStatus: string implements HasColor, HasIcon, HasLabel` + `use EnumHelper`，match 三件套（getLabel/getColor/getIcon）。
- "上架/下架"（Up/Down）是货架语义不是"正常/禁用"，图标保留 Arrow 体系。

### 4.5 补充统一项（结构惯例，固化成文）

1. **资源目录与创建规范**：见 §4.6（五层结构，Posts/Links 为范本）。
2. **scopeable 按资源性质**：公用全局资源（member、user 等）**不 use Scopeable、不加 scope 合并**；可多实例资源（post、product、link、navigation 等）模型 use Scopeable，且 CreatePage 必须 `mutateFormDataBeforeCreate` 合并 scopeable（CreateLink/CreatePost 模式）。
3. **翻译 key 命名**：`<资源>_table.<field>` / `<资源>_form.<field>` / `<资源>_status.<case>` / `<资源>_table.search_placeholder` / `<资源>_resource.model_label` 等；**禁止硬编码中文 label**（现存 ProductForm 的 `'排序'` 要清理）。
4. **空状态**：暂不强制统一（后续可扩展 `emptyStateHeading` 工厂）。

### 4.6 资源创建规范（五层结构）

> 范本：`addons/cms/src/Filament/Resources/Posts`（含软删除 + ViewPage 增强）与 `.../Links`（无软删除、最小集）。新资源一律按此结构创建。

```
Resources/Xxxs/
├── BaseResource.php        # abstract：本包资源的全部默认值
├── XxxResource.php         # final：页面路由绑定 + 配置模式入口
├── Pages/
│   ├── ListXxxs.php        # ListRecords（header CreateAction）
│   ├── CreateXxx.php       # CreateRecord（scopeable 资源须合并 scopeable）
│   ├── EditXxx.php         # EditRecord（软删除表加 ForceDelete/Restore）
│   └── ViewXxx.php         # 可选，按资源类型增强（见下）
├── Schemas/XxxForm.php     # 表单（静态 configure，与 Resource 解耦）
└── Tables/XxxsTable.php    # 表格（静态 configure，与 Resource 解耦）
```

**BaseResource（abstract）—— 默认值层，不定义 `getPages()`**：

- 资源身份：`$navigationIcon` / `$activeNavigationIcon`（Outlined + 实心成对）、`$slug`、`$recordTitleAttribute`、`$navigationSort`；
- `getModel()` 一律走包 Utils（模型经 `config('包名.models.xxx')` 可替换——这是资源可被其他包复用的前提）；
- 标签方法用 `static::$xxx ?? 翻译key` 模式（子类可用属性覆盖）；
- `form()` / `table()` 委托给同目录 `Schemas/XxxForm` / `Tables/XxxsTable` 的静态 `configure()`；
- `getEloquentQuery()` = `applyScopeableToQuery(parent::getEloquentQuery())`（仅 scopeable 资源）；**表有软删除字段时**才追加 `->withoutGlobalScopes([SoftDeletingScope::class])`，无软删除不加（Links 范本）。

**XxxResource（final）—— 注册与配置层，不定义默认值**：

- 只做三件事：`getPages()` 路由绑定；use `CanBeConfigured` + `$configurationClass = ResourceConfiguration::class`（`form()`/`table()` 先查插件 customProperties 闭包，有则用调用方的，无则回落 parent）；`getEssentialsPlugin()` 返回本包插件实例。

**Pages —— 行为层**：

- 全部 use support 的 `Pages\Scopeable`；`$resource` 写死指向 final 类。
- **「继承即自建 Pages」**：Pages 的 `$resource` 绑定 final 类（Filament generator 惯例），其他包 `extends BaseResource` 自定义资源时**必须自建 Pages**（把 `$resource` 指向自己的类）；不想自建就直接注册 `XxxResource::class` 并用插件 customProperties 闭包覆盖 form/table。
- **ViewPage + widgets（评论/浏览等 footer widget 装配）是 post 特有增强，按资源类型可选**，不属于标准结构必选项。

**软删除条件规则**（仅表有软删除字段时遵守，无则全部省略）：

| 层 | 有软删除 | 无软删除 |
|---|---|---|
| BaseResource `getEloquentQuery()` | 追加 `withoutGlobalScopes([SoftDeletingScope])` | 只 `applyScopeableToQuery()` |
| Table filters | 末尾 `TrashedFilter::make()` | 不加 |
| EditPage header actions | Delete + ForceDelete + Restore | 只 Delete（或按需） |
| Table actions | Delete/ForceDelete/Restore + 对应 Bulk | Delete + DeleteBulk |

## 5. support 包基础设施改造（规范落地载体）

### 5.1 新增模型 trait `HasOrderColumn`

- 位置：`addons/support/src/Models/Concerns/HasOrderColumn.php`。
- `creating` 钩子：order_column 为空时取 `max(order_column) + 1` 填充。
- 查询定制（2026-09-09 按用户确认由数组改为 query 方式）：模型可覆盖 `modifyOrderColumnQuery(Builder $query): Builder` 自由拼接条件（如 `return $query->where('team_id', $this->team_id)`），默认实现返回原 query（全表计数；序号全局单调，组内排序依然正确，因此各包模型默认**不覆盖**）。
- 测试：创建留空自动填充 max+1；显式传值不覆盖；覆盖 modifyOrderColumnQuery 后隔离正确。

### 5.2 工厂方法扩展

| 工厂 | 位置 | 内容 |
|---|---|---|
| `FormComponents::statusToggleButtons(string $enumClass, string $field = 'status', ?string $label = null)` | FormComponents | ToggleButtons + inline + grouped + options($enum) + default(枚举第一 case)；label 缺省取翻译 `form_components.status.label` |
| `FormComponents::orderColumnInput(string $field = 'order_column', ?string $label = null)` | FormComponents | TextInput + integer + min:0 + label 缺省取翻译 + helperText（翻译 key） |
| `FilterComponents::statusFilter(string $enumClass, string $field = 'status', ?string $label = null)` | FilterComponents | SelectFilter + options($enum)，label 缺省取翻译 |

每个工厂写测试（断言组件类型、默认配置）。

## 6. 各包改造清单（文件级，现状 → 目标）

> 改动分三类：【C】颜色/图标、【T】表格、【F】表单。已并入 §7 步骤，按步骤提交。

### support

| 文件 | 改动 |
|---|---|
| `resources/boost/guidelines/core.blade.php` | 【S0】写入 §4 规范 |
| 主应用 `AGENTS.md` | 【S0】同步 support 区段（Boost 机制或手动） |
| `src/Models/Concerns/HasOrderColumn.php` | 【S1】新增 |
| `src/Filament/Forms/FormComponents.php` | 【S2】新增 statusToggleButtons / orderColumnInput |
| `src/Filament/Filters/FilterComponents.php` | 【S2】新增 statusFilter |
| `resources/lang/{zh_cn,en}/support.php` | 【S2】新增 order helperText 等翻译 |
| `src/Filament/Resources/Tags/Tables/TagsTable.php` | 【S9 顺带】方法顺序核对（tags 无 status，跳过状态项） |

### cms

| 文件 | 改动 |
|---|---|
| `src/Enums/LinkStatus.php` | 【S3·C】Normal: success→primary、Eye→OutlinedCheckCircle |
| `src/Enums/NavigationStatus.php` | 【S3·C】已是 primary；Eye→OutlinedCheckCircle |
| `src/Enums/NavigationTypeStatus.php` | 【S3·C】success→primary、Eye→OutlinedCheckCircle |
| `src/Enums/PostStatus.php` | 【S3·C】Draft: info→gray（Published/Hidden 已符合） |
| `src/Filament/Resources/Posts/Tables/PostsTable.php` | 【S4·T】status 列加 badge；defaultSort 改 desc + `reorderable('order_column', direction: 'desc')`；status 筛选换 statusFilter |
| `src/Filament/Resources/Links/Tables/LinksTable.php` | 【S4·T】status 列加 badge；defaultSort 改 desc + direction: 'desc'；status 筛选换 statusFilter |
| `src/Filament/Resources/NavigationTypes/Tables/NavigationTypesTable.php` | 【S4·T】status badge + statusFilter；保持 asc + direction: 'asc' |
| `src/Filament/Resources/Posts/Schemas/PostForm.php` | 【S5·F】**Flex 全部移除、平铺**（多 Section 时 order/status 放最上 Section）；order_column/status 换工厂 |
| `src/Filament/Resources/Links/Schemas/LinkForm.php` | 【S5·F】order_column/status 换工厂（布局已是范本，仅换组件） |
| `src/Filament/Resources/NavigationTypes/Schemas/NavigationTypeForm.php` | 【S5·F】换工厂 |
| `src/Filament/Pages/Navigation/Schemas/NavigationForm.php` | 【S5·F】（nestedset 页面）status 换 statusToggleButtons |
| `src/Models/{Post,Link,NavigationType}.php` | 【S5】use HasOrderColumn（Navigation 是 nestedset，其顺序由树管理，**不加**） |
| `database/migrations/*.stub` | 不动（nullable + 索引已符合；如需调整直接改 stub） |

### category

| 文件 | 改动 |
|---|---|
| `src/Enums/CategoryStatus.php` | 【S6·C】success→primary、Eye→OutlinedCheckCircle |
| `src/Enums/CategoryTypeStatus.php` | 【S6·C】success→primary、Eye→OutlinedCheckCircle |
| `src/Filament/Resources/CategoryTypes/Tables/CategoryTypesTable.php` | 【S6·T】status badge + statusFilter；保持 asc + direction |
| `src/Filament/Resources/CategoryTypes/Schemas/CategoryTypeForm.php` | 【S6·F】**Flex→平铺**（样板案例）；Radio→statusToggleButtons；order_column 换工厂 |
| `src/Filament/Pages/Category/Schemas/CategoryForm.php` | 【S6·F】（nestedset）status 组件统一 |
| `src/Models/CategoryType.php` | 【S6】use HasOrderColumn（Category 是 nestedset，不加） |

### user + member + comment

| 文件 | 改动 |
|---|---|
| `user/src/Enums/Status.php` | 【S7·C】success→primary（图标已 OutlinedCheckCircle，符合） |
| `member/src/Enums/MemberStatus.php` | 【S7·C】success→primary（图标已符合） |
| `member/.../Tables/MemberTable.php` | 【S7·T】补 createUpdateRangeFilter + statusFilter + status badge 核对 |
| `user/.../Tables/UserTable.php` | 【S7·T】核对 status badge 与筛选补齐 |
| 两包 Form | 【S7·F】status 换 statusToggleButtons |
| `comment/src/Enums/CommentStatus.php` | 【S8·C】图标 solid→Outlined（Eye→OutlinedCheckCircle、DocumentCheck/EyeSlash/ShieldExclamation 加 Outlined 前缀）；颜色已符合 |
| `comment/.../Tables/CommentTable.php` | 【S8·T】补时间筛选 + statusFilter（badge 已有则跳过） |
| comment 相关 Form/前端引用 | 【S8】全局搜 `getIcon()` 核对 solid→outlined 的视觉影响 |

### product

| 文件 | 改动 |
|---|---|
| `src/Enums/AttributeStatus.php` | 【S9·C】字符串图标→`Heroicon::OutlinedArrowUp/Down`（颜色 primary 已符合） |
| `src/Enums/ProductStatus.php` | 【S9·C】Hidden: info→gray（Draft 已 gray，Up 已 primary） |
| `src/Enums/VariantStatus.php` | 已符合，核对即可 |
| `src/Filament/Resources/Products/Tables/ProductTable.php` | 【S9·T】**补 `direction: 'desc'`**（现状 defaultSort desc + 无 direction，拖拽语义错误，本步重点）；status badge 核对 |
| `src/Filament/Resources/Products/Schemas/ProductForm.php` | 【S9·F】硬编码中文 label（'排序'）→ 翻译 key；order/status 换工厂；Flex 检查 |
| `src/Models/Attribute.php` / `Spec.php` / `Product.php` | 【S9】children/specs/variants 关联排序方向核对统一（Attribute desc vs Spec/Product asc，见 §3 盘点表） |

## 7. 执行步骤（按权重排序；每天 1+ 步，每步 ≤ 半天，独立提交）

> 权重含义：P0 = 依赖根（规范与基础设施，后续步骤全部依赖）；P1 = 核心业务包；P2 = 次要包与回归。
> 每步完成：包内 commit（addons 为 git 子模块，需再提交主仓库指针）→ 本文件勾选 → 记录 hash。

**P0（最高权重，最先做）**

- [x] **S0 规范落地 support**（~2.5h）：§4（含 §4.6 资源创建规范）写入 `addons/support/resources/boost/guidelines/core.blade.php`（「组件工厂」小节后，新增 `### Filament Resource 风格统一` 小节）；同步主应用 AGENTS.md support 区段（`php artisan list` 查 `boost:*` 同步命令，无则手动贴）。support 子模块 commit + 主仓库 commit（含本计划）。
  > ✅ 2026-09-09 代码已完成，**改动未提交，等用户审阅后自行提交**（提交约定变更：各步骤由用户提交，AI 不再代为 commit/push）。同步机制：`boost.json` agents 仅配 `claude_code`，`php artisan boost:update` 只重生成 CLAUDE.md，AGENTS.md 需用新生成的 CLAUDE.md 整体替换其 boost 区块（`<laravel-boost-guidelines>` 标签内），手写尾部保留。**顺带修复存量 bug**：core.blade.php「侧栏 grid」代码块未用 `@verbatim` 包裹，`@class([...$hasSidebar...])` 编译时变量未定义 → 整个文件渲染失败 → boost 静默丢弃 Support 区段（preference 同查无此问题）。

**P0（基础设施，S1、S2 可分两天）**

- [x] **S1 HasOrderColumn trait**（~2h）：`addons/support/src/Models/Concerns/HasOrderColumn.php` + 功能测试（留空自动填充 max+1 / 显式值不覆盖 / scope 隔离）；`vendor/bin/pint --dirty`；跑新测试；commit。
  > ✅ 2026-09-09 代码已完成，**改动未提交，等用户审阅后自行提交**；测试 `tests/Feature/Support/HasOrderColumnTest.php`（4 passed / 9 assertions，临时建表 + 匿名模型）。
- [x] **S2 表单/筛选工厂**（~3h）：`statusToggleButtons` / `orderColumnInput` / `statusFilter` 三方法 + zh_cn/en 翻译 + 测试；pint；commit。
  > ✅ 2026-09-09 代码已完成，**改动未提交，等用户审阅后自行提交**；测试 `tests/Feature/Support/FilamentComponentFactoriesTest.php`（6 passed / 19 assertions）；Support 目录回归 86 passed。注意：support 包中文翻译实际目录为 `resources/lang/zh_CN/`（大写 N），计划中写的 `zh_cn` 在 Windows 大小写不敏感文件系统上会静默落到同一文件，其他包操作翻译时留意。

**P1（核心业务包，cms 三步、category 两步）**

- [ ] **S3 cms 枚举统一**（~1h）：4 个枚举颜色/图标按 §4.4 映射表改；`grep -r "success\|primary\|info" tests/` 核对断言；跑 `php artisan test --compact --filter=Cms`；commit。
- [ ] **S4 cms 表格改造**（~3h）：PostsTable / LinksTable（badge + desc + direction + statusFilter）、NavigationTypesTable（badge + statusFilter，asc + direction）；核对 §3 前端排序盘点表（cms 前端已全 desc，一致）；跑 cms 测试 + 手动开面板看顺序；commit。
- [ ] **S5 cms 表单改造**（~3h）：**PostForm 去 Flex 平铺**（多 Section，order/status 放最上 Section）、LinkForm / NavigationTypeForm 换工厂、NavigationForm（nestedset）status 组件；Post/Link/NavigationType 模型 use HasOrderColumn；跑测试；commit。
- [ ] **S6a category 枚举+表格**（~1.5h）：2 个枚举换色换图标；CategoryTypesTable badge + statusFilter + direction；跑测试；commit。
- [ ] **S6b category 表单**（~1.5h）：**CategoryTypeForm 去 Flex 平铺**（样板案例）；CategoryForm（nestedset）status 组件；CategoryType 模型 use HasOrderColumn；commit。

**P2（次要包）**

- [ ] **S7 user + member**（~2h）：两枚举 success→primary；两 Table 补筛选/badge 核对；Form status 换工厂；跑测试；commit。
- [ ] **S8 comment**（~2h）：CommentStatus 图标 outlined 化（颜色不动）；CommentTable 补时间筛选 + statusFilter；全局搜枚举 `getIcon()` 引用核对前端视觉；跑 comment 测试；commit。
- [ ] **S9 product**（~3h）：AttributeStatus 图标常量化、ProductStatus.Hidden→gray、ProductTable 补 `direction: 'desc'`（重点）、ProductForm 翻译清理 + 换工厂、Attribute/Spec/Product children 排序方向核对统一、TagsTable 方法顺序顺带核对；跑 product 测试；commit。

**P2（收尾）**

- [ ] **S10 全量回归**（~1h）：`php artisan test --compact` 全量；逐面板人工核对（badge 颜色、拖拽方向、筛选齐全、表单平铺无侧栏）；最后统一检查各子模块指针已提交。

## 8. 风险与注意点

1. **defaultSort 方向翻转**（posts/links asc→desc）：现有数据 order 刻度（links 演示数据 100→60）展示顺序会颠倒——但这恰好与前端一致（前端本来就是 desc）。可接受直接翻转；如需整齐刻度可拖一次重排。**S4 时先看数据再定，不重排也不阻塞**。
2. **reorder + 软删除**（PostsTable 有 TrashedFilter）：重排 SQL 基于 `getQuery()`，S4 时验证是否会把回收站记录一并重编号（预期会，影响可忽略但需知晓）。
3. **CommentStatus 图标 solid→outlined**：前端 Livewire 评论组件视图可能直接引用枚举图标，S8 改前全局搜引用点。
4. **颜色改动影响既有测试**：S3 改枚举前先 grep 测试断言。
5. **HasOrderColumn 并发**：max+1 在后台管理低并发场景足够，不加锁；将来冲突再升级。
6. **子模块提交两步走**：包内 commit + 主仓库指针 commit 都要做（filament-nestedset Release 规范有前车之鉴）。
7. **core.blade.php 同步**：support 规则源文件改后主应用 AGENTS.md 需同步；S0 时确认 Boost 的同步机制（命令或手动）。

## 9. 待确认决策点（已给推荐默认，执行中如遇异议再问用户）

| # | 决策点 | 推荐默认（不问即按此执行） |
|---|---|---|
| 1 | order 自动填充：模型 trait max+1 vs CreatePage 填 id | **trait max+1**（刻度与拖拽一致） |
| 2 | Normal 图标：严格统一 CheckCircle vs 分域保留 Eye | **严格统一 OutlinedCheckCircle** |
| 3 | Disabled 色：danger vs gray | **danger**（维持现状） |
| 4 | Draft 色：info vs gray | **gray**（统一弱化色） |
| 5 | 内容型表（posts/links/products）defaultSort desc | **desc**（前端已全 desc，前后台一致） |
| 6 | product 关联排序方向分裂（Attribute desc vs Spec/Product asc） | **S9 核对后统一，方向跟随该表 defaultSort 方向（product 域 = desc）** |

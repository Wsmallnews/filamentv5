# filament-nestedset 侧边栏树组件（SidebarTree）执行方案

> 状态：待审阅（2026-10-14）
> 结论：**应该做**。做成 filament-nestedset 包内的 panel 专用 Livewire 树组件，media 目录树作为第一个接入方（P0），cms posts 分类树作为第二个接入方（P1）。

## 一、背景与现状

media 素材管理页的左侧目录树（约 200 行：nav DOM + 计数 + ⋯ 菜单 CRUD + 筛选联动）目前全部写在 `sn-media` 包里；cms posts 将来需要几乎同样的左树（分类筛选文章列表）。两处需求 = 同一个「嵌套集侧边栏树」，应下沉到 filament-nestedset 包（它已具备：NodeTrait 查询体系、KalnoyNestedsetSelectTree 字段、管理页面/Widget 基类）。

**包独立性硬约束**（.ai/rules/filament-nestedset-src.md）：不依赖 support 包——组件内空态/样式必须自建，不得使用 `x-sn-support::empty` 等。

## 二、Filament 官方能力研究结论（源码 + 官方文档核实）

### 2.1 Sub-navigation（页面左侧子导航）

**机制**（`vendor/filament/filament` 源码核实）：

- 任意 Filament 页面（Resource 页、自定义 Page）覆写 `getSubNavigation(): array`，返回 `NavigationItem | NavigationGroup` 数组；页面视图（`components/page/index.blade.php`）检测到非空后，在 `fi-page-main` 左侧渲染 `fi-page-sub-navigation-sidebar`。
- 位置由 `subNavigationPosition` 控制：`Start`（左，默认）/ `End`（右）/ `Top`（标签页）。面板级、Resource 级、Cluster 级都可设置。
- Resource 的官方用法是 **`getRecordSubNavigation(Page $page)`**——围绕**同一条记录**的多页面切换（View/Edit/Manage 系列页面），`$page->generateNavigationItems([...])` 生成项。
- `NavigationItem` 能力：label/icon/url/badge/activeIcon/isActive（按 URL 匹配）；`NavigationGroup` 可分组 + collapsible——**平铺或一层分组，无多级缩进树**。
- 官方文档明确：需要第三层导航时应考虑 clusters（即 sub-navigation 自身不支持深层树）。
- 关键渲染细节：Start 侧栏区块（含 `PAGE_SUB_NAVIGATION_START_BEFORE/AFTER` 两个 render hook）被 `@if ($subNavigation)` 守卫——**getSubNavigation() 非空才会渲染侧栏区**。render hook 支持 scope（可精确到某个 Resource）。

**定位结论**：sub-navigation 是「页面/记录导航容器」，不是「数据筛选控件」。它的**侧栏容器（位置、样式、响应式）可以被我们复用**，但树形内容必须自定义。

### 2.2 Clusters（集群）

**机制**（`vendor/filament/filament/src/Clusters/Cluster.php`）：

- Cluster 本身是一个 Page：拥有独立 slug/路由前缀/面包屑，把多个 Resource + Page 归组（`getClusteredComponents()`）；cluster 页面自动用这些组件生成左侧导航（`Cluster::getSubNavigation()`）。
- 归入 cluster 的 Resource 路由变为 `/{cluster-slug}/{resource-slug}`。

**定位结论**：clusters 解决「多个资源/页面归为一组、给组一个独立侧栏」（例如本项目"商城"聚合 产品/订单/附加项）。它**与数据筛选无关**，不适合分类树场景。本项目后续做资源分组时可再考虑。

## 三、新组件设计：SidebarTree（filament-nestedset 包内）

### 3.1 形态选型

**panel 专用 Livewire 组件**（非前台组件、非 Widget）：

- 注册名 `sn-filament-nestedset-sidebar-tree`，可从任意 blade（页面视图、render hook、Widget）以 `<livewire:>` 嵌入——posts 走 render hook 时这是唯一灵活形态。
- CRUD 需要 Filament Action（modal）→ 组件 `use InteractsWithActions + HasSchemas`，视图尾部带 `<x-filament-actions::modals />`（comment 包面板组件先例，子组件 Actions 与宿主页面 Actions 互不干扰）。

### 3.2 文件清单

```
addons/filament-nestedset/
├── src/Livewire/Components/SidebarTree.php                    # 组件本体
├── src/Livewire/Concerns/HasSidebarTreeActions.php            # createChild / rename / delete 三 Action
├── resources/views/livewire/components/sidebar-tree.blade.php # 树视图（自 media 迁移）
├── resources/lang/{zh_CN,en}/nestedset.php                    # 补 key
└── resources/css/index.css                                     # 如有新工具类（应基本复用现有）
```

### 3.3 组件 API（Livewire props）

| Prop | 类型/默认 | 说明 |
|---|---|---|
| `model` | string（必填） | NodeTrait 模型类名 |
| `scope` | ?array | 嵌套集 scoped 属性（如 `['team_id' => 1]`），与 `getScopeAttributes()` 对齐 |
| `manageable` | bool=false | true：根部 `+` + 行内 `⋯`（创建子目录/重命名/删除）；false：纯展示 |
| `selectedId` | ?int | 选中节点高亮 |
| `maxDepth` | ?int | 层级上限（默认读模型 `MAX_DEPTH` 常量再回落包配置） |
| `labelField` | string='name' | 节点标题列 |
| `countColumn` | ?string | 计数外键列（如 media 的 `directory_id`）；空则不显示计数 |
| `countScope` | ?Closure | 计数基础查询扩展（如附加 team 条件/软删除） |
| `showAll` / `allCount` | bool / ?int | 顶部「全部」哨兵行（media 用） |
| `showUncategorized` / `uncategorizedCount` | bool / ?int | 「未归类」哨兵行（media 用；posts 关） |
| `headerLabel` | ?string | 树标题（默认翻译「目录」） |
| `eventName` | string | 选择事件频道，默认 `sn-filament-nestedset-tree-selected` |

子类可覆写的钩子方法（复用包既有模式）：`modifyNodeQuery()`、`nodeLabel(Model $node)`、`formSchema()`（CRUD 字段，默认单 `name` 输入）、`afterCreate/afterRename/afterDelete()`。

### 3.4 数据与交互契约

- **数据**（media 现有逻辑上移，全部为嵌套集原生能力）：`withDepth()`（单 SQL 深度，0 基）+ `defaultOrder()`（`_lft` 树序）+ `toFlatTree()`（平铺父先子后）+ 计数单条聚合查询后按 `_lft/_rgt` 区间向上累加。
- **点击节点**：`$dispatch(eventName, ['id' => nodeId])`；「全部」派发 `id=null`、「未归类」派发 `id=0`。宿主页面用 `#[On]` 接收后设置自身筛选属性并过滤查询（符合 .ai/rules/component-events.md：`sn-` 前缀、payload 标量、宿主 PHP 监听）。
- **CRUD**（manageable）：组件内 `mountAction`（名称 `nestedsetTreeCreateChild/Rename/Delete`，带参数）；层级校验 `ancestors()->count() + 1 >= maxDepth` 拒绝；删除确认含子孙文件数提示由 `countColumn` 计算生成；动作完成组件刷新并 dispatch `sn-filament-nestedset-tree-updated`（宿主可选监听刷新列表计数）。
- **样式**：迁移 media 树当前 DOM——单行、计数紧贴菜单（4px）、`⋯` ActionGroup（dropdownTeleport + bottom-end）、恒显哨兵行、空态自建（简洁图标+文案，不用 support 组件）。

### 3.5 包内注册

`FilamentNestedsetServiceProvider::packageBooted()` 增加 `Livewire::component('sn-filament-nestedset-sidebar-tree', SidebarTree::class)`；JS 无需新增（树无 Alpine 组件依赖，ActionGroup 自带）。

## 四、Media 接入改造（P0，组件首个验证场）

1. `media.blade.php` 左树整块替换为：
   ```blade
   <livewire:sn-filament-nestedset-sidebar-tree
       :model="Wsmallnews\Media\Models\MediaDirectory::class"
       :scope="['team_id' => $this->resolveTeamId()]"
       manageable
       :selected-id="$libraryDir"
       count-column="directory_id"
       :show-all="true" :all-count="$this->typeCounts['all']"
       :show-uncategorized="true" :uncategorized-count="$this->uncategorizedCount"
   />
   ```
2. `MediaPage`：删除 `directoryMenuActions()` 与 `snMediaDirectory{Create,Rename,Delete}Action()`（组件自带）；新增 `#[On('sn-filament-nestedset-tree-selected')]` 接收 → `filterByDir($id)`。
3. 迁移包语言 key；media 测试中目录 CRUD 相关用例改走组件（或保留 MediaPage 层测试仅覆盖页面联动）。
4. 预期收益：MediaPage/blade 减约 200 行重复逻辑；行为与现有完全一致。

## 五、Posts 分类树嵌入方案（P1）

三条路线对比：

| 路线 | 做法 | 优点 | 缺点 |
|---|---|---|---|
| A. 纯官方平铺 | `PostResource::getRecordSubNavigation()` 改造——按分类生成 NavigationItem（全部/一级项+badge 计数，二级塞 NavigationGroup），URL 带 `?category=`，`ListPosts::getEloquentQuery()` 读参过滤 | 零自定义组件、纯官方能力 | 无树形缩进；点击整页跳转（非 Livewire 局部更新）；三级以上表达力弱 |
| **B. 官方侧栏容器 + hook 注入树组件（推荐）** | 见下 | 树形完整；点击仅 Livewire 更新表格；视觉挂在官方侧栏容器（响应式/暗黑/间距全继承） | 需要 panel 级 render hook + scope 精确 |
| C. 覆写 `ListPosts::content()` 两列 Schema | `View` 组件包树 + `EmbeddedTable` 并排 | 不依赖 hook | 脱离官方侧栏容器；表格组件宽度适配有风险 |

### 推荐路线 B 的实现步骤

1. **激活侧栏容器**：`PostsBaseResource` 的 `ListPosts` 覆写 `getSubNavigation(): array`，返回单项 `NavigationItem::make('all')->label(全部文章)->url(ListPosts::getUrl())`——非空即激活 `fi-page-sub-navigation-sidebar`。
2. **注入树**：`AdminPanelProvider`（或 PostsPlugin）注册：
   ```php
   FilamentView::registerRenderHook(
       PanelsRenderHook::PAGE_SUB_NAVIGATION_START_BEFORE,
       fn () => view('sn-cms::posts.category-tree', ['resource' => PostsResource::class]),
       scopes: ListPosts::class,   // 仅文章列表页渲染
   );
   ```
   `sn-cms::posts.category-tree` 视图内：
   ```blade
   <livewire:sn-filament-nestedset-sidebar-tree
       :model="Wsmallnews\Category\Models\Category::class"
       :scope="['scope_type' => 'sn-cms', 'scope_id' => 0, 'team_id' => ...]"
       :selected-id="$categoryFromUrl"
       event-name="sn-cms-posts-category-selected"
   />
   ```
3. **联动过滤**：`ListPosts` 增加 `#[Url] public ?int $category` + `#[On('sn-cms-posts-category-selected')]` 设置属性；`getEloquentQuery()` 按 `$category`（含子孙，`inDirectories` 同款区间过滤）收窄。`#[Url]` 保持链接可分享/回显选中。
4. posts 数据源待确认项见 §8-3。

## 六、分期计划

- **P0（先行，1 个工作单元）**：SidebarTree 组件 + Actions + 视图 + 包测试 → media 接入替换 → media 50 用例回归 + 浏览器全链路验证（树渲染/CRUD/筛选/层级限制/计数/选择联动）。
- **P1（P0 验收后）**：posts 接入（路线 B）+ 分类数据源对接 + 测试 + 浏览器验证。
- **P2（可选增强）**：树内搜索过滤、展开态 `$persist`、拖拽换序（nestedset 原生 `appendToNode/insertBefore/insertAfter`）、移动端抽屉形态。

## 七、测试计划

包级新增（`addons/filament-nestedset/tests`）：
1. 渲染：树结构/深度缩进/计数（含子孙累加）/哨兵行；
2. manageable=true：创建子目录/重命名/删除（含子孙计数提示）走组件 Action；层级超限拒绝；
3. manageable=false：无任何管理入口；
4. 事件：点击派发 `sn-filament-nestedset-tree-selected`（id/null/0 三态）。

media 回归：现有 50 用例（目录 CRUD 用例迁移为组件级或页面联动级）。

## 八、风险与待确认

1. **多实例事件隔离**：同页多树（如未来表格上方+侧栏）用 `eventName` prop 分频道。
2. **包独立性**：空态/样式自建，不引 support（规则硬约束）。
3. **posts 分类数据源**：需确认文章与 category 包 `Category` 的关联方式（`category_id` 外键？多对多？scope_type 取值）——影响 `countColumn` 与 `inCategories` 过滤实现。当前 cms 包 PostsTable 已有 `modelColumn/morphColumn`，categories 关联待查证后在 P1 开工前确认。
4. **hook scope 精确性**：render hook 以页面类 scope，仅 ListPosts 生效；`PAGE_SUB_NAVIGATION_START_BEFORE` 在 `$subNavigation` 非空时才渲染——getSubNavigation 必须返回非空（方案已含）。
5. **选中态与 URL 同步**：树组件 `selectedId` 由宿主传入（单向），URL 参数驱动回显；树点击只 dispatch 不改 URL（宿主决定是否 `#[Url]`）。

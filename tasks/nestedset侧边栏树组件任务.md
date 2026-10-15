# 任务：filament-nestedset 侧边栏树组件（SidebarTree）

> 状态：方案已定稿待执行（方案全文见 `.zcode/plans/plan-nestedset-sidebar-tree.md`，含官方能力研究结论与完整设计）
> 来源会话：2026-10-14（media 素材管理页修复轮之后定稿）
> 阶段：**P0 先行（SidebarTree 组件 + media 接入）**；P1（posts 接入）待 P0 验收后进行

## 一、任务目标

media 素材管理页左侧目录树约 200 行逻辑（nav DOM / 计数 / ⋯ 菜单 CRUD / 筛选联动）与 cms posts 未来的分类树需求重复，应下沉为 filament-nestedset 包内的通用组件：

- **panel 专用 Livewire 组件** `Wsmallnews\FilamentNestedset\Livewire\Components\SidebarTree`，注册名 `sn-filament-nestedset-sidebar-tree`
- 可管理（增删改查，manageable=true）/ 纯展示（false）双模式
- 点击节点 dispatch 事件，宿主页面 `#[On]` 接收筛选右侧内容
- 数据全部用嵌套集原生能力：`withDepth()` + `defaultOrder()` + `toFlatTree()` + 计数单查询按 `_lft/_rgt` 区间累加

## 二、P0 执行步骤（SidebarTree + media 接入）

1. **读规则**：`.ai/rules/index.md` → `filament-nestedset-src.md`（包不依赖 support，硬约束）、`filament.md`（Action 优先级）、`component-events.md`（事件规范）、`js.md`/`css.md`（如涉资产）
2. **组件本体** `src/Livewire/Components/SidebarTree.php`：
   - `use InteractsWithActions + HasSchemas`，视图尾带 `<x-filament-actions::modals />`
   - Props：`model`(必填) / `scope` / `manageable` / `selectedId` / `maxDepth` / `labelField='name'` / `countColumn` / `countScope` / `showAll`+`allCount` / `showUncategorized`+`uncategorizedCount` / `headerLabel` / `eventName`
   - 可覆写钩子：`modifyNodeQuery()` / `nodeLabel()` / `formSchema()`（默认单 name 输入）/ `afterCreate/afterRename/afterDelete()`
   - 事件：默认 `sn-filament-nestedset-tree-selected`（payload: id / null=全部 / 0=未归类）；变更后 dispatch `sn-filament-nestedset-tree-updated`
   - CRUD Actions（`HasSidebarTreeActions` concern）：createChild / rename / delete（requiresConfirmation + 含子孙计数提示），层级校验 `ancestors()->count() + 1 >= maxDepth`
3. **视图** `resources/views/livewire/components/sidebar-tree.blade.php`：**从 media.blade.php 左树整块迁移**——单行、计数紧贴 ⋯ 菜单（`right-9` 定位）、ActionGroup（`dropdownTeleport + dropdownPlacement('bottom-end')`）、哨兵行（全部/未归类）、空态**自建**（不能用 sn-support::empty，包独立性）
4. **注册**：ServiceProvider `packageBooted()` 加 `Livewire::component(...)`
5. **media 接入**：media.blade.php 左树替换为 `<livewire:sn-filament-nestedset-sidebar-tree ...>`；MediaPage 删 `directoryMenuActions()` + 三个 `snMediaDirectory*Action()`；加 `#[On]` 接收选择 → `filterByDir()`
6. **语言包**：nestedset 包 lang 补 key（zh_CN + en 同步，参考 media 语言包 key 级 diff 做法）
7. **构建**：如改 css/`js` → 包内 `npm run build` + 根目录 `php artisan filament:assets`；改 blade 工具类 → 根目录 `npm run build`
8. **验证**：pint --dirty → 包测试（新增：渲染/深度/计数/哨兵、manageable CRUD/超限拒绝、纯展示无入口、事件三态派发）→ media 50 用例回归 → 浏览器全链路（树渲染缩进 10/22/34px、计数含子孙、第 3 层无创建项、选择联动筛选、CRUD modal、未提交等审查）

## 三、P1 概要（posts 接入，路线 B：官方侧栏容器 + render hook）

1. `ListPosts::getSubNavigation()` 返回单项激活官方侧栏容器（`fi-page-sub-navigation-sidebar`）
2. Panel 注册 `PanelsRenderHook::PAGE_SUB_NAVIGATION_START_BEFORE` hook（scope 到 ListPosts）渲染树组件（分类 model + 自定义 eventName）
3. `ListPosts` 加 `#[Url] ?category` + `#[On]` 接收；`getEloquentQuery()` 按分类含子孙收窄
4. **前置待确认**：posts 与 category 包 Category 的关联方式（category_id 外键？多对多？scope_type 取值）——开工前先查证 cms 包

## 四、方案待用户确认的三件事

1. posts 分类数据源关联方式（用户可能直接告知）
2. P0 / P1 是否分批（建议分批）
3. 组件命名（建议 SidebarTree）

## 五、环境与浏览器测试备忘（上一轮验证踩坑汇总）

- **PHP**：`C:/Users/smallnews/.config/herd/bin/php.bat`（不在 PATH）
- **浏览器必须用 http://**（https 证书在 IAB 失败 → chrome-error）；登录 `test@example.com` / `password`
- **资产 URL 版本号恒定占位**：改 css/js 后浏览器需强刷——测试脚本先 `fetch(href, {cache:'reload'})` 预热所有 stylesheet 再 reload
- **侧栏遮罩坑**：视口 <1024px 且侧栏 open 时 `fi-sidebar-close-overlay` 盖住页面拦截 hover；测试用 ≥1024 视口或先关侧栏
- **Filament v5 关键行为**（已验证）：
  - 下拉触发是 `mousedown` 不是 click（合成事件测不了，用 `dispatchEvent(new MouseEvent('mousedown', {bubbles:true, button:0}))`）
  - ActionGroup 下拉面板：`dropdownTeleport()`（fixed 策略防 overflow 裁剪）+ `dropdownPlacement('bottom-end')`
  - `mountAction('name')` 按 `{name}Action()` 方法约定解析（方法需声明 `: Action` 返回类型）；mount 后的 modal 标题等配置必须写在 action 方法闭包里（渲染期 clone 的配置不参与 mount）
  - 分页组件是**容器查询**：必须占满宽度（`class="w-full"`），否则降级成裸形态
  - 面板 `->spa()`：带 href 的下载动作会被 wire:navigate 拦截，用 `->alpineClickHandler('window.location.href = ...')` 替代
  - 树/内容区 flex 收缩：flex 子项记得 `min-w-0`，否则被内容撑宽
- **嵌套集用法**（media 已落地）：`withDepth()`（0 基）/ `defaultOrder()` / `toFlatTree()`；层级校验 `ancestors()->count() + 1 >= MAX_DEPTH`；表无 order_column（nestedset 用 `_lft/_rgt`）

## 六、工作区状态（截至交接）

- 本会话所有 media 修复与优化**均未提交**（addons/media、addons/filament-nestedset 为子模块，主仓另有迁移/测试文件改动）——按规则等用户审查后提交；建议开工本任务前先提交 media 轮改动，保持基线干净
- media 目录树当前已完全基于嵌套集原生能力（上轮重构完成），是组件迁移的**直接蓝本**：`addons/media/src/Livewire/Concerns/InteractsWithMediaLibrary.php::libraryDirectories()` + `media.blade.php` 左树区块 + `MediaPage::directoryMenuActions()`
- 测试基线：media 50 用例全过；pint 干净

# Page 独立模型 + 内容编排实体化 重构计划（含首页方案）

> 本文档自包含全部背景与设计结论，供新会话（无此前对话上下文）直接执行。
> 状态：**Composition 已完整落地并全量回归通过（2026-09-14）；同日完成 support 下沉重构（第二轮）**。Page 实体（原阶段 2）与导航类型收敛仍为 backlog。
> 基线：cms 测试 111 项全绿（含 CompositionTest 15 项）；全套件 254 passed / 66 skipped。
> 关联计划：`.zcode/plans/plan-navigation-overflow.md`（导航溢出折叠，已完成）。

## 0.2 编排系统演进设计（2026-09-15 定稿方案，未实施）

三个时间层解耦：**构建期（上下文注入）→ 布局期（容器查询）→ 运行期（事件/复合组件）**。

### A. 容器查询迁移（✅ 2026-09-15 已实施，规则见 .ai/rules/container-queries.md）

已完成：block/page 容器/自含容器三处 @container 落位；posts、index-posts、product index/detail/sku、swiper、alert、search post-item 迁移容器断点；换算原则（视口像素对齐：sm→@2xl、md→@3xl、lg→@5xl、xl→@6xl，上限档因容器边距下调一档；index-posts 轮播取 @4xl）；CSS 重建验证；全量回归 258 绿；真机验证窄槽内 posts 不再强行分栏。**页面骨架（行分栏/侧栏 1:3/页头页脚/导航/账户页）保持视口断点。**

### B. 构建期上下文注入（✅ 2026-09-15 已实施，规则见 .ai/rules/composition.md）

已完成：resolveRows 增加 $pageContext + 行上下文袋（消费→解析→提供，行内左→右流动、跨行隔离、extras 优先）；post-detail 注册 provides（选定文章模型）；新增 related-posts 组件（context 消费者，回退链：显式 categoryIds > post 上下文分类 > 空态提示）；registry docblock 补语义；CompositionTest 至 26 项（机制 6 条 + 真实注入链 + 空态）。测试库无分类表，related-posts 的分类查询链在开发库真机验证。

### C. purpose 侧栏（路由详情页/page 导航的左右挂靠）

- Composition 加 `purpose` 字段（string 可空、scoped unique），后台表单加"用途"下拉；cms 内置 `post-detail-sidebar`（后续 `page-sidebar`、左侧位等）；
- Post 详情页 render 查 purpose 绑定的已发布编排：有则分栏（详情主体 2 + 编排侧栏 1，侧栏固定右侧），`['post' => $post]` 作 pageContext 传入 resolveRows；无则现状全宽；
- page 类型导航同机制（`page-sidebar`，NavigationContainer 的 page 分支改分栏行）；固定路由与 page 导航共用 purpose 机制，不逐页发明配置；
- Q2.1（详情混排在编排里，地址 /cms/navigation/{slug}）与 Q2.2（详情为主体+侧栏，地址原生 /cms/posts/{slug}，SEO/评论/计数挂页面组件）分工明确。

### D. 运行期通信决策规则（先立规矩）

1. 渲染时能定的 → 构建期上下文；2. 交互强耦合（树过滤列表）→ 优先复合组件（posts 内置 categoryStyle=tree 即此思路）；3. 仅通知类松耦合用事件 `sn-composition::{slotKey}.{event}`（v1 不实现，等真实场景）。

### 待拍板（2026-09-15 已提请用户）

① 上下文行内流动/跨行隔离；② 详情侧栏固定右侧 2:1；③ purpose 英文 key + 中文标签。

## 0.1 support 下沉重构（2026-09-14 第二轮 + 第三轮微调，最终架构）

编排能力整体下沉 support，cms 变为零代码消费方：

- **归属 support**：`Features/Composition/{CompositionRegistry, CompositionRenderer}`（registry 原名 ContentRegistry，已按 SearchRegistry 词汇重构为 module：$modules、register(string $module)）、`Models/Composition`（sn-support.models.composition 可替换，实现 HasSnSubject，**无 slug**——编排不经前端直接寻址）、`Enums/CompositionStatus`（lang 在 sn-support::composition.*）、迁移 stub（publish-only，应用侧副本已存在）、`Filament/Resources/Compositions/` 完整五层（PostResource 形状，support 不注册）、`views/components/composition/{rows,block}`（sn-support:: 匿名组件，标签写 `<x-sn-support::composition.rows>`，**不带 components. 段**）。
- **module 与 scope 正交**（多次讨论定稿的核心概念）：registry key = 模块标识（插件 id）恒定；scope_type/scope_id 管编排数据隔离；派生 scope（sn-cms-footer）仅导航树用。
- **module_id 接缝**：`HasConfigurationProperties` 增 moduleId 属性/setter；`CanBeConfigured::getModuleId()` = module_id 配置 → getEssentialsPlugin()->getId() → null；support 侧 CompositionForm 经 `BaseResource::form()` 传入决定可选组件（注册表 forms 闭包经注入的 `$livewire->data` 读整表根状态）。
- **表单**：layout ToggleButtons inline+grouped；行内 Grid 三等分栅格，槽位 columnSpan 随布局动态（1/2/full，所见即所得）。
- **cms 消费形态**：零代码——config `panel_register.resources` 注册 support 的 `CompositionResource::class`，条目声明 `scope_type/scope_id + module_id`（包配置与应用发布配置两边同步）；自定义时继承 support BaseResource + use CanBeConfigured（getEssentialsPlugin 可选）。
- **cms 保留**：首页绑定链路（is_home/互斥/urlInfo/Index 解析链/空状态）、CompositionRegistry 组件注册（posts 等）、`Utils::getCompositionModel()` 委托 support。
- **is_home 表单提权**（导航表单顶部）：勾选自动切 type=Content 并锁定；再设别的首页时原节点经互斥钩子自动退回普通内容页（仅地址变化）。
- **渲染统一**：NavigationContainer 单一行式渲染路径——content 解析编排引用，page 类型映射为单通栏行；无 if/else 旧分支。
- **morph map**：`sn_composition` 移至 SupportServiceProvider 注册。
- **测试注意**：`livewire()` 直连页面不经 resource-configuration 路由中间件（也不解析当前 panel），零代码资源的 livewire 测试需显式 `setCurrentPanel('admin') + setCurrentResourceConfigurationKey('default')`（CompositionTest 的 `withCompositionConfiguration` 助手）。

## 0. 实施结果与最终决策（2026-09-14，覆盖下文部分旧结论）

已落地内容：

- **ContentRegistry 下沉 support**（类 + Facade → `Wsmallnews\Support` / `Wsmallnews\Support\Facades\ContentRegistry`，cms 不保留旧 Facade）；注册 key 改为**模块标识插件 id**（`app(CmsPlugin::class)->getId()`）。footer 派生 scope（`sn-cms-footer`）**保留不动**——组件选择/内容实体查询用模块归属，不再用页面实例 scope（bug 根因），规则已沉淀 `.ai/rules`。
- **Composition 实体**（`sn_compositions` + 模型 + `CompositionStatus` 独立枚举），`components` json 为**行式布局**：`[{layout, left: [...], right: [...]}]`，layout ∈ full / 1-2 / 2-1；lg+ 三等分栅格分栏，<lg 单列按 left→right 堆叠；槽内组件条目 `{type, label, description, extras}`（标题/描述前台块头展示）。
- **CompositionRenderer**（`Support/CompositionRenderer`）+ 视图 `components/composition/{rows,block}.blade.php`；未知布局回退通栏、未注册组件类型跳过。
- **CompositionResource**（Posts 五层结构范本）：行式布局 Repeater（layout ToggleButtons + 左右栏 Repeater），**Repeater 必须 defaultItems(0)**（否则空表单预置空条目 + required 组件类型挡保存；visibleJs 只是客户端隐藏，服务端校验仍命中右侧）。
- **首页绑定（用户拍板，替代原 §2.5 方案 A）**：`is_home` 放在**导航 options**（不放 Composition）；导航 content 类型节点勾选 is_home 必须绑 `options.composition_id`；Navigation saving 钩子同 scope 互斥；is_home 节点 urlInfo 指向首页路由（前缀 + /）；Index 页解析链（模块 scopeable 查询）任一环缺失 → 渲染空状态提示（旧 IndexPosts 硬编码示例已移除）。
- **导航 content 类型 = 引用 Composition**（`options.composition_id`），NavigationContainer 走 Renderer；旧内联组件数据/渲染不保留（用户确认不兼容老数据）。
- 演示页 `public/demo/composition-form.html`（**保留不删**）。
- 应用侧同步：发布迁移 `2026_09_14_000001_create_sn_compositions_table.php`；`config/sn-cms.php`（models.composition + panel_register.resources）——**注意应用侧发布配置会整体覆盖包配置，两边都要加**；morph map 增加 `sn_composition`。
- 构建：cms 前台样式经应用主题（`resources/css/filament/admin/theme.css` @import 包源码）编译，改视图后跑根目录 `npm run build` + `php artisan filament:assets`；cms 包自身 dist 构建链未使用（资产注册被注释）。

## 1. 背景与动机

当前 CMS 存在两类"内容长在导航上"的耦合：

1. **`Navigation type = Page`**：关于我们/隐私政策等单页的正文存在导航节点的 `content` 关联里——页面必须挂在导航树上才存在，URL 是 `/cms/navigation/{slug}`（导航语义），后台入口藏在导航树的节点类型里。
2. **`Navigation type = Content`**：首页式的内容编排（组件组合）存在导航节点的 `options.components` 里（经 `ContentRegistry` 注册 posts/index-posts/post-detail 三种组件类型）。**首页本身没有任何编排能力**——`tradition/index.blade.php` 硬编码渲染 `IndexPosts limit=6`，运营无法调整首页版块。

核心结论（用户已确认的哲学）：**导航的本质是"入口的编排"，永远不存内容**；内容是独立实体（Page = 单页、Composition = 编排页），导航只通过"引用"指向它们。

## 2. 已确认的设计决策（本会话逐条讨论过，不要重新讨论）

### 2.1 导航类型收敛为三种

| 类型 | 语义 | 数据 |
|---|---|---|
| `link` | 指向任意地址的入口 | `url` / `route` |
| `reference` | 指向站内内容实体的入口 | `reference_type` + `reference_id`（多态：Page、Post 分类、Composition…） |
| `group` | 纯分组容器，不可点 | 无地址（原 child） |

现状五类型（child/route/page/url/content）中，`page` 和 `content` 两个类型本质是把内容塞进导航，全部改为引用语义。展示属性（图标/target/高亮）与类型正交，放 options。

### 2.2 Page 独立模型

```
sn_pages
├── id / team_id / scope_type / scope_id
├── title
├── slug                    （scoped unique）
├── description             （摘要兼 SEO 描述）
├── cover                   （媒体库，可选）
├── contentable → sn_contents（多态内容，复用 support 四格式）
├── status                  （draft/published/hidden —— 复用 PostStatus 语义，不新造）
├── published_at
├── options (json)
├── order_column
└── timestamps + softDeletes
```

- 模型 = Post 的极简版：SupportModel + Scopeable + SoftDeletes + InteractsWithMedia(cover) + content() MorphOne；**不** use Commentable/Viewable/HasTags（单页没有时间线语义）。
- 后台 PageResource 照 Posts 五层结构（BaseResource + final + Pages + Schemas + Tables）；表单 = 标题/slug/SEO 描述/封面/内容类型组/状态。**没有**分类/标签/flags/定时任务。
- 前端路由 `/cms/pages/{slug}`，SEO 走 M2 体系（title/description/Article JSON-LD/canonical）。
- **不做列表页**：单页是离散完整内容，无时间线；"帮助中心"类聚合需求用导航分组或将来聚合页解决。模型上 `Page::published()->ordered()` 天然可查，将来加列表 = 一个路由 + 一个 Livewire 组件。
- **scopeable**：直接用模块主 scope（cms = sn-cms，shop = sn-shop），**不派生独立 scope**——Page 与 Post 平级，"cms 的页面"和"cms 的文章"是同范围平行内容。shop 要用时模型经 `sn-cms.models.page` 可替换或下沉 support（推荐先放 cms，等真有第二消费方再下沉，避免提前抽象）。

### 2.3 导航与 Page 的结合

```
Page:about-us（实体） ◄──引用── 导航节点（类型=reference，显示名"关于"，可不同于页面标题）
```

- 导航节点 URL 由引用解析（Page → `/cms/pages/{slug}`）；
- 删除 Page 时引用它的导航节点置"引用失效"（渲染不可点），**不级联删导航节点**（导航树编排是运营心血）。

### 2.4 内容编排（Composition）——本次要解决的主体

现状：`Navigation type = Content` 把组件编排存 `options.components`（数组：type/label/extras），渲染时经 `ContentRegistry::getType()` 解析为 Livewire 组件（posts/index-posts/post-detail）。

**目标设计**：编排抽成独立实体 `sn_compositions`（命名可再定：Composition/落地页）：

```
sn_compositions
├── id / team_id / scope_type / scope_id
├── title
├── slug                    （scoped unique，可寻址；也可仅作后台标识）
├── status
├── components (json)       （即现 options.components 的结构：[{type, label, extras}]，ContentRegistry 解析）
├── options (json)
└── timestamps
```

- 渲染逻辑从 NavigationContainer 迁出：Composition 存在 → 按 components 循环渲染注册的 Livewire 组件（ContentRegistry 机制原样复用，它已经是"注册器 + 组件解析"的干净抽象，只需消费方从导航换成 Composition）。
- 导航 `type = content` 变为 **引用 Composition**。

### 2.5 首页方案（本次的大问题，三个候选）

现状首页完全硬编码：`tradition/index.blade.php` → `<livewire:sn-cms::components.post.index-posts :limit="6" />`。

| 方案 | 机制 | 优点 | 缺点 |
|---|---|---|---|
| **A. 约定绑定（推荐）** | Composition 有 `is_home` 标记（或约定 slug=`home`），`sn-cms.routes.uri.index` 对应的 Index 页面组件查找 `is_home` 的 Composition：存在 → 按编排渲染；不存在 → 回退现状 IndexPosts 默认渲染 | 运营在后台直接编辑"首页"；多租户下每租户自己的首页编排；回退保证空站点不坏 | 需要处理"多个 is_home"的唯一性（save 时互斥置位） |
| B. 设置指向 | GeneralSettings 加 `homepage_composition_id`，Index 页面读设置解析 | 显式、可换 | 多租户 settings 要逐租户配；空值回退逻辑同 A，链路更长 |
| C. 配置指向 | config `sn-cms.homepage.composition_id` | 简单 | 配置是代码层面的，运营改不了，等于没解决 |

**推荐 A**，且 A 的 `is_home` 思路可扩展：Composition 增加 `purpose`（home/landing/…）或直接用布尔，一期只用 home。

首页渲染形态（A 方案下）：`Index` Livewire 页面组件 `render()` 里查 home Composition → 有则循环渲染其 components（复用 §2.4 的渲染服务），无则现状视图。SEO（M2 体系）不变。

## 3. 实施阶段（建议顺序）

### 阶段 1：Composition 实体 + 渲染服务（核心）
- 迁移 `sn_compositions` + 模型（Scopeable/SoftDeletes）+ 枚举状态；
- **渲染服务** `CompositionRenderer`（或 support 上的组件解析助手）：输入 Composition，输出渲染所需的组件清单（把 NavigationContainer 里 `type == Content` 分支的解析逻辑原样搬来，ContentRegistry 消费方从导航换成该服务）；
- Index 页面接 home Composition（§2.5 方案 A，含回退）；
- 测试：home 编排渲染各注册组件、无 home 回退默认视图、组件 extras 透传。

### 阶段 2：Page 实体（独立，可与阶段 1 并行）
- `sn_pages` 迁移 + Page 模型 + PageResource（Posts 五层结构）+ `/cms/pages/{slug}` Livewire 页（SEO 套 M2）；
- 测试：详情渲染/状态过滤/scope 隔离/SEO meta。

### 阶段 3：导航引用化
- NavigationForm 的 `page` 类型改为引用选择器（Select Page，options.page_id 或统一 reference 字段）；`content` 类型改为引用 Composition；
- NavigationContainer：Page/Content 型节点改为解析引用（引用失效渲染不可点）；
- **存量兼容决策**（执行前必须问用户）：不考虑老数据可直接切换；要兼容则旧渲染逻辑作 fallback 保留 + 后台"一键迁移"（建 Page/Composition + 复制 content/components + 节点改引用）。
- 测试：引用解析/失效态/引用 URL 生成。

### 阶段 4（可选，视用户）：旧 URL 301
- Page/Composition 上线后，`/cms/navigation/{slug}` 旧地址若要保留权重，接 301 重定向机制（backlog 里的 Redirect 方案，届时一起做）。

## 4. 关键现有代码位置（执行前重读，可能有更新）

| 文件 | 相关点 |
|---|---|
| `addons/cms/src/Livewire/Components/Navigation/NavigationContainer.php` | type==Content 分支的组件解析逻辑（阶段 1 要搬走）；**导航任务刚重构过，执行前重读最新版** |
| `addons/cms/src/ContentRegistry.php` + `CmsServiceProvider` 里 `ContentRegistryFacade::registers(...)` | 组件注册（posts/index-posts/post-detail），机制保留复用 |
| `addons/cms/resources/views/livewire/tradition/index.blade.php` | 现状硬编码首页 |
| `addons/cms/src/Livewire/Index.php` | 首页 Livewire 页面组件（SEO：`Seo::website()`） |
| `addons/cms/src/Filament/Pages/Navigation/Schemas/NavigationForm.php` | page/content 类型的表单（阶段 3 改造点） |
| `addons/cms/src/Enums/NavigationType.php` | 五类型枚举（收敛为三类时改） |
| `addons/cms/database/migrations/create_sn_navigations_table.php.stub` | 导航表结构 |
| support 的 `sn_contents` 多态内容表 + ContentType | Page 内容复用 |

## 5. 验证基准

- 现有 81 项 cms 测试全绿为底线；
- 新增：Composition 渲染/回退/首页绑定（~8 用例）、Page 详情/SEO/scope（~6 用例）、导航引用（~6 用例）；
- 真机：首页编排编辑后即时变化；`/cms/pages/about-us` 直达；导航引用失效态。

## 6. 明确不做（本期）

- Page 列表页（见 2.2）；
- Page 放 support 包下沉（等 shop 真用再下沉）；
- 旧数据自动迁移脚本（兼容策略见阶段 3 决策点）；
- Composition 的可视化拖拽编辑器（沿用 ContentRegistry 表单式编排，编辑器是独立大话题）。

## 7. 执行前需用户拍板的三个决策点

1. **`content` 编排（Composition）本期抽不抽**——不抽则 Page 单独走（阶段 2），首页编排继续放 backlog；抽则按本文档全量。用户已表示"先处理 content 编排，因为首页怎么办"，倾向抽。
2. **首页方案 A/B/C**——文档推荐 A（is_home 标记 + 回退），需确认。
3. **存量 Navigation Page/Content 数据兼容**——直接切（用户曾说"不考虑老数据的兼容性"，但执行前再确认一次，涉及开发库现有演示数据）。

## 8. 与并行任务的冲突提示

- `plan-navigation-overflow.md`（导航溢出）已标记完成，但其文件（NavigationContainer/NavigationForm/Navigation.php 模型、config navigation 节、lang）与本任务阶段 3 高度重叠——**执行阶段 3 前必须重读这些文件的最新版**；
- config/lang 修改遵循"只动自己区域、写前重读"约定（M5 期间验证有效）；
- 新会话开工时先跑 `php artisan test --compact tests/Feature/Cms/` 确认基线（应为 81 绿）。

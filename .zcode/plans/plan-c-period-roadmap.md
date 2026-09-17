# C 期路线图：purpose 侧栏 / 组件事件 / Page 实体化

> 状态：待确认。三项按优先级排序，8 与 10 有依赖关系（10 的 P3 依赖 8 的 purpose 概念先行验证）。

---

## 8. C 期：purpose 侧栏（详情页挂编排侧栏）

### 背景与目标

原生路由页（如 `/cms/posts/{slug}` 详情页）目前是固定布局（文章 + 评论，无侧栏），没有编排能力。B 期的构建期上下文（`resolveRows($rows, $module, $pageContext)`）已支持页面级种子注入，本项把它用到真实场景：**详情页可配置侧栏（相关推荐、热门列表、广告位等），后台可视化编排**。

### 方案设计

**数据模型**：`sn_compositions` 加 `purpose` 字段（string，nullable，索引）。purpose = 编排的用途槽位（null = 通用展示编排，维持现状；`post-sidebar` = 详情页侧栏）。

- **值域决策点（需确认）**：
  - 方案 A：support 包枚举 `CompositionPurpose`（Home/PostSidebar + 预留）——类型安全，但扩展要改包
  - 方案 B：config 白名单（`sn-support.composition.purposes`）+ 字符串字段——各站点可自定义，校验靠 config
  - 推荐 B（与 scope/module 的配置化风格一致），枚举仅在 support 内部做默认值常量

**渲染链**（全部复用现有机制）：
- 新增 `CompositionRenderer::resolveForPurpose(string $purpose, string $scopeType, int $scopeId, array $pageContext): ?array`——按 purpose + scope 查询已发布编排，命中则 `resolveRows`，未命中返回 null
- cms 详情页（`Livewire\Post\Post` 所路由页）布局改造：外层改 `lg:grid lg:grid-cols-4 xl:grid-cols-5`（views.md 既有分栏规范），主列（文章+评论）`col-span-3/4`，侧栏列渲染 purpose 命中的编排，`pageContext = ['post' => $post]`（B 期注入链直接可用，related-posts 等消费者零改动）
- 无匹配编排时详情页回退全宽布局（与现状一致）

**后台**：CompositionResource 表单加 purpose 下拉（config 白名单，默认 null）+ 列表筛选；无 purpose 的编排行为完全不变。

### 改动清单

| 包 | 内容 |
| --- | --- |
| support | 迁移（purpose 字段+索引）、Composition 模型 fillable/cast、resolveForPurpose、CompositionForm purpose 字段、CompositionTable 筛选列、翻译 |
| cms | 详情页布局改造（分栏 + 侧栏渲染 + 回退）、Post 路由页 pageContext 注入 |
| 主仓库 | config 同步、CompositionTest 扩展（purpose 查询命中/未命中回退/侧栏上下文注入） |

### 阶段拆分

1. **C1 数据与查询**：迁移 + resolveForPurpose + 测试（纯 support 层）
2. **C2 详情页布局**：cms 分栏改造 + pageContext 注入 + 无编排回退 + 浏览器验证（SEO 元数据回归）
3. **C3 后台与默认组件**：purpose 表单/筛选 + 演示数据（post-sidebar 编排 + related-posts 复用）

### 风险与决策点

- purpose 语义膨胀：先用 config 约束，出现第三个真实用途前不加枚举
- 详情页布局回归：评论分页、SEO meta、面包屑位置需逐一验证（SeoMetaTest 已有覆盖）
- 侧栏编排的 contained 默认值：侧栏组件建议默认带卡片（视觉分隔），文档写明

**预估**：一个完整会话轮（C1-C3 顺序做，测试+真机验证）。

---

## 9. D 期：组件间事件约定（设计预留，不立项）

### 定位

三层决策规则的最后一层（构建期上下文 → 复合组件 → 事件），**共识是等真实场景出现再实施**。当前没有任何一个真实需求需要运行期组件通信（related-posts 用上下文注入已覆盖）。

### 设计草案（预留）

- 事件通道：Livewire `dispatch`（跨组件、走网络）+ Alpine `$dispatch`（同页 DOM 局部，零网络）双通道，优先 Alpine
- 命名约定：`sn-composition::{event}`，payload 必带 `blockKey`（编排条目键）供监听方过滤同源/异源
- 使用约束：只做"通知类松耦合"（如轮播换页 → 标题组件同步），交互强耦合仍走复合组件，渲染期能定的仍走上下文注入

### 计划

本轮仅将草案记入 `.ai/rules/composition.md` 附录（已完成三层决策规则的第三层补充）。**不排期、不写代码**，出现第二个真实场景时再立项。

---

## 10. Page 实体化与导航类型收敛

### 背景与目标（最大的一项，独立规划）

现状"页面"概念分散在四处：

1. 导航节点 content 类型（`options.composition_id` 引用编排）——"页面长在导航树上"
2. 首页（导航 `options.is_home` 绑定）——首页语义藏在导航 options
3. 固定路由页（`/cms/posts/{slug}`、profile、search 等）——与导航无模型关联
4. 编排本身（可被导航引用，但独立存在）

痛点：about-us 这类独立页面必须挂导航树才可路由；页面 URL 与导航结构耦合（挪导航 = 挪页面）；sitemap/SEO 对"页面"没有统一模型。

**目标模型**：`Page` 实体（slug 唯一、title、类型、内容绑定）成为页面的唯一事实源；`Navigation` 回归纯结构（指向 Page / 外链 / 锚点），不再承载页面语义。

### 方案设计

**P1：Page 实体与路由**
- `sn_pages` 表：title/slug（scope 内唯一）/type（composition/uri/module-route）/composition_id 绑定/SEO 字段（title/description/og，替代部分站点设置）/status
- 路由 `cms/pages/{slug}`：composition 型渲染编排；uri 型 302；module-route 型透传到模块路由
- PageResource（五层结构，零代码注册风格）

**P2：导航引用迁移**
- Navigation content 类型改为 `page_id` 引用（`options.composition_id` 兼容一个版本，渲染端双读）
- 后台导航表单的编排选择器换成页面选择器（保留"直接选编排"快捷创建页面的路径）
- 存量数据迁移命令（navigation content → 建 Page → 改引用）

**P3：语义收敛（依赖 8 的 purpose 验证）**
- 首页 = `purpose='home'` 的 Page（is_home 从导航 options 移除，保留导航首页节点的展示语义）
- sitemap/SEO 生成以 Page 为源（与 SeoMeta 整合）
- `/cms/navigation/{slug}` 旧路由 301 到 `/cms/pages/{slug}`

### 改动清单（概览）

| 层 | 内容 |
| --- | --- |
| support | Page 模型/迁移/资源骨架（可复用 Composition 的五层范本） |
| cms | pages 路由与渲染页、导航表单 page 选择器、is_home 迁移、301、sitemap 整合、存量迁移命令 |
| 主仓库 | config、演示数据、大版本测试 |

### 风险与决策点

- **引用方向**：Page 持有 composition_id（推荐，简单）vs composition 挂 page 反向（灵活但复杂）
- **slug 命名空间**：与 posts/{slug} 共享 `/cms/` 前缀，pages 用独立段 `/cms/pages/{slug}` 无冲突，但需确认 URL 审美
- **兼容成本**：P2 的双读期与 P3 的 301 是主要回归面；导航树后台的交互变化需要你试用后再定细节
- **与 8 的关系**：purpose 侧栏先行验证"页面挂编排"的渲染体验，P3 再把首页迁入 Page 模型，降低一次性风险

**预估**：P1 一轮会话、P2 一轮、P3 一轮（含迁移命令与真机验证），共 3 轮。**建议排在 8 之后**。

---

## 建议执行顺序

1. **8（purpose 侧栏）**：独立、收益直接、复用 B 期机制，先做
2. **10（Page 实体化）**：分 P1-P3 渐进，每步可停
3. **9（事件）**：不立项，草案已入规则

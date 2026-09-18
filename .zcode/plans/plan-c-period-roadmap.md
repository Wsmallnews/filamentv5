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

### 状态：P1 已完成（2026-09-17，含最终形态修订）

**最终形态（经全站推演定稿，替代早期"薄路由壳"方案）**：

```
Page（站点静态内容页实体，support）
├── title / slug（scope 内唯一）/ status / order_column
├── content（MorphOne → sn_contents）     ← 主体通道：富文本/Markdown 简单页面
└── composition_id（nullable）           ← 高级模式：专题页/首页绑编排

导航节点（纯结构）──page_id──→ Page ──┬── content
                                        └── composition
```

**三条边界（防退化）**：
1. content 与 composition_id 应用层互斥（编排优先），不做双向转换魔法
2. Composition 保持独立实体（purpose 侧栏需要），不是 Page 从属
3. Page 不长 Post 的能力（无 publisher/评论/标签/调度/分类）

**前端形态**：slug 寻址详情路由 `/cms/pages/{slug}`；无列表页无 feed（页面经导航/链接结构触达，WordPress 模型）；不提供 id 路由（避免双 URL SEO 分叉）。

**已落地**（P1 + content 通道修订）：
- sn_pages 迁移（六字段 + scope）、Page 模型（content/composition 双关联）、PageStatus
- PageResource 五层（表单：互斥的编排选择器 + contentTypeGroup 内容组）
- PageRenderer（findPage 预加载 content / resolvePage 编排通道）、cms 路由 + 渲染组件（三分支：编排/内容/空态）
- 编排块 embedded 标志：块内组件让渡页面级 SEO（修真实 bug：块内文章组件曾覆盖页面标题）
- 后台零代码注册（config panel_register）、PageTest 12 项

### P2：导航收敛（方案 C——Content 类型删除，只认 Page）

**原则**：无真实用户零兼容包袱（见 `.ai/rules/database.md`）——删字段直接改原始迁移（stub + 主仓库双侧），migrate:fresh 重建，不写数据迁移脚本，不做 301。

改动清单：
1. **NavigationTypeEnum 收敛**：删除 `Content` 类型；`Page` 类型语义改为 page_id 引用（表单从 contentTypeGroup 换成 Page 选择器）
2. **导航表瘦身**：删 `slug` 字段（寻址职责归 Page）、删 content 兼职通道相关代码（Navigation::content() 关联与表单 contentTypeGroup）
3. **导航表结构**：`navigations` 加 `page_id`（nullable，index）；原始迁移直接改，不叠增量
4. **路由下线**：删 `/cms/navigation/{slug}` 路由与渲染入口（Navigation Livewire 组件），存量外链直接断
5. **is_home 迁移**：`options.is_home` → Page 加 `is_home` 布尔（或首页 = is_home 的 Page，P3 与 purpose 商定）；Index 组件改查 Page
6. **演示数据重 seed**：关于我们等页面重建为 Page（content 型），首页绑编排的 Page

### P3：语义收敛（依赖 8 的 purpose 验证）

- sitemap/SEO 生成以 Page 为源（与 SeoMeta 整合）
- Page 侧栏属性（sidebar_purpose / sidebar_position）在 purpose 机制落地时追加
- shop 落地页/品牌页 = Page（scope=sn-shop）+ sn-shop 模块注册的组件编排

### 风险与决策点

- 导航树后台交互变化（Page 选择器替代编排选择器 + contentTypeGroup）需真机试用后定细节
- is_home 的最终载体（Page 布尔字段 vs purpose='home' 查询）在 P2 时与 8 的机制统一考虑

---

## 建议执行顺序

1. **10-P2（导航收敛）**：Page 实体已就位（P1 完成），导航收敛是下一个闭环
2. **8（purpose 侧栏）**：独立、收益直接、复用 B 期机制；其 purpose 概念与 10-P3 的 is_home/侧栏统一设计
3. **9（事件）**：不立项，草案已入规则

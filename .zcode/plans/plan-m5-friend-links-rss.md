# CMS M5（友情链接 + RSS）执行记录与剩余任务计划

> 本文档自包含全部背景与细节，供后续会话（无此前对话上下文）直接继续执行。
> 状态：**M5 代码已完成、测试全绿，但用户尚未审阅**——用户将换电脑在 git 仓库中审阅并提交。
> 里程碑全景：M1 站点配置 / M2 SEO meta / M3 sitemap+robots / M4 Footer 重构（均已完成并经用户多轮审阅）；M5 为本文档主体，待审。

## 1. M5 交付清单（今晚审阅用，文件级明细）

### 1.1 友情链接（Link）

**新增文件：**

| 文件 | 作用 |
|---|---|
| `addons/cms/src/Enums/LinkStatus.php` | 状态枚举 Normal/Hidden（HasLabel/Color/Icon，与其他枚举同构） |
| `addons/cms/src/Models/Link.php` | 模型（表 `sn_links`；use Scopeable；scopeNormal/Hidden/Ordered——ordered = order_column 降序 + id 降序） |
| `addons/cms/database/migrations/create_sn_links_table.php.stub` | 迁移：name/url/logo/group_name/order_column/nofollow/status + team/scope 列 |
| `database/migrations/2026_09_07_000001_create_sn_links_table.php` | 应用侧迁移副本（**已执行**，sn_links 表已在开发库） |
| `addons/cms/src/Filament/Resources/Links/BaseResource.php` | 抽象基类（Scopeable、图标 Heroicon::OutlinedLink、slug `links`、navigationSort=4、applyScopeableToQuery） |
| `addons/cms/src/Filament/Resources/Links/LinkResource.php` | final + CanBeConfigured（form/table 支持插件自定义覆盖，与 NavigationTypeResource 同构） |
| `addons/cms/src/Filament/Resources/Links/Schemas/LinkForm.php` | 表单：name/url（url 校验）/logo 上传/group_name/order_column/nofollow 开关/ToggleButtons 状态 |
| `addons/cms/src/Filament/Resources/Links/Tables/LinksTable.php` | 列表：搜索、复制链接、nofollow 布尔、状态筛选徽章、默认 order 降序 |
| `addons/cms/src/Filament/Resources/Links/Pages/{ListLinks,CreateLink,EditLink}.php` | 三个页面；**CreateLink 的 `mutateFormDataBeforeCreate` 合并 scopeable**（照抄 CreatePost 模式，不合并则 scope_type 为 null） |

**修改文件（友链部分）：**

| 文件 | 改动 |
|---|---|
| `addons/cms/src/Support/Utils.php` | 新增 `getLinkModel()`（`getModel('link')`） |
| `addons/cms/config/sn-cms.php` + `config/sn-cms.php` | ① `models` 加 `'link' => Models\Link::class`；② `panel_register.resources` 加 `LinkResource::class`（+use 导入） |
| `addons/cms/resources/views/livewire/tradition/components/footer.blade.php` | 友链条：合规条上方一行平铺（"友情链接："前缀，nofollow 自动 `rel="noopener nofollow"`，空则整条隐藏）；RSS 入口位置：分组形态快捷条尾部 / 全一级形态右侧链接组尾部（**空树形态不放 RSS**——契约来自 FooterTest "空树=仅品牌区+合规条"） |
| `addons/cms/resources/lang/{zh_CN,en}/cms.php` | 新增 `link_status` / `link_resource` / `link_form` / `link_table` 四节；`frontend` 节加 `friend_links` |

**演示数据：** 开发库已种 5 条友链（新华网/人民网/澎湃/虎嗅[nofollow]/36氪，order 100→60）。

### 1.2 RSS 订阅（Feed）—— **已重构为 support 级 FeedRegistry 架构（2026-09-08）**

> 背景：站点将有多个可作为站点访问的扩展包（cms、后续 shop），feed 原先是 cms 私有路由（`/feed` 只输出 cms 文章），与 sitemap 的多模块聚合模式不一致。重构后与 SitemapRegistry 平行：**端点与聚合由 support 提供，各包注册具名内容流**。
> 语义差异（为何不并入 SitemapRegistry）：sitemap 是站点级清单（全部模块 URL 合并一份），feed 是内容更新流（读者按流订阅）——因此按 name 注册多个流。shop 上线后只需 `Feed::register('sn-shop', 'products', [...])`，自动获得全部端点。
>
> **三层端点与内容隔离（2026-09-08 第二轮，用户拍板"模块端点 + 根整站流"）**：
>
> | 端点 | 输出 | 隔离机制 |
> |---|---|---|
> | `/feed` | 整站聚合流（全部可见模块流合并、时间倒序） | 域名过滤（独立域名部署下天然只含本域模块） |
> | `/feed/{name}` | 具名流（如 `/feed/posts`） | 域名过滤 + name 全局唯一 |
> | `/cms/feed`、`/cms/feed/{name}` | **模块端点**（仅本模块流） | 模块归属（路径前缀部署下多模块共用域名时的内容隔离） |
>
> footer 与 autodiscovery 用**模块视角**（`Feed::moduleFeeds(插件 ID)`），链接指向模块端点 `/cms/feed/{name}`——两种部署形态下入口行为一致且天然隔离。

**support 侧新增文件：**

| 文件 | 作用 |
|---|---|
| `addons/support/src/Features/Feed/FeedRegistry.php` | feed 注册表（单例 + `Feed` facade；配置读取统一走 `SupportUtils::getConfig` 点号参数名）：`config(模块, [domain/title/description/link])` 模块域名与频道元数据声明 / `register(模块, name, feed)` 具名流（title/label/description/link/limit/items 闭包，**name 全局唯一、重名抛异常**）/ `available()` 整站视角可见流 / `moduleFeeds(模块)` 模块视角可见流 / `getFeed(name, ?模块)` 含归属校验 / `routes(模块)` 注册模块端点路由并捕获完整路由名（`{前缀}feed.show`）/ `feedUrl(name)` 订阅地址（模块端点优先，`sn_route` 租户感知）/ `autodiscoveryTags(模块)` head 内 autodiscovery 标签（`@snFeeds` 指令的支撑方法）/ `render(?name)` 整站渲染 / `renderModule(模块, ?name)` 模块端点渲染（四种端点形态共用 compile，按流整份缓存，key 带租户+域名+模块+流标识）/ `flush()` 清全部端点缓存 |
| `addons/support/src/Facades/Feed.php` | facade（accessor = FeedRegistry::class） |
| `addons/support/src/Http/Controllers/FeedController.php` | `index()` 整站聚合 + `show(name)` 具名流（不可见 404）+ `moduleIndex()` / `moduleShow(name)` 模块端点（**feed_module 显式从路由参数取，不走方法注入**——Laravel 传路由参数按位置 array_values，defaults 键与 URI 段顺序无法对齐，踩过参数错位坑） |
| `addons/support/src/Features/Concerns/MatchesDomain.php` | 从 SitemapRegistry 抽出的域名过滤 trait（moduleMatchesDomain + hostMatches，含 `{tenant:slug}.example.com` 通配），两个 registry 共用；各自经 `domainFilterKey()` 声明配置键 |

**support 侧修改文件：**

| 文件 | 改动 |
|---|---|
| `addons/support/src/SupportServiceProvider.php` | 注册 FeedRegistry 单例 + `@snFeeds` Blade 指令（参数 = 模块 ID，输出该模块的 autodiscovery 标签） |
| `addons/support/routes/web.php` | 重构为共享中间件组（`web` + IdentifyTenant），加 `sn-support.feed`（/feed）与 `sn-support.feed.show`（/feed/{name}，where name `[a-z0-9\-]+`）路由，`sn-support.feeds.enabled` 门控 |
| `addons/support/config/sn-support.php` | 新增顶层 `feeds` 节：enabled / uri / limit（聚合总量上限，默认 50）/ cache_ttl（默认 3600）/ domain_filter |

**cms 侧（删除 FeedController.php 与 cms 路由块，改为注册制）：**

| 文件 | 改动 |
|---|---|
| `addons/cms/src/CmsServiceProvider.php` | sitemap 注册块之后新增：`sn-cms.feed.enabled` 开启时 `Feed::config('sn-cms', [domain + title/description/link 模块聚合频道元数据])->register('sn-cms', 'posts', [...])`；title = site_name + " - 文章动态"、description = seo_description→slogan、link = cms 首页、limit = sn-cms.feed.limit（闭包渲染期解析，运行时改 config 可生效）、items = 已发布文章（published_at 降序） |
| `addons/cms/routes/web.php` | prefix 组内最前（**必须早于 navigation/{slug} 等动态段路由**，否则 /cms/feed 会被吃掉）一行调用 `Feed::routes(app(CmsPlugin::class)->getId())`（registry 提供的模块端点路由注册：{前缀}/feed → moduleIndex、{前缀}/feed/{name} → moduleShow，路径段取 sn-support.feeds.uri，路由名带组前缀 sn-cms.feed / sn-cms.feed.show），feed.enabled 门控 |
| `addons/cms/src/Livewire/Components/Footer.php` | `$feedUrl` 改为 `$feeds` 列表：`Feed::moduleFeeds('sn-cms')`（模块视角，其他模块流不出现）逐流生成 `{url, title, label}`，URL 经 `Feed::feedUrl(name)`（模块端点优先、租户感知）；feed.enabled 关闭时空集合 |
| `addons/cms/resources/views/livewire/tradition/components/footer-rss.blade.php` | RSS 入口分部，**从 components/tradition 组件迁到 livewire 目录与 footer.blade.php 同级**，footer 经 `@include($this->getThemeView(...))` 引用（非组件，无 @props），逐流渲染链接（icon + label，title 属性带完整频道标题，多流间加分隔线） |
| `addons/cms/resources/views/components/layouts/app.blade.php` | autodiscovery 改用 support 注册的 `@snFeeds('sn-cms')` 指令（参数 = 模块 ID，指令内部走 `FeedRegistry::autodiscoveryTags`，逐流输出 `<link rel="alternate">`，href 指向模块端点） |
| `config/sn-cms.php`（两份副本） | `routes.uri.feed` 已删除（feed 全部路径统一由 `sn-support.feeds.uri` 管理：根端点 /feed 与模块端点 {前缀}/feed 用同一路径段）；`feed` 节注释更新（enabled = 是否注册流与模块端点路由，boot 期） |
| `addons/cms/resources/lang/{zh_CN,en}/cms.php` | `frontend.rss`（"RSS 订阅"）替换为 `frontend.rss_posts`（"文章动态"/"Posts"，footer 短标签 + 频道标题后缀） |

**测试：** feed 用例从 LinkResourceTest 迁出至新建的 `tests/Feature/Cms/FeedTest.php`（16 个用例：整站聚合 RSS 2.0 结构/草稿与 scope 过滤/多模块合并排序/单流与聚合两级 limit/根具名流/未知与域名不匹配 404/**模块聚合与模块具名端点隔离（shop 流不进 /cms/feed，其他模块流名 404，根端点仍全局可见）**/重名与缺 items 抛异常/缓存与 flush/footer 与 autodiscovery 渲染（指向模块端点）/enabled 关闭隐藏）；LinkResourceTest 保留 7 个友链用例。重构后全量回归：**cms + support 159 过 / 2 挂（SwiperComponentTest 两个存量失败，与本次无关）**。

## 2. 审阅要点与已知取舍

1. **`CreateLink::mutateFormDataBeforeCreate` 合并 scopeable** 是必须的（不合并 scope_type 为 null）——这与 CreatePost 逐个页面复制的模式一致；若后续嫌重复，可考虑把 scope 合并上收到 BaseResource 层（属重构，本次未做）。
2. **feed.enabled 的语义（重构后）**：`sn-cms.feed.enabled` = cms 是否注册 posts 流 + 模块端点路由 + 前台渲染开关，**流注册与路由注册发生在 boot 期，运行时改 config 无法移除已注册的流与路由**（测试只断言渲染层消失，注释有说明）。站点级关闭 RSS 端点是 `sn-support.feeds.enabled`（路由注册期读取，同样需改 config 后重载）。两级 limit：`sn-cms.feed.limit`（本流，渲染期闭包解析，运行时可改）与 `sn-support.feeds.limit`（聚合总量）。整站聚合流 `/feed` 的频道标题用 app.name（无模块元数据可取），模块端点 `/cms/feed` 用模块声明的站点名——若想让 `/feed` 也显示站点名，属后续增强（可在 sn-support.feeds 加可选 title 配置）。
3. **空树形态无 RSS** 是被 FooterTest 契约约束的（用户异地优化定义的"空树=仅品牌区+合规条"），RSS 只在有导航的两种形态出现。
4. **LinkResource navigationSort=4**：排在 导航(1)/图文(2)/底部导航(2) 之后，属拍脑袋值，审阅时可按喜好调整。
5. `sn_links` 迁移的 `group_name` 字段当前**仅存储、未参与前台渲染**（footer 是单行平铺），留给将来分组渲染。
6. **feed 渲染按流整份缓存**（默认 3600 秒，`sn-support.feeds.cache_ttl`）：文章发布后最长延迟 1 小时出现在 feed，需即时生效可调 `Feed::flush()` 或把 cache_ttl 设 0；sitemap 同款取舍。
7. **feed 中间件比原实现更轻**：原 cms 路由挂着 `user-active`/`ResolveMember`（RSS 阅读器轮询会被会员可用性校验拦截），重构后与 sitemap 同款 `web + IdentifyTenant`，公开只读端点不再走会员中间件——这是顺带修正的瑕疵。

## 3. 与并行任务的冲突管控记录（重要，后续会话须知）

另一进程执行 `.zcode/plans/plan-navigation-overflow.md`（导航溢出折叠 + 无限级 + 风格化）。本任务与其**仅两个文件重叠**，已按"只动自己的区域、写前重读"处理，无冲突：

- `config/sn-cms.php`（两份副本）：对方加顶层 `navigation` 节；本任务（含 feed 重构后）只动 `models.link` / `LinkResource` 注册 / `feed` 节注释（`routes.uri.feed` 已删除）。注意：**包内副本与应用副本在 `search.display`（null vs 'page'）上存在既有差异**，属对方任务范围，勿"顺手同步"。
- `lang/{zh_CN,en}/cms.php`：对方在 `frontend` 节加 `more` 等；本任务加 `link_*` 新节与 `frontend.friend_links/rss`。

其余 M5 文件（模型/迁移/资源/控制器/视图/测试）与对方零交集。全量回归 74 绿证明两任务测试同时通过。

## 4. 剩余任务（按优先级）

### 任务 A：PostForm slug 生成优化（来自 M3 期间发现的存量问题）

**问题**：`addons/cms/src/Filament/Resources/Posts/Schemas/PostForm.php` 的 slug 自动生成用 `Str::slug(title)`：
- 超长标题（如重复粘贴产生 170 字符标题）生成超长 slug；
- 纯中文标题 `Str::slug` 转换结果为**空**，slug 字段校验 required 会卡住（开发库现存 `3%E9%98%BF...` 这类中文直存 slug 即历史脏数据）。

**方案**（改 `afterStateUpdated`）：
```php
->afterStateUpdated(function (Set $set, $state) {
    $slug = Str::limit(Str::slug(title: $state, language: app()->getLocale()), 80, '');
    $set('slug', filled($slug) ? $slug : 'post-' . Str::lower(Str::random(8)));   // 空结果兜底
}),
```
- 截断 80 字符、无省略号后缀；
- 空结果（纯中文等）兜底 `post-随机串`；运营者仍可手动改（字段可编辑）；
- **不做存量数据自动迁移**（脏 slug 由运营后台手改）；可选：给 slug 字段加 `maxLength(80)` 前端约束。

**测试**（新建 `tests/Feature/Cms/SlugTest.php` 或并入 PostContentTest）：
- 长重复标题 → slug ≤80 且非空；
- 纯中文标题 → slug 以 `post-` 开头非空。

**文件冲突检查**：PostForm.php 不在导航任务范围，无冲突。

### 任务 B：收尾杂项

1. 删除 `public/footer-preview.html`（M4 静态预览页，M4 已验收，对照价值已完成；注意 `public/demo/` 目录按项目规则**永久保留**，此文件不在 demo 目录，可删）。
2. 排查 `tests/Feature/Support/SwiperComponentTest.php` 的 2 个存量失败（精确 HTML 断言疑似环境差异；已验证与 M2-M5 改动无关——stash 掉改动后仍失败）。
3. 若移动/重命名包内 PHP 文件，记得 `composer dump-autoload`（classmap 优化模式会找不到新命名空间的类，M4 期间踩过）。

### 任务 C：远期 backlog（来自全面差距分析，未排期，供规划参考）

| 项 | 说明 |
|---|---|
| 301 重定向管理 | slug 变更后旧链接 404；Redirect 模型 + 中间件 |
| Page 独立模型 | 关于我们/隐私政策等单页仍挂在导航树上（Page 型导航），语义耦合 |
| 草稿预览 | 未发布文章的签名预览链接 |
| 后台数据面板 | 文章数/评论数/浏览量/待审评论 dashboard widgets |
| 统一媒体库 | 图片散落各模型，无 alt 管理 |
| 首页可视化编排 | 首页目前硬编码 IndexPosts limit=6 |
| user 模块 SEO 全局层 | Seo 支持"全局默认配置层"（模块→全局→app.name 三级回退），user 页面复用 cms 品牌 |
| sitemap 分片 | URL 超 5 万时的 sitemap index（当前规模不需要） |

## 5. 验证命令备忘

```bash
# 跑 M5 相关测试（友链）
php artisan test --compact tests/Feature/Cms/LinkResourceTest.php

# 跑 feed 测试（重构后）
php artisan test --compact tests/Feature/Cms/FeedTest.php

# 全量 cms + support 回归（含导航任务测试）
php artisan test --compact tests/Feature/Cms/ tests/Feature/Support/

# 格式化（改 PHP 必跑）
vendor/bin/pint --dirty --format agent

# 改 blade 视图后必须重建前端（否则新 Tailwind class 不生效）
npm run build

# 真机验收
# 前台: http://filamentv5.test/cms（友链条 + 快捷条尾部"文章动态" RSS 入口 → /cms/feed/posts）
# 模块聚合流: http://filamentv5.test/cms/feed（仅 cms 流，频道标题 = 站点名）
# 模块具名流: http://filamentv5.test/cms/feed/posts（仅 cms posts 流）
# 整站聚合流: http://filamentv5.test/feed（合并全部模块流，频道标题 = app.name）
# 根具名流: http://filamentv5.test/feed/posts
# 后台: http://filamentv5.test/admin/links
```

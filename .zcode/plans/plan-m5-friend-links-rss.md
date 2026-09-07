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

### 1.2 RSS 订阅（Feed）

**新增文件：**

| 文件 | 作用 |
|---|---|
| `addons/cms/src/Http/Controllers/FeedController.php` | 单动作控制器：RSS 2.0 输出当前 scope 已发布文章（published_at 降序，`sn-cms.feed.limit` 上限默认 50）；频道标题/描述取 GeneralSettings（site_name/seo_description→slogan 兜底）；内容类型 `application/rss+xml` |
| `addons/cms/resources/views/components/tradition/components/footer-rss.blade.php` | footer RSS 链接分部（橙色 RSS 图标 + 文案；`@props(['feedUrl'])`，经 `x-dynamic-component :component="getBladeThemeView('components.footer-rss')"` 引用，与 footer-brand 同约定） |

**修改文件：**

| 文件 | 改动 |
|---|---|
| `addons/cms/src/Support/Utils.php` | 新增 `getLinkModel()`（`getModel('link')`） |
| `addons/cms/config/sn-cms.php` + `config/sn-cms.php` | ① `models` 加 `'link' => Models\Link::class`；② `panel_register.resources` 加 `LinkResource::class`（+use 导入）；③ 顶层新增 `'feed' => ['enabled' => true, 'limit' => 50]`；④ `routes.uri` 加 `'feed' => 'feed'` |
| `addons/cms/routes/web.php` | feed 路由：根路径组（不参与 cms 前缀）、`feed.enabled` 为 true 才注册、路由名 `sn-cms.feed` |
| `addons/cms/resources/views/components/layouts/app.blade.php` | head 加 RSS autodiscovery `<link rel="alternate" type="application/rss+xml">`（feed.enabled 开启时） |
| `addons/cms/src/Livewire/Components/Footer.php` | render 增查 links（`snScope(...)->normal()->ordered()->get()`）与 `$feedUrl`（feed.enabled 开启时 `Utils::route('feed')`），传视图 |
| `addons/cms/resources/views/livewire/tradition/components/footer.blade.php` | ① 友链条：合规条上方一行平铺（"友情链接："前缀，nofollow 自动 `rel="noopener nofollow"`，空则整条隐藏）；② RSS 链接：分组形态快捷条尾部 / 全一级形态右侧链接组尾部（**空树形态不放 RSS**——契约来自 FooterTest "空树=仅品牌区+合规条"） |
| `addons/cms/resources/lang/{zh_CN,en}/cms.php` | 新增 `link_status` / `link_resource` / `link_form` / `link_table` 四节；`frontend` 节加 `friend_links` / `rss` |

**测试：** `tests/Feature/Cms/LinkResourceTest.php`（10 个用例：footer 渲染/隐藏与 scope 隔离/排序/autodiscovery/feed 内容与过滤/limit/后台页面/表单创建含 scope 断言）。当前 **74 个 cms 测试全绿**。

**演示数据：** 开发库已种 5 条友链（新华网/人民网/澎湃/虎嗅[nofollow]/36氪，order 100→60）。

## 2. 审阅要点与已知取舍

1. **`CreateLink::mutateFormDataBeforeCreate` 合并 scopeable** 是必须的（不合并 scope_type 为 null）——这与 CreatePost 逐个页面复制的模式一致；若后续嫌重复，可考虑把 scope 合并上收到 BaseResource 层（属重构，本次未做）。
2. **feed.enabled 的语义边界**：路由注册发生在 boot 期，**运行时改 config 无法注销路由**（测试里只断言了渲染层消失，没断言 404，注释有说明）。整站关闭 RSS 需改 config 文件后重载。
3. **空树形态无 RSS** 是被 FooterTest 契约约束的（用户异地优化定义的"空树=仅品牌区+合规条"），RSS 只在有导航的两种形态出现。
4. **LinkResource navigationSort=4**：排在 导航(1)/图文(2)/底部导航(2) 之后，属拍脑袋值，审阅时可按喜好调整。
5. `sn_links` 迁移的 `group_name` 字段当前**仅存储、未参与前台渲染**（footer 是单行平铺），留给将来分组渲染。

## 3. 与并行任务的冲突管控记录（重要，后续会话须知）

另一进程执行 `.zcode/plans/plan-navigation-overflow.md`（导航溢出折叠 + 无限级 + 风格化）。本任务与其**仅两个文件重叠**，已按"只动自己的区域、写前重读"处理，无冲突：

- `config/sn-cms.php`（两份副本）：对方加顶层 `navigation` 节；本任务只加 `models.link` / `LinkResource` 注册 / `feed` 节 / `routes.uri.feed`。注意：**包内副本与应用副本在 `search.display`（null vs 'page'）上存在既有差异**，属对方任务范围，勿"顺手同步"。
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
# 跑 M5 相关测试
php artisan test --compact tests/Feature/Cms/LinkResourceTest.php

# 全量 cms 回归（含导航任务测试）
php artisan test --compact tests/Feature/Cms/

# 格式化（改 PHP 必跑）
vendor/bin/pint --dirty --format agent

# 改 blade 视图后必须重建前端（否则新 Tailwind class 不生效）
npm run build

# 真机验收
# 前台: http://filamentv5.test/cms（友链条 + 快捷条尾部 RSS）
# Feed: http://filamentv5.test/feed
# 后台: http://filamentv5.test/admin/links
```

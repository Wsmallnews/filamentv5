# Media Library 使用规范与订单图片快照改进计划

> **状态：⏸️ 暂缓（2026-09-22）。** 本轮为纯分析会话，未修改任何代码。本文档自包含全部调查结论、已确认的分析判断与建议实施方案，供后续会话（无此前对话上下文）直接接手。
> 触发背景：用户就 spatie/laravel-medialibrary 提出 5 个问题（历史遗留 / 正确用法 / navigation icon 取舍 / 订单图快照 / media 存宽高），已完成分析，等待排期实施。

## 1. 环境与版本事实

- spatie/laravel-medialibrary **v11.23.8**，filament/spatie-laravel-media-library-plugin v5.8.2（经 support 子包引入，主仓库 composer.json 不直接依赖）。
- `media` 表为标准 spatie 结构（`database/migrations/2026_05_22_072708_create_media_table.php`）：含 `custom_properties` / `manipulations` / `generated_conversions` / `responsive_images` JSON 列，**无 scope_type/scope_id 独立列，也无 width/height 列**。
- `config/media-library.php`：`disk_name = public`，`queue_connection_name = sync`（默认），`media_observer` 指向 **Spatie 默认观察者**。
- medialibrary v11 **默认不存储图片尺寸**（已核实 vendor 源码无 `getimagesize` 逻辑）。
- 使用 `InteractsWithMedia` 的模型：Post、Product、Navigation（均同时实现 `HasMedia` + `HasSnSubject`）；upload 组件双轨工厂在 `addons/support/src/Filament/Forms/Concerns/HasUploadComponents.php`。

## 2. 历史调查结论（cms 改造时 media 的演进与未完成任务）

### 2.1 时间线（support / cms 子模块 git 历史）

1. support `e7aa3f7` 安装 spatie-laravel-media-library-plugin；
2. support `de8c7a5`~`da55dbf` 开发 Mediable field，曾有 `add_scopeinfo_to_media_table` 迁移（media 表加 scope_type/scope_id 独立列）；
3. support `a119fed`（2025-11-11「media 和 setting 为多租户做准备」）：**删除**了 scopeinfo 独立列迁移（改用 custom_properties JSON 方案），新建自定义 MediaObserver，新建 DatabaseSettingsRepository；
4. support `7ef97b6`：getScopeInfo 改名 getScopeable，MediaObserver 移到 `src/Observers/MediaLibrary/`；
5. cms `afe1b2c`（2025-11-24「media 自定义属性填充 scopeable 信息」）：PostForm / NavigationForm 的 SpatieMediaLibraryFileUpload 补 `->customProperties(fn ($livewire) => $livewire->getScopeable())`。

### 2.2 未完成任务（按严重程度排序，均已在当前 main 分支验证）

| # | 问题 | 证据位置 | 影响 |
|---|---|---|---|
| 1 | **自定义 MediaObserver 从未接入**：`deleted()` 特意注释掉 `removeAllFiles()`（注释：删产品时图片还需在订单 item 使用），但 config 用的仍是 Spatie 默认观察者，自定义版是死代码 | `addons/support/src/Observers/MediaLibrary/MediaObserver.php` vs `config/media-library.php:73` | 删除产品/文章 media 时物理文件被连带删除，订单快照图片 404 |
| 2 | **全系统零 conversion（缩略图）**，且订单管道引用不存在的访问器 `$product->mainUrl['medium']`（旧系统 snshop 遗留，Product/Variant 均无 mainUrl、无 medium conversion） | `addons/order/src/Pipes/Shop/Summary/Product.php:24`；全库无 `registerMediaConversions` | 订单图片快照实际取不到值（坏）；media 库缩略图价值完全未启用 |
| 3 | **custom_properties 的 scopeable/team_id 只写不读**：无任何代码按这些字段过滤/查询 media | ProductForm / PostForm 的 `->customProperties()` 写入 vs 全库无 `custom_properties` 查询 | media 多租户管理只完成写入侧 |
| 4 | （次要）**写法不统一**：ProductForm 用 `$livewire::getScopeable()` + `current_tenant()?->id`；cms PostForm 用 `$livewire->getScopeable()` 且不带 team_id | `addons/product/src/Filament/Resources/Products/Schemas/ProductForm.php:290-314` vs `addons/cms/src/Filament/Resources/Posts/Schemas/PostForm.php:91-104` | 行为不一致，统一时需选定基准 |

## 3. 已确认的分析判断（与用户确认过结论，不要重新讨论）

### 3.1 media 在系统中的正确使用方式（双轨制，现状设计正确）

| 场景特征 | 路线 | 系统内例子 |
|---|---|---|
| 模型的成套**内容资产**：多图、要排序、未来要缩略图/响应式图、多态挂载 | `FormComponents::mediaImageUpload()`（media 表） | product 主图/轮播图、post 主图/轮播图、navigation banner |
| **内联轻量**图片：单张、跟字段强耦合、无版本/缩略图需求 | `FormComponents::plainImageUpload()`（路径存字段/options） | navigation icon、小配置图 |

已做对：集合命名约定 `<model>_<purpose>`；`getSnSubjectCoverUrl()` 统一走 `getFirstMediaUrl`；列表 `->with('media')` 预加载（如 `addons/product/src/Livewire/Components/Product/Products.php:43`）。

### 3.2 navigation icon 不用 media —— **合理**

- 数据形态不符：icon 常态是图标名字符串（IconPicker → `options.icon`），不是文件资产；图片 icon（`plainImageUpload` → `options.icon_src`）是单张 UI 装饰。
- 性能不划算：导航是嵌套集树，整树渲染读 options 零额外查询；走 media 意味着 N 节点查全局 media 表，而集合排序/缩略图/多态复用的收益 icon 全用不上。
- 已知代价（接受）：`files_url()` 只拼当前 disk url 前缀，未来上 CDN/换盘时 `options.icon_src` 相对路径需自行迁移。
- 区分：navigation **banner 用 media 合理**（内容资产），本结论只针对 icon。

### 3.3 订单图片快照方案（两层配合）

诉求：订单数据不可变——产品换图/软删/media 清理均不影响历史订单。

- **第一层：快照存完整 URL（利用现有 `sn_order_items.relate_image` string 列），不存 media_id**
  - 下单时取 `$product->getFirstMediaUrl('product_image')`；注册 conversion 后可取 `getFirstMediaUrl('product_image', 'thumb')` 缩略图 URL；
  - 优点：订单自包含、渲染零额外查询、产品删除后仍可显示；溯源靠现有 `relate_type`/`relate_id`。
- **第二层：接入自定义 MediaObserver，media 记录删除时保留磁盘文件**
  - 代价：磁盘积累孤儿文件，需配定期清理命令兜底（保守策略：无 media 记录指向 + mtime 超长保留期才清；订单存 URL 字符串无法反向精确匹配，保留期从宽）。
- 隐藏保障：Product 是 SoftDeletes，常规删除不动 media 关联；第二层防御的是「后台清理 media 记录/物理删除」操作。
- **不推荐**（已评估，不再复议）：存 media `uuid`（记录删除即失效，与快照诉求矛盾）；下单字节级复制图片（存储翻倍，无合规级需求不值得）。

### 3.4 media 存图片宽高（瀑布流）——**可行，不改表结构**

- 推荐实现：监听 `Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAdded` 事件 → 用 Intervention Image（support 已依赖）或原生 `getimagesize` 读磁盘文件 → 把 `width`/`height` 写进 `custom_properties`（更新时临时 unsetEventDispatcher，避免触发观察者 `updated()` 的 manipulations 重算）。
  - 覆盖 Filament 上传 / 代码 `addMedia()` / artisan 同步全路径；不碰 vendor、零迁移。
- 前端消费：列表已 `with('media')`，`$product->getFirstMedia('product_image')?->getCustomProperty('width')`；Blade 输出 `<img width height>` 或 CSS `aspect-ratio` 防瀑布流 CLS。
- 存量数据需一次性回填命令（遍历 media 读文件写 custom_properties）。
- 仅当出现「SQL 层按尺寸筛选」需求（如横竖图分栏）才升级为独立 `width`/`height` 列 + 自定义 media_model；纯展示 JSON 值足够。

## 4. 实施步骤建议（恢复本任务时按此顺序执行）

1. **P0 接入自定义 MediaObserver**：`config/media-library.php` 的 `media_observer` 改指 `Wsmallnews\Support\Observers\MediaLibrary\MediaObserver::class`；补测试验证「删 media 记录后物理文件仍在」。
2. **P0 修复订单管道遗留代码**：`addons/order/src/Pipes/Shop/Summary/Product.php:24` 的 `$currentVariant->mainUrl['medium'] ?? $product->mainUrl['medium']` 换成 `getFirstMediaUrl`（Variant 目前无 media 关联，取产品主图即可；变体图属新需求另行评估）。全局搜索 `mainUrl` 清理其余残留（`Shortcuts/Shop.php` 等处的赋值链保留，只换取值源）。
3. **P1 注册缩略图 conversion**：Post / Product 模型补 `registerMediaConversions`（建议 `thumb` 400px / `medium` 800px，` FilamentFile`→ 光标优化，按项目规则用 EnumHelper 式惯例）；同步把 `queue_connection_name` 从 sync 改为真实队列（否则 conversion 阻塞上传请求）；订单快照改取 thumb URL。
4. **P1 宽高事件回填**：按 3.4 实现 `MediaHasBeenAdded` 监听器 + 存量回填 artisan 命令 + 瀑布流模板 `width/height` 消费。
5. **P2 media 多租户读取侧**（如需 media 后台管理再启动）：按 custom_properties 的 scope_type/scope_id/team_id 过滤查询；统一 ProductForm / PostForm 的 customProperties 写法（建议以 ProductForm 的 `::getScopeable() + current_tenant()?->id` 为基准）。
6. 每步完成后跑受影响的测试（order / product / cms 的 Feature 测试），并 `vendor/bin/pint --dirty --format agent`。

## 5. 关键文件索引

- 自定义观察者（死代码待接入）：`addons/support/src/Observers/MediaLibrary/MediaObserver.php`
- 观察者配置：`config/media-library.php:73`
- 订单快照坏点：`addons/order/src/Pipes/Shop/Summary/Product.php`、`addons/order/src/Shortcuts/Shop.php`
- 订单表结构：`database/migrations/2026_09_20_000002_create_sn_order_items_table.php`（`relate_image` string 列）
- 上传组件工厂：`addons/support/src/Filament/Forms/Concerns/HasUploadComponents.php`
- media 表：`database/migrations/2026_05_22_072708_create_media_table.php`
- 各表单 customProperties 写入：`addons/product/src/Filament/Resources/Products/Schemas/ProductForm.php`、`addons/cms/src/Filament/Resources/Posts/Schemas/PostForm.php`、`addons/cms/src/Filament/Pages/Navigation/Schemas/NavigationForm.php`
- navigation icon（plain 路线，维持现状）：`addons/cms/src/Filament/Pages/Navigation/Schemas/NavigationForm.php`（icon_type / IconPicker / plainImageUpload）
- 前端图片消费范例：`addons/product/src/Livewire/Components/Product/Products.php:43`（with('media')）

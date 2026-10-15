# media 素材管理扩展包实施计划（T11 重规划版）

> **状态：📋 待确认（2026-10-08）。** 已完成 demo（public/demo/media-library.html）两轮反馈迭代并获用户拍板，本文档为正式实施计划，确认后动工。
> 取代：`plan-media-library-usage-and-order-snapshot.md`（spatie 双轨制旧方案，已废弃；其"订单快照两层"结论被本文档 4.5 修正）。

## 0. 已拍板决策（不再讨论）

| # | 决策 |
|---|---|
| 1 | **移除 spatie/laravel-medialibrary**——但移除动作推迟到替换轮；本期 media 包与 spatie **并存**，业务表单（Product/Post/Navigation）一律不动 |
| 2 | 引用方式 **path 为主**：业务字段存相对路径 / 值对象 JSON，不存绝对 URL |
| 3 | **删除只删素材记录**，物理文件永不删；无引用扫描、无 prune、无回收站 |
| 4 | 本期**只做包**：addons/media 包 + demo 对照验收，不接任何业务表单 |
| 5 | **无 scope_type/scope_id**；保留 `team_id`（租户隔离）；业务维度（商城/门店）用 path 目录前缀表达 |
| 6 | 缩略图走 **URL 变换层**（OSS/COS 按需参数，本地盘出原图）；只存原图，无预生成、无队列 |
| 7 | 全格式支持（图片/视频/音频/PDF/Office/压缩包/任意），宽高仅图片计算 |
| 8 | 统一上传管道（MediaManager），三种 UI 形态（弹窗选择 / 内联 mini / 编辑器原生）共用；富文本一期不收编 |
| 9 | 值对象契约统一 JSON（含 icon 类字段）；`media()` presenter 兼容纯 string 老字段 |
| 10 | 素材管理页 = 资源管理器形态：大图标（默认）/ 详细信息双视图、累积单击多选、hover 快捷操作（预览/复制路径/下载）、批量删除、ratio 调用处自定义 |

## 1. 本期范围

**做**：新包 `addons/media`（Wsmallnews\Media / sn-media）全部内容——表、上传服务、URL 变换层、下载路由、MediaPicker 表单字段 + 选择弹窗、素材管理页、helpers、测试。

**不做**（后续轮次）：业务表单替换与 spatie 移除；订单管道 `relate_image` 改存 path；富文本/Markdown 编辑器插图收编；下载中心/研究成果场景接入；hash 查重/秒传；EXIF 方向修正与 webp 转存（列为后续可选）；视频封面/时长。

## 2. 包骨架与注册

```
addons/media/
├── composer.json                # name: wsmallnews/media；require wsmallnews/support + spatie/laravel-package-tools；
│                                #   autoload.files: src/Helpers/helper.php（对齐 support）
├── config/sn-media.php
├── database/migrations/create_sn_media_table.php.stub   # 主仓库执行版同步（database.md 双侧规则）
├── src/
│   ├── MediaServiceProvider.php # PackageServiceProvider；ModuleRegistry 登记（plugin: MediaPlugin::class）
│   ├── MediaPlugin.php          # Filament Plugin（id: sn-media，参照 CommentPlugin traits 组合）
│   ├── Commands/MediaInstallCommand.php
│   ├── Models/Media.php
│   ├── Enums/MediaType.php      # image/video/audio/pdf/word/excel/ppt/zip/file，HasLabel+HasColor+HasIcon+EnumHelper
│   ├── Exceptions/MediaException.php   # 继承 SupportException；异常消息英文（addons-src 规则）
│   ├── Support/
│   │   ├── MediaManager.php     # 上传服务（容器单例，不设 Facade——避免与 spatie Media 概念歧义）
│   │   ├── MediaValue.php       # presenter（media() 返回值）
│   │   └── Utils.php            # 静态工具（getConfig/getModel/getDisk，对齐各包惯例）
│   ├── Filament/
│   │   ├── Forms/MediaPicker.php        # 表单字段（P2）
│   │   └── Pages/MediaPage.php          # 素材管理页（P1）
│   ├── Livewire/Components/Picker.php   # 选择弹窗组件（P2）
│   └── Http/Controllers/MediaDownloadController.php
├── resources/
│   ├── views/...                # 字段视图 / 管理页视图 / picker 弹窗视图
│   ├── css/index.css            # 遵循 css.md 规则与 sn-* 体系
│   └── js/components/media-manager.js   # Alpine 资产组件（管理页本地交互态）
└── tests/                       # Pest（包内）+ 主仓库 tests/Feature/Media 集成测试
```

注册点：根 `composer.json`（repositories path + `wsmallnews/media: v1.x-dev`）→ `composer dump-autoload`；`AdminPanelProvider->plugins()` 加 `MediaPlugin::make()`。

## 3. 数据表 sn_media

| 列 | 类型 | 说明 |
|---|---|---|
| id | bigint PK | |
| team_id | unsignedBigInteger nullable + index | 租户；模型必须加 `team()` 关联（database.md 规则） |
| disk | string(32) | 写入时的活动盘（账本用途；渲染默认走 config 盘） |
| path | string + **unique** | 相对路径，唯一身份证，生成后不可变 |
| name | string | 原始文件名（下载/展示用，可改） |
| ext | string(16) | 扩展名（小写） |
| mime | string(128) | |
| size | unsignedBigInteger default 0 | 字节 |
| width / height | unsignedInteger nullable | 仅图片 |
| alt | string nullable | 可编辑 |
| uploader_type / uploader_id | nullable morphs | 上传者审计 |
| created_at / updated_at | timestamps | **无 softDeletes**（拍板 3：物理删记录） |

不存的列：scope_type/scope_id（拍板 5）、directory（从 path 前缀派生虚拟目录树）、hash（一期不做查重）。

`Media extends SupportModel`，成员顺序按 models.md 规则；`MediaType::fromMime()` 负责类型映射（图片→瓦片色/中文标签用枚举，色板对齐 demo：pdf red / word blue / excel green / ppt orange / video purple / audio pink / zip amber / file gray，图标一律 `Heroicon::Outlined*`）。

## 4. 核心契约

### 4.1 path 规则与目录约定
- 生成：`{directory}/{Ymd}/{Str::random(8)}.{ext}`，如 `sn/shop/product/202610/8f3kq2.jpg`；目录前缀由调用处传（模块语义），缺省 `config('sn-media.directory')`（`sn/media`）。
- 不可变：不移动、不改存储名；素材库"重命名"只改 `name` 列。
- 虚拟目录树 = path 前缀分组，无 folder 表；过滤 = `where('path', 'like', $prefix.'%')`。

### 4.2 值对象（字段保存值）
```json
{ "path": "…", "name": "原始名.jpg", "mime": "image/jpeg", "size": 421888, "width": 800, "height": 1000 }
```
单值字段存对象（模型 cast `array`）；多值字段存对象数组，**数组序即排序**；非图片 width/height 为 null。业务字段渲染零查询；老 string 字段由 presenter 兼容。

### 4.3 URL 变换层（helpers）
- `media($value): MediaValue`——接受 string|array|null|Media；string 时 `Media::findByPath` 反查（请求级静态缓存）补齐元数据。
- `media_url($value, ?int $w = null): ?string`——presenter 快捷。
- `MediaValue`：`url(?int $w)` / `downloadUrl()` / `name` / `width` / `height` / `size` / `mime` / `isImage`。
- transforms 覆盖主流对象存储（一期只做宽度档；`{w}` 为宽度占位，作为 query string 追加）：
```php
'transforms' => [
    'providers' => [
        'oss'   => ['resize' => 'x-oss-process=image/resize,w_{w}'],        // 阿里云 OSS
        'cos'   => ['resize' => 'imageMogr2/thumbnail/{w}x'],               // 腾讯云 COS（数据万象）
        'qiniu' => ['resize' => 'imageView2/2/w/{w}'],                      // 七牛
        'obs'   => ['resize' => 'x-image-process=image/resize,m_lfit,w_{w}'], // 华为云 OBS
        's3'    => null,   // Amazon S3 无原生图片处理：出原图；接 CloudFront+Lambda 自助缩图后自行配置模板
    ],
    // 盘名 → provider 键（优先于 driver 推断；OBS 复用 s3 driver，靠这里区分 AWS 与华为）
    'disks' => [
        // 'obs' => 'obs',
    ],
],
```
- **provider 解析顺序**：`transforms.disks[盘名]` → `transforms.providers[filesystems.disks.{盘}.driver]` → 无匹配出原图。driver 直配键：oss/cos/qiniu/obs/s3（社区 OSS/COS/七牛适配器的 driver 名即其名）。
- **华为云接入评估结论（已验证）**：Laravel 原生支持——OBS 提供 S3 兼容 API，用 `league/flysystem-aws-s3-v3`（Laravel 自带依赖）配 `driver: s3 + endpoint: https://obs.{region}.myhuaweicloud.com` 即可上传，**无需引入华为官方 PHP SDK**（体积大、无 Laravel/Filament 生态）；图片处理走 `x-image-process` 参数。

### 4.4 下载（原始文件名）
- 路由 `GET /sn-media/download/{media}`（name `sn-media.download`，ServiceProvider loadRoutesFrom）。
- 本地盘：`response()->download(Storage::disk()->path(), $media->name)`。
- 云盘：redirect `temporaryUrl($path, +5min, ['ResponseContentDisposition' => "attachment; filename=…"])`。
- `MediaValue::downloadUrl()` 生成路由；presenter 与管理页 hover 下载按钮走同一入口。

### 4.5 订单快照（结论修正，替换轮执行）
新模型的不可变性由「path 不可变 + 物理文件永不删」保证，订单 `relate_image` 列**存 path** 即可（不再是旧计划的完整 URL）：换盘/换 CDN 域名只改 config，历史订单 URL 由渲染层重算；缩略尺寸由 `media_url($path, 200)` 现场决定。前提：订单图统一来自素材管道（拍板 8 保证）。

## 5. 组件设计

### 5.1 MediaManager（上传管道）
```php
public function upload(UploadedFile $file, ?string $directory = null, ?Model $uploader = null): Media;
public function value(Media $media): array;      // 模型 → 值对象
public function uploadValue(...): array;          // 字段侧一步到位
```
- 校验：mime 白名单 + 大小上限（config `mimes` / `max_size`；默认全开常见格式、禁可执行类）。
- 宽高：`getimagesize($file->getRealPath())`，仅 `image/*`；原样存储（不转码、不转方向，见 §1 不做）。
- team_id：`Filament::getTenant()?->getTenantKey()`（panel 上下文），无租户上下文为 null；uploader 默认 `Filament::auth()->user()`。

### 5.2 MediaPicker 字段（P2）
- `Filament\Forms\Components\Field` 子类；配置项：`multiple()` / `acceptedMimes()`（含 `acceptImages()` 语法糖）/ `ratio(int|float)`（默认 1）/ `directory()` / `maxItems()` / `maxSize()`。
- state：单值对象 / 对象数组；视图 = 空态触发框（按 ratio 占位）+ 已选预览卡（替换/移除，多值含排序）。
- 弹窗：字段自带 Filament Action modal，内容为 Livewire 组件 `sn-media::components.picker`（Components/ 内嵌组件，props 注入 multiple/accept/ratio/directory，不读模块默认值——livewire.md 规则）。
- Picker 两个 tab：素材库（服务端分页网格 + 类型/目录过滤 + 搜索）/ 本地上传（WithFileUploads 多文件 + 进度，**上传完成即入库并自动选中**）。
- 回填：确认后 dispatch `sn-media-selected`（payload: values 数组 + 目标字段标识，键名与监听参数精确匹配——component-events 规则），字段视图 Alpine 监听（局部 UI 动作）写回 `$wire.$set`。事件登记进 component-events.md 事件表。
- 多值排序：一期上下移 + 移除；拖拽（SortableJS）二期可选。
- **P2 开工先做 spike**：字段 + 弹窗 + 事件回填最小闭环验证（Filament v5 自定义字段 modal 的实现路径），通过后再铺开 UI。

### 5.3 MediaPage 素材管理页（P1）
- `Filament\Pages\Page` 子类（页面即 Livewire 组件），MediaPlugin 经 `panel_register.pages` 注册；不 use Scopeable（无 scope 语义，config 不声明 scopeables——若注册链强校验再按 main 兜底，实施时验证）。
- 视图移植 demo：工具栏（面包屑/排序/视图切换/搜索/上传）+ 左树（类型 + path 虚拟目录树）+ 大图标/详细信息双视图（切换记忆 localStorage）+ 状态栏。
- 交互分工（component-events 规则）：
  - 服务端 `wire:click`：查询过滤/搜索/排序/分页、批量删除、重命名 name、编辑 alt、预览（设 `$previewId` 弹 lightbox）、上传落库；
  - Alpine 资产组件 `media-manager.js`（走 Filament 资产管线，禁止视图内联脚本）：卡片累积多选本地态（点选/取消）、视图切换、hover 操作、拖拽上传 drop 区；批量删除时 `$wire.call('batchDelete', ids)` 传参。
- 复制路径：`navigator.clipboard`（JS 侧）；下载：走 §4.4 路由。
- 导航：翻译键**纯字符串**（config.md 闭包毒化规则），icon `Heroicon::OutlinedPhoto`。

## 6. 多租户
- 上传写入 team_id；管理页与 Picker 查询按 `Filament::getTenant()` 过滤；无租户 panel（现状）显示全量。
- 未来租户独立 bucket：disk 列已备，per-tenant disk 解析二期再做。

## 7. 实施批次与测试

| 批次 | 内容 | 测试（Pest） |
|---|---|---|
| **P0 基础层** | 包骨架/注册（composer/SP/Plugin/InstallCommand/ModuleRegistry）+ 迁移双侧 + Media 模型 + MediaType 枚举 + MediaManager + helpers（media/media_url/transforms）+ 下载路由 | MediaManagerTest（fake storage：落盘/path 唯一/记录字段/值对象形状）；MediaValueTest（string/数组/Model 归一、本地 url、oss 参数追加）；DownloadTest（Content-Disposition 原始文件名） |
| **P1 管理页** | MediaPage（网格/列表/树/搜索/批量删/上传/预览/重命名/alt）+ JS 资产 + 样式 | MediaPageTest（渲染/过滤/删除记录后文件仍在 Storage::assertExists/重命名）；上传入账测试 |
| **P2 字段** | spike → MediaPicker + Picker 弹窗 + 事件回填 + 多值排序 | PickerTest（Livewire 渲染/选择确认 dispatch payload）；MediaPickerFieldTest（state 读写/多值） |

每批：`vendor/bin/pint --dirty --format agent` + 主仓库 `php artisan test --compact tests/Feature/Media`；完成后**停在未提交状态**（general.md：不主动 commit）。

## 8. 验收对照（demo 逐项）
1. 管理页四布局区（工具栏/树/内容区/状态栏）与 demo 视觉一致；2. 大图标卡片（图片缩略图 + 非图片类型瓦片）+ 详细信息双视图 + localStorage 记忆；3. 累积单击多选 + 清除选择按钮（无 Ctrl、无空白清空）；4. hover 三操作（预览/复制路径/下载）；5. 批量删除 toast 文案（仅删记录文件保留）；6. 拖拽/按钮上传 → 进度 → 入库出现；7. 预览 lightbox 五行元信息 + 下载原始文件名；8. Picker 弹窗双 tab、库过滤类型不匹配置灰、上传即入库自动选中；9. 四类字段形态（单值/多值排序/mini/banner 3:1）+ JSON 契约面板一致；10. `media_url` 本地盘出原图、oss 盘追加 `x-oss-process` 参数。

## 9. 风险与规则引用
- 迁移双侧同步 + `team_id → team()` 关联（database.md）；模型成员顺序 + Attribute 新写法（models.md）；Eloquent 链顺序（addons-src.md）；异常消息英文（addons-src.md）；panel_register 翻译字符串键（config.md）；Heroicon 枚举传参（filament.md）；Blade @class 双属性与组件标签 x-bind: 陷阱（livewire.md）；嵌套组件内联 Alpine 禁止、交互 JS 走资产管线（livewire.md）；事件命名 `sn-media-selected` 进登记表（component-events.md）。
- 技术风险：Filament v5 自定义字段 + modal 含 Livewire 组件的回填链路（P2 spike 先行验证）；Livewire 多文件上传的临时文件大小配置（`livewire.temporary_file_upload`）。

## 10. 后续轮次预告（不在本期）
1. **替换轮**：Product/Post/Navigation 表单换 MediaPicker；模型 cover 方法改 `media()` presenter；删除 spatie 依赖与 media 表；订单管道 `mainUrl` 坏点修复 + `relate_image` 存 path。
2. 富文本插图收编（自定义 toolbar action）。
3. 下载中心 / 研究成果（证书）场景接入。
4. EXIF 方向修正 / webp 转存 / hash 查重 / per-tenant disk。

# media 扩展包审查清单（回喂 ZCode 用）

> 审查对象：`addons/media`（Wsmallnews\Media / sn-media）
> 审查时间：2026-10-12
> 审查维度：代码 / 代码风格 / 功能 / 管理界面 / 安全 / 工程测试
> 说明：每一条都是可执行的"问题 → 位置 → 改法"。请 ZCode 逐条对照优化。

---

## 一、核心问题：Unlisted 功能与原始需求方向相反（最重要）

### 原始需求
- 有些特殊上传（如**支付证书**）**不能在素材库中管理**；
- 用 Filament 原生上传表单样式不一致，需要给方案。

### 现状（ZCode 给的方案）
`sn_media` 表加 `unlisted` boolean 列 + `MediaPicker->unlisted()`：
文件**照样入库**，只在素材库默认视图折叠；管理页工具栏加"眼睛斜杠"开关随时可再次显示。

### 问题
1. **方向反了**：要的是"隔离出素材库"，给的是"藏进素材库同一个抽屉 + 一个开关翻开"。
   - 支付证书仍存在同一张 `sn_media` 表、同一个「素材管理」页，管理员点开关即可看到/改名/删除所有"私有"文件。
   - 位置：`src/Filament/Pages/MediaPage.php`（`showUnlisted`）、`resources/views/filament/pages/media.blade.php`（眼睛按钮）。
2. **"私有/敏感"只是 UI 假隐私，无任何安全边界**（见第二节）。
3. **unlisted 池是全局扁平的，不按目录隔离**：
   - `src/Livewire/Components/Picker.php` 的 `mediaQuery()` 只按 `unlisted` 分池，**完全不过滤 `directory`**。
   - `->directory('sn/test/private')` 只决定新上传落盘路径，不影响弹窗"素材库"tab 展示范围。
   - 计划 §5.2 明确写了"类型/**目录**过滤"，实现漏掉目录过滤。
   - 后果：支付证书、私密文档共用一个大池子，选的时候互相可见。
4. **测试页把需求做拧了**：你真正要解决"支付证书"，但测试页里 `certificate`（仅 PDF）做成了**普通 listed 字段**，反而是演示性的 `secret`/`secret_gallery` 用了 `unlisted`。
   - 位置：`app/Filament/Pages/MediaPickerTestPage.php`。

### 改法（按原话重做）
- 若目的是"不进素材库 + 样式统一"：做一个**不写 `sn_media` 表**的字段形态（走 MediaManager 只落盘、值直接存 path 字符串），而不是"入库 + 打隐藏标"。
- 若要保留入库做审计：`unlisted` 必须变成真正有边界的私有池——**至少按 `directory` 分池 + 访问/下载加鉴权**。
- 命名建议：`unlisted`/`listed` 成对 + 素材库工具栏裸开关与"敏感"定位冲突，改成 `visibility/private/restricted` 语义并带权限判断，或去掉工具栏开关。

---

## 二、安全缺陷（P0，最优先修）

1. **下载路由零鉴权**
   - 位置：`routes/media.php`（`GET /sn-media/download/{media}`，仅 `web` 中间件，按 `id` 路由绑定，无 auth / 无签名）。
   - 风险：任何人枚举 id 即可下载任意文件（含"私有"支付证书）。
   - 改法：加 auth 中间件（Filament 面板认证）或带签名的临时 URL。
2. **文件落盘 visibility = public**
   - 位置：`config/sn-media.php`（`'visibility' => 'public'`）。
   - 风险：随机 URL 可直接访问。
3. **扩展名白名单可绕过，且命名误导**
   - 位置：`src/Support/MediaManager.php` 的 `validate()`。
   - 现状：校验 `getClientOriginalExtension()`（扩展名）而非真 MIME 嗅探，改后缀即可绕过；config 名 `mimes` 实为扩展名。
   - 改法：改真 MIME 校验 + 更名或加注释说明语义。

---

## 三、功能性 bug

1. **`media($value)->alt` 永远 null**
   - 位置：`src/Models/Media.php` 的 `toValue()`（不含 `alt`）、`src/Support/MediaValue.php` 的 `__get()`（只读 `$this->value`，不回落 `$this->model`）。
   - 现状：README 示例 `media($product->image)->alt` 失效。
   - 改法：值对象带上 `alt`，或 `__get` 回落 model。
2. **`MediaValue` 内部读取不一致**
   - `url()/downloadUrl()` 读 `$this->model`，`__get()/isImage()/toArray()` 只读 `$this->value`。
   - 改法：统一取值来源。
3. **Picker 的 mime 过滤放在分页之后**
   - 位置：`src/Livewire/Components/Picker.php` 的 `medias()`（`setCollection(filter)`）。
   - 现状：`total()/lastPage()` 仍是未过滤总数 → 分页页码错乱、可能出现空页。
   - 改法：过滤改 SQL 侧或修正分页计数。
4. **上传落盘与写库非事务**
   - 位置：`src/Support/MediaManager.php` 的 `upload()`（先 `Storage::put` 再 `Model::create`）。
   - 现状：写库失败留下"黑户文件"，与 README"没有黑户文件"自宣矛盾。
   - 改法：加事务或失败补偿（删除已落盘文件）。
5. **查询性能：`typeCounts` 每条渲染 ~9 次 COUNT / 目录树全量 pluck 后 PHP 算祖先**
   - 位置：`src/Filament/Pages/MediaPage.php` 的 `typeCounts()` / `directories()`。
   - 现状：`ofMediaType` 的 `mime like` 无索引，数据量大时管理页变慢。
   - 改法：减少 COUNT、dirname 索引化或缓存。
6. **`sortBy` 未加 `#[Url]`**
   - 现状：搜索/类型/目录/视图/`showUnlisted` 都进 URL，唯独排序不写回，切排序刷新不同步。

---

## 四、代码 / 风格（对照 .ai/rules）

1. **`MediaPlugin::__call` 魔法代理**
   - 位置：`src/MediaPlugin.php`（`@method` + `__call` 转发到 `Utils` 静态方法）。
   - 改法：直接实现 `getPanelRegister()`，去掉魔法。
2. **无用的 morph 别名**
   - 位置：`src/MediaServiceProvider.php`（`Relation::enforceMorphMap(['sn_media' => Media::class])`）。
   - 现状：Media 不作为任何 morph 的 target，注册无意义别名还污染全局 morph map。
   - 改法：删除。
3. **`getTitle()` 用 `self::$title`**
   - 位置：`src/Filament/Pages/MediaPage.php`（`self::$title` 恒 null，等于永远走 `__()`，死代码）。
   - 改法：改 `static::$title` 或简化。
4. **非图片瓦片 label 硬编码英文，且重复两处**
   - 位置：`resources/views/filament/pages/media.blade.php` 与 `resources/views/livewire/components/picker.blade.php` 的 `MP4/MP3/PDF/DOC/XLS/PPT/ZIP/FILE`。
   - 改法：抽成组件或从 `MediaType` 枚举暴露，走翻译。
5. **大量手写 Tailwind 而非 Filament 原生组件**
   - 现状：排序 select、分页按钮、弹窗、输入框都是手写；弹窗是 `wire:ignore` 里的 `fixed overlay` modal，z-index/焦点/a11y 自扛。
   - 计划 §5.2 原写"字段自带 Filament Action modal"。
   - 改法：改用 Filament 原生组件与 Modal，统一风格（这也正是你抱怨"样式不一致"的同源问题）。

---

## 五、管理界面端（浏览器实测）

1. **左侧目录树 <768px 整段消失且无替代**
   - 位置：`resources/views/filament/pages/media.blade.php`（`hidden md:block`）。
   - 现状：小屏/窄窗无抽屉无下拉，浏览器 735px 实测看不到目录树。
   - 改法：加移动端折叠方案。
2. **"显示隐藏文件"开关裸放在工具栏**
   - 与"私有/证书"定位冲突（见第一节），建议收进权限或去掉。

---

## 六、工程 / 测试

1. **包内 `tests/` 全是脚手架**
   - `ExampleTest`（can test）、`DebugTest`（禁 dd/dump/ray）、`ModelFactory` 全文注释；真测试全在主仓库 `tests/Feature/Media`。
   - 计划说"包内 + 主仓库"双轨，实际包内零业务测试。
2. **遗留临时脚本 `\.t10-ref\add-unlisted.php`**
   - 加列的临时脚本，列已并入迁移，应删除。
3. **composer 声称 `filament/filament ^4.0 || ^5.0`**
   - 代码用 v5 的 `Filament\Schemas\Components\Section` 等 API，未验证 v4 兼容。
   - 改法：收紧为 `^5.0` 或补 v4 矩阵。

---

## 七、优化优先级总表

| 优先级 | 改动 |
|---|---|
| P0 | 下载路由加鉴权/签名 |
| P0 | 重新对齐 Unlisted 语义（"隔离/私有池" vs "UI 隐藏"），按原话重做 |
| P1 | Picker 补目录过滤；mime 过滤改 SQL 侧或修正分页计数 |
| P1 | 修 `media()->alt` 失效 + 统一 `MediaValue` 内部读取 |
| P1 | 上传落盘/写库加事务或失败补偿 |
| P2 | `typeCounts`/`directories` 查询优化 |
| P2 | 删 `MediaPlugin::__call` 魔法、无用 morph map、`self::$title` |
| P2 | 抽掉重复的瓦片 label 硬编码，改翻译 |
| P3 | 目录树小屏适配 |
| P3 | 清理脚手架测试、`.t10-ref` 残留、收窄 composer 版本约束 |
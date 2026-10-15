# media 扩展包 · 后续任务总结（2026-10-13）

> 本文档汇总所有已讨论但推迟处理的问题，后续统一处理。新问题由 ZCode 记录到本文档末尾。
> 格式：问题 + 用户思路 + 当前状态。

---

## 一、已拍板但未实施的功能

### 1. modal 换 Filament 原生（部分完成）
- **现状**：目录新建/重命名/删除已用 Filament Action modal ✅；移动到目录弹层仍是 Alpine 自绘 ❌；预览弹层仍是手写 modal ❌
- **用户思路**：所有弹窗/modal 统一走 Filament（Action modal / form modal），保持风格一致
- **优先处理**：移动弹层改为 Filament Action（含目录选择表单）；预览弹层改为 Filament Action 或 Infolist

### 2. 预览弹层补齐操作按钮
- **现状**：代码已加"移动到目录"和"复制路径"，但浏览器未验证（可能 CSS 未构建）
- **还需加**：删除按钮改为 Filament Action + requiresConfirmation

### 3. 单张素材 hover 操作 → ActionGroup `⋯`
- **用户思路**：grid 视图 hover 一个 `⋯` 按钮（ActionGroup 强制折叠）；list 视图用 `ActionComponents::recordActions()` 读全局配置决定折叠/平铺
- **操作项**：预览 / 复制路径 / 下载 / 移动到目录 / 删除（确认弹框）
- **同一组 Action 在预览弹层里复用**

### 4. 批量操作 → Filament Action
- **用户思路**：批量删除改为 `mountAction('snMediaDeleteSelected', { ids })` + requiresConfirmation；移动改为 Filament form modal 选目录
- **参考**：`ActionComponents::bulkAction()` 的安全模式

### 5. 分页 → 复用 Filament table 分页 blade 组件
- **方案 A（已拍板）**：`<x-filament-tables::pagination>` 传入 paginator + pageOptions，样式自动跟随 Filament 主题
- **全站 table 分页模式统一**：用户查到可用 `Table::configureUsing()` 在 ServiceProvider boot 里全局设置：
  ```php
  \Filament\Tables\Table::configureUsing(function (Table $table): void {
      $table->paginationMode(PaginationMode::Default)
          ->paginationPageOptions([25, 50, 100])
          ->defaultPaginationPageOption(25);
  });
  ```
  **但素材页没有 Table 对象无法读到**——素材分页仍走 blade 组件复用方案

---

## 二、已确认推迟的功能

### 6. 下载路由鉴权
- **用户拍板**：先搁置，没有真实场景不好决策
- **背景**：公开素材的直链（/storage/...）必须保留；敏感文件走私有盘 + 签名 URL

### 7. 富文本/Markdown 编辑器插图收编
- **用户思路**：一期保持 Filament 原生上传（体验不打断），二期用自定义 toolbar action 收编入库

### 8. 业务表单替换 + spatie 移除
- **范围**：ProductForm/PostForm/NavigationForm 换 MediaPicker；删 spatie 依赖与 media 表；修订单管道 mainUrl 坏点
- **依赖**：media 包功能稳定后执行

### 9. 订单管道 relate_image 改存 path
- **背景**：新模型 path 不可变 + 文件永不删，订单快照只存 path 即可（不再存完整 URL）

### 10. filament-nestedset 侧边栏导航组件
- **用户思路**：后续给 filament-nestedset 做一个可编辑管理的导航形组件，如 cms post 列表左侧展示分类 + 可管理分类 + 点击分类筛选右侧 post；素材管理也可切换到这个组件
- **当前素材左树**：保持自建

---

## 三、技术优化（不急）

### 11. hash 查重提示版
- **场景**：上传时查 hash 提示"素材库已有相同文件"，让用户选复用还是新传
- **背景**：当前是自动秒传（当天同内容同 path），不做提示

### 12. 包内测试（统一批次）
- **现状**：包内 tests/ 全脚手架，真测试在主仓 tests/Feature/Media
- **用户思路**：各包功能完善得差不多时统一补 testbench 独立测试
- **背景**：所有包都会上 Packagist，需要独立可验证

### 13. composer 版本约束统一收紧
- **现状**：所有包 `^4.0 || ^5.0`，未验证 v4
- **用户思路**：暂不收紧，生态统一处理

### 14. typeCounts 查询进一步优化
- **现状**：已从 9 次 COUNT 改为单查询条件聚合；后续十万级数据可能需要缓存
- **依赖**：真有性能压力再做

---

## 四、已知问题（待修复）

### 15. 复制路径在 HTTP 环境下不可用
- **根因**：`navigator.clipboard` 只在 HTTPS 可用
- **用户拍板**：不修，HTTPS 下正常即可

### 16. 素材管理页 content 区高度
- **现状**：整页跟着浏览器滚动，内容区没有固定高度
- **方向**：内容区 `max-h-[calc(100vh-300px)]` + overflow-y-auto；或降低每页条数（12）

### 17. 列表视图横向滚动
- **修复方向**：列表容器 `min-w-0` + 内层 `min-w-[600px]`

---

## 新增记录区

> 后续用户说"某某功能后续再做"时，ZCode 在此追加。

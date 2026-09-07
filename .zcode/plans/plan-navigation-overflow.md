# CMS 导航系统重构执行计划(PC 溢出折叠 + 无限级 + 风格化)

> **状态:✅ 主体完成 + 6 轮迭代反馈全部闭环(2026-09-07)。** 初版 7 阶段执行完毕后,经历 6 轮用户反馈迭代(见文末「迭代记录」),当前 Cms 测试套件 81 项全过。后续用户自定义优化将基于本文档继续。
> 本文档自包含全部背景、已确认决策、实现步骤与迭代决策,供后续会话(无此前对话上下文)直接接手。
> 交互演示页随本计划一起提交:`public/demo/navigation-overflow-demo.html`(按项目规则永久保留于 `public/demo/`)。

## 1. 背景

- 问题:CMS 前台 PC 模式(lg+)导航数量过多时溢出容器。复现:`http://filamentv5.test/cms`,12 个一级导航 × `min-w-32`(128px)≈ 1536px,1280px 视口下 container 仅 ~1152px,末尾导航项被截断。
- 用户要求:超出部分折叠进「更多」菜单(纯图标 ⋯),更多按钮自动占据最后一个能放下的导航位置;同时支持无限级导航、可配置的展开形式与导航风格。
- 现有代码:
  - 主视图(PC 三级硬编码 + 移动端手风琴):`addons/cms/resources/views/livewire/tradition/components/navigation/navigation.blade.php`
  - 移动端递归 item 组件:`addons/cms/resources/views/components/tradition/components/navigation/navigation-record.blade.php`
  - Livewire 组件类:`addons/cms/src/Livewire/Components/Navigation/Navigation.php`(`getNestedset()` 已返回 `toTree()` 整树,children 全部预加载,无 N+1)
  - 模型:`addons/cms/src/Models/Navigation.php`(已有 `url_info` / `is_active` / `has_active` / `name_label`)
- 技术栈:Filament v5 + Livewire v4 + Alpine.js(现有视图为内联 `x-data` 风格,新代码保持一致)+ Tailwind v4。
- 演示页是纯静态原生 JS 实现;正式实现时把逻辑翻译成 Alpine,不要照搬原生 JS。

## 2. 已确认决策(与用户逐条确认过,不要重新讨论)

### 2.1 溢出折叠(PC 主行)

- Alpine + ResizeObserver 测量:主行全量渲染 → 逐项累计宽度 → 第一个放不下的项起全部隐藏,「更多」按钮显示。
- 「更多」按钮:形态由 `more_icon_only` 配置——`true`(默认)纯图标 ⋯(ellipsis-horizontal);`false` 显示文字(「更多」+ chevron 图标)。两种形态都必须保留 `aria-label`(多语言),溢出测量预留宽度取实际按钮宽度(纯图标约 48px,带文字约 96px)。
- 重新测量时机:组件初始化、ResizeObserver 监听容器、`document.fonts.ready` 之后(字体加载改变项宽)。
- 被折叠项中含激活态(`has_active`)→ 「更多」按钮高亮。
- 不依赖后端:数据服务端已全量渲染,JS 只做显隐。

### 2.2 无限级导航(替代现有三级硬编码)

- **级联(cascade)**:一级向下弹,二级+向右弹;子菜单右缘超出视口时 JS 实时检测(`getBoundingClientRect`)反向向左弹。
- hover 防断链三件套(演示页 `setupCascade` 已验证):子菜单左缘与父行重叠 5px、关闭延迟 300ms、打开即时 + 祖先链保持 + 同级互斥。
- **手风琴(accordion)**:递归嵌套折叠。父子级区分 = **子级文字缩进 + 父级字重 600 / 叶子字重 400,不使用竖线**(用户反馈竖线突兀,已移除)。缩进块必须在**链接元素(`<a>`)内部**,不能做在子级容器 margin 上——否则 hover 背景从缩进位置开始不满行。用户移动端现有实现即此方案:**hover/选中背景必须通栏**(从面板左缘到右缘)。
- **缩进实现(用户对齐的要点)**:参考用户移动端现有方案(`navigation-record.blade.php` 里 `@for` 循环输出 `<div class="w-4">` 缩进块)——**链接元素(`<a>`)内部、文字之前重复 N 个固定宽度的缩进块(Tailwind 刻度,如 `w-5` = 20px,N = depth)**,不用 inline style 的 padding-left。这样既保证纯 Tailwind class,又因块在 `<a>` 内部而保持 hover/选中背景通栏。深度来源:模型基于 kalnoy/nestedset(经 filament-nestedset),查询链加 `->withDepth()` 后模型直接访问 `->depth`(根节点从 **0** 开始,`toTree()` 后属性保留),参照 `addons/filament-nestedset/src/Livewire/Components/Nestedset.php` 的标准做法。cms 的 `addons/cms/src/Livewire/Components/Navigation/Navigation.php` 的 `getNestedset()` 当前**没有** withDepth,实现时改为:`$this->getScopedQuery()?->normal()->defaultOrder()->withDepth()->get()->toTree()`。递归 partial 直接用 `$record->depth`,不需要手动传递层级变量。
- **纯展开模式的行必须整行一体**:文字区与右侧箭头是同一个"展开"动作,hover 任一处时整行(文字+箭头)联动高亮、点击任意处都展开,不能两块各自独立响应(用户明确指出的问题;实现可用 `.acc-row:has(> .row-toggle):hover` 或等价的行级 hover)。

### 2.3 配置模型(`config/sn-cms.php` 顶层新增 `navigation` 节)

```php
'navigation' => [

    // 整体风格:primary = 主题色底+白字(现状默认);minimal = 白底简约,亮暗自适应
    'style' => 'primary',

    // PC 主行(lg+)子菜单展开形式:cascade = 级联(一级向下,深层向左/右弹)| accordion = 手风琴
    'desktop_submenu_style' => 'cascade',

    // 主行子菜单触发:hover | click(仅 cascade 有效;accordion 固定 click)
    'desktop_submenu_trigger' => 'hover',

    // 仅「hover 级联」生效:父项是否可点击(直达第一个可用叶子)
    'parent_clickable' => true,

    // "更多"下拉(溢出折叠)里的展开形式:accordion(默认,窄面板更稳)| cascade
    'more_submenu_style' => 'accordion',

    // "更多"下拉里 cascade 形式的触发(accordion 时此值忽略)
    'more_submenu_trigger' => 'click',

    // "更多"按钮仅图标(⋯),不显示文字
    'more_icon_only' => true,
],
```

注意:配置文件已有的 `contents.navigation`(内容表单)和 `models.navigation`(模型映射)是不同层级的既有键,与新顶层 `navigation` 节不冲突;新配置读取路径为 `sn-cms.navigation.*`。

### 2.4 父项可点击规则(用户定义的互斥规则)

| 展开形式 | 触发方式 | 父项可点击? |
|---|---|---|
| 级联 | hover | ✅ 直达第一个可用叶子(受 `parent_clickable` 控制) |
| 级联 | click | ❌ 纯展开(点击语义被展开/收起占用) |
| 手风琴 | 固定 click | ❌ 纯展开 |

- 无效组合自动降级:accordion 时 trigger 强制 click;非 hover 级联时 `parent_clickable` 强制无效。
- **注意**:演示 3 展示的「手风琴 + 父项直达链接 + 右侧箭头展开」混合形态已被用户后续决策**放弃**,不在最终模型内(演示页保留仅作历史参考,不要实现它)。

### 2.5 直达目标:`first_leaf_url`(模型新增 accessor)

```php
// addons/cms/src/Models/Navigation.php
protected function firstLeafUrl(): Attribute
{
    return Attribute::make(
        get: function () {
            $node = $this;
            while ($node->children->isNotEmpty()) {
                $next = $node->children
                    ->filter(fn ($c) => $c->status === NavigationStatusEnum::Normal)
                    ->first();          // 跳过隐藏项,找第一个可用子项
                if (! $next) {
                    break;
                }
                $node = $next;
            }

            return $node->url_info['url'] ?? null;
        }
    );
}
```

- 整树已 `toTree()` 加载,递归无 N+1。
- 全部子项不可用/无链接时返回 null,父项回退为展开行为(`javascript:;`)。

### 2.6 箭头方向跟随子菜单显隐(用户明确要求)

- 手风琴:`.open` 时 chevron 转 180°。
- 级联子菜单项:未展开向右(-90°)→ 展开后向下(0°)。
- 主行父项:未展开向下 → 展开后向上(180°)。

### 2.7 导航风格 `style = primary | minimal`(用户定義,颜色已确认)

| 元素 | primary(默认) | minimal 亮色 | minimal 暗黑 |
|---|---|---|---|
| 导航条背景 | `primary-500` | `white` + `gray-200` 底边框 | `gray-900` + `gray-700` 边框 |
| 文字 | `white` | `primary-600` | `primary-400` |
| hover | `primary-400`(更浅/提亮) | `gray-100` | `gray-800` |
| 选中(active) | `primary-600`(更深) | `primary-100` 底 + `primary-700` 字 | `primary-500` 16% 透明底 + `primary-300` 字 |
| 子菜单面板 | 主题色面板,白字 | `white` + `gray-200` 边框,深灰字 | `gray-900` + `gray-700` 边框,浅灰字 |
| 子菜单 hover | `primary-400` | `gray-100` 底 + `primary-700` 字 | `gray-800` 底 + `primary-300` 字 |
| 手风琴行 hover/选中 | `primary-400`(通栏) | `gray-100`(通栏) | `gray-800`(通栏) |

- primary 为三色体系(用户最终确认):底 `primary-500`、hover 提亮 `primary-400`、**选中更深 `primary-600`**——与 minimal 的三色体系(白底 / 灰 hover / 浅主题色选中)一一对应。
- 参考演示 4(风格对比 + 暗黑)、演示 5(手风琴两风格)。

### 2.8 移动端(< lg 汉堡菜单)

- **交互保持现状**:手风琴 + 点击展开、激活链默认展开(`navigation-record.blade.php` 的递归逻辑保留),不参与 `submenu_style`/`submenu_trigger` 配置。
- **配色必须按 `style` 同步换肤**(用户明确要求优化,现状配色不好看):
  - primary:主题色面板,行 hover/选中为更浅主题色(primary-400),分隔线调整;
  - minimal:白底(暗黑深底)+ 主题色文字 + 浅灰 hover;底部登录/注册按钮区同步换肤(primary:白底橙字登录 + 白边透明注册;minimal:主题色实底登录 + 灰边注册,暗黑对应翻转)。
  - 参考演示 5 的两个手机面板。

### 2.9 默认值汇总

主行 `cascade + hover`;「更多」下拉 `accordion + click`;`style = primary`;`parent_clickable = true`;`more_icon_only = true`。

## 3. 演示页对照表(验收基准,`public/demo/navigation-overflow-demo.html`)

| 演示 | 验证点 |
|---|---|
| 演示 1 | hover 无限级级联(4 级)、右缘反向弹(JS 实时检测)、宽度滑块、父项点击直达第一个叶子、整树预览按钮 |
| 演示 2 | 溢出折叠测量、「更多」纯图标按钮占最后可放下的位置、纯展开手风琴、折叠项含激活态时更多按钮高亮 |
| 演示 3 | (历史参考,混合形态,已被决策放弃,不实现) |
| 演示 4 | primary / minimal 两种风格 + 暗黑切换 + 选中态(「联系我们」示例) |
| 演示 5 | 手风琴层级区分(缩进块 + 字重)、移动端两种风格配色 + 登录注册区、248px「更多」面板宽度 |

## 4. 实现步骤(建议顺序)

### 阶段 1:模型层

- `addons/cms/src/Models/Navigation.php` 加 `firstLeafUrl()` accessor(代码见 2.5)。
- 测试:`tests/Feature/Cms/` 下新建(参考 `FooterTest.php` 写法,工厂用 `addons/cms/database/factories/ModelFactory.php` 中现有 Navigation 工厂):
  - 父→子→孙三层,断言 `first_leaf_url` 返回第一个叶子 url;
  - 第一个子级 `status=Hidden` 时跳过取第二个;
  - 整棵子树都隐藏时返回 null。
- 运行:`php artisan test --compact --filter=FirstLeafUrl`

### 阶段 2:配置

- `addons/cms/config/sn-cms.php` 顶层加 `navigation` 节(见 2.3,带中文注释)。
- 如需便捷读取,在 `addons/cms/src/Support/Utils.php` 参考现有 `getConfig` 用法添加。

### 阶段 3:视图重构(tradition 主题)

`navigation.blade.php` 拆分重构:

1. **PC 主行**:全量渲染所有一级项 + Alpine 测量折叠组件(`x-data` 注册测量逻辑,逻辑对照演示页 `apply()`);按 `desktop_submenu_style` 渲染级联或手风琴。
2. **递归 partial**(新建 `addons/cms/resources/views/livewire/tradition/components/navigation/partials/` 下):
   - `cascade-item.blade.php`:`@include` 自身递归 `children`,一级向下/深层向右 + JS 边缘检测;
   - `accordion-item.blade.php`:递归手风琴,缩进用 `$record->depth`(`getNestedset()` 需加 `->withDepth()`,见 2.2)计算,背景通栏,父级字重 600 / 叶子 400。
   - 注意:用 `@include` 传参递归,不用 `x-dynamic-component`(它依赖 Livewire 组件上下文,「更多」下拉内的片段没有 `$this`)。
3. **「更多」按钮 + 下拉面板**:纯图标按钮(aria-label)、按 `more_submenu_style` 渲染内部结构。
4. **父项链接**:`parent_clickable` 且 hover 级联且 `first_leaf_url` 非空 → 父项渲染真实 `<a href>`;否则 `javascript:;` 纯展开。
5. **箭头方向**按 2.6 实现并跟随显隐状态。
6. **Alpine 逻辑**:测量折叠 + 级联 hover 管理(对照演示页 JS 翻译,含 300ms 延迟、祖先链、边缘检测),以视图内 `<script>` 或包内 js 资产注册(与现有内联风格一致)。
7. **移动端面板**:交互逻辑保留现状,配色按 2.8 重构。
8. **多语言**:`addons/cms/resources/lang/zh_CN/cms.php` 与 `en/cms.php` 的 `frontend` 节加 `more`(「更多」aria-label)等新文案。

### 阶段 4:风格 CSS

- primary / minimal 两套(色值表见 2.7),minimal 暗黑用 Tailwind `dark:` 前缀。
- 遵循项目 sn-* CSS 体系与 AGENTS.md 规范(几何用令牌、颜色类体系);主题色用项目 primary 调色板,不要硬编码演示页的橙色十六进制值。

### 阶段 5:移动端配色(见 2.8)

### 阶段 6:测试与验收

- 补 Feature 测试(配置读取默认值、first_leaf_url、渲染含「更多」按钮/aria-label 等)。
- `vendor/bin/pint --dirty --format agent`(改了 PHP 必跑)。
- `php artisan test --compact`(跑相关文件,最小范围)。
- 浏览器对照演示页逐项验收:`http://filamentv5.test/cms`(后台造 12+ 个多级导航;验证折叠、四级级联、边缘反向、两种风格切换、暗黑、移动端 DevTools 模式)。

### 阶段 7:收尾

- 演示页 `public/demo/navigation-overflow-demo.html` **保留不删除**——`public/demo/` 目录按项目规则专门存放演示文件,所有演示文件永久保留作历史查看。
- 本计划文档归档于 `.zcode/plans/plan-navigation-overflow.md`,任务完成后更新状态标记即可。

## 5. 注意事项

- 只改 `addons/cms` 包与主应用测试;`addons/support` 的 sn-* 类可直接复用,不修改。
- 触屏 lg 平板:hover 级联首次 tap 触发 hover 展开、第二次 tap 才跳转,可接受,无需特殊处理。
- 键盘可访问性:沿用现有 `x-trap`、`x-on:keydown.down.prevent` 等模式。
- `generate_href_html()` 用法保持与现有一致(`url_info['url']` + `url_info['target']`)。
- 演示页中的原生 JS 只是交互原型,正式实现以 Alpine 重写;演示页数据是硬编码的,不要照搬。
- 提交:演示页 + 本计划文档一起提交(用户要求);实现代码另行提交。

---

# 迭代记录(初版完成后的 4 轮反馈,全部已实现并验证)

> 初版执行时⚠️已过时的决策:§2.4 中「手风琴固定 click」已被修订(见迭代三-1);§4 阶段 7 的「演示页删除」改为按项目规则永久保留。以下为当前**最终生效**的架构与决策。

## 迭代一:代码风格与复用

1. **Utils 精简**:仅保留 `navigationConfig()`(基础读取)与 `isDesktopParentClickable()`(级联+hover+配置三重判断);其余 6 个透传方法(getNavigationStyle 等)删除,视图直接 `Utils::navigationConfig('style', 'primary')` 读取。
2. **组件纯化(去容器化,最终形态)**:导航组件 PC 主行固定 `hidden lg:flex h-16 w-full`,**不带容器、不带边框、不带背景**;容器/背景/边框全部由调用方外层包裹(`components/container/page.blade.php` 中:primary → `sn-primary-bg`,minimal → `bg-white dark:bg-gray-900 + border-b`)。⚠️ **primary 皮肤文字为白色,调用方必须提供深色背景,否则不可见**(config 注释已提醒)。
3. **CSS 分工原则**:sn-cms-* 类只保留「皮肤双色 / 状态 / 级联定位」;简单布局一律视图内联 Tailwind。空容器钩子类(`sn-cms-nav-list`、`sn-cms-acc`、`sn-cms-nav-bar`)保留供插件覆盖。
4. **溢出测量 double-count bug(真 bug)**:列表是 flex-1,`clientWidth` 已排除更多按钮占位,原判断 `used + moreW > avail` 重复扣减导致提前折叠一项(空隙最多多出一个按钮宽)。修正为 `used > avail`(+1px 舍入保护;胶囊模式下逐项累加 `columnGap`)。一级项 `min-w-32 → min-w-24`。
5. **图标**:全部改用 `Filament\Support\Icons\Heroicon` 枚举(如 `:icon="Heroicon::ChevronDown"`),不写 icon 字符串。
6. **链接规则**(已 record-rule 到 `.ai/rules/views.md`):前台跳转用 `<x-filament::button tag="a">`(自带 SPA 判断),裸 `<a>` 必须 `generate_href_html()` 输出 href;纯展开占位固定 `javascript:;`。

## 迭代二:交互补全与 Brothers

1. **手风琴选中色**:primary `bg-primary-600`(更深)、minimal `bg-primary-100 + primary-700 字`(暗黑 `primary-500/15 + primary-300`);hover 保持提亮。所有手风琴语境(移动端/更多面板/PC 手风琴下拉/Brothers)一处生效。
2. **点击事件补全 + 更名**:`sn-filament-nestedset-*` → **`sn-cms-navigation-node-click` / `sn-cms-navigation-leaf-click`**(载荷 `{ recordId, hasChild }`),主视图一级链接、cascade-item、accordion-item 全部派发。更名同时修复了旧名误触发 Posts 分类过滤的串扰;category 体系事件不动。
3. **Brothers 修复**:原查询子级无 depth(且 `descendants` 关联上 `withDepth()` 算出 -1,以子树为基准不可用)→ 改 PHP 侧 `markDescendantDepth()` 树递归标注绝对 depth + 视图 `depthOffset=1` 转相对层级(侧栏顶层无缩进)。
4. **主题路径动态化**:partial include 用 `$this->getThemeView('components.navigation.partials.*')` 解析(主视图/Brothers 视图算好路径变量传入)。⚠️ `@include` 子视图无 `$this` 绑定,递归用继承变量 + 默认值兜底。多主题时随主视图整体替换。
5. **移动端合并**:删除 `navigation-record.blade.php`,移动端与 Brothers 复用 `accordion-item` partial(`$variant: 'menu'` = li 根 + py-3.5 触控尺寸;默认 panel 尺寸)。

## 迭代三:触发语义与圆角体系

1. **trigger 语义修订**(推翻初版 §2.4):`desktop_submenu_trigger` 对 cascade 和 accordion 的**一级**都生效(默认 hover);accordion 面板内部层级固定 click。hover 区父项点击放行真实链接;占位链接(`javascript:;`)点击也切换(触屏/键盘兜底)。
2. **圆角方案**(用户澄清后的最终版):**内部一律通栏 hover/选中**,圆角只做在弹出容器外层。新增配置 `desktop_item_style: flush(默认,通栏)| rounded(胶囊,my-2 + rounded-md + 列表 gap-1.5)`。
3. **sn-rounded 分角变体**(support 包):补齐 `sn-rounded-t/b/l/r/tl/tr/bl/br`,与 `sn-rounded` 同走 `--sn-radius-card` 令牌(响应式 md/lg)。导航用法:Brothers 卡片 `sn-rounded`;移动端面板 `sn-rounded-b`;下拉/更多面板因含 flyout(裁切风险)保持控件级 `rounded-md`。
4. **Brothers 卡片化**:`sn-rounded + overflow-hidden + shadow-(--sn-shadow-card) + py-2`(minimal 加 ring),内部通栏、无分割线(移动端菜单保留分割线 `sn-cms-acc-divide`,两级语境不同)。
5. **更多面板滚动条**:挂 `sn-scrollbar`(6px 细),primary 皮肤滑块白色半透明、minimal 恢复灰系。

## 迭代四:FOUC 闪块攻坚(三轮定位)+ 皮肤终态

闪块问题经历三次误诊-修正,最终结论(⚠️ 接手者必读,避免重蹈):

1. **[x-cloak] 规则位置**:canonical 在 `addons/support/resources/css/index.css`(app.css head 阻塞加载,首帧前生效,覆盖所有布局);cms 布局内联的重复规则与 `.sn-cms-nav-bar` 内联关键 CSS(基于「app.css 到达前渲染」的误诊,head 阻塞 CSS 下该窗口不存在)均已删除。
2. **移动端闪块真凶**:汉堡按钮的定位原写在 `.is-closed` 状态类(Alpine `:class` 挂载)→ 初始化前按钮在**文档流内**撑起 nav ~44px → 调用方 wrapper 背景透成一条 ~50px 色带,Alpine 启动后塌缩消失。**修复 = 定位类写死 blade**(`absolute top-3 right-3 z-20`),CSS 状态类只管颜色。结构性移除,任何时序不再出现。
3. **PC 全导航闪现 + 横向滚动条**:主行 `<li>` 折叠隐藏由 Alpine 测量后执行,初始化前全量渲染溢出。**修复 = 主行 `overflow-x-clip`**(组件内消化,clip 不影响纵向 flyout;右缘反向检测保证 flyout 不超视口,横向实际裁不到)。body 级裁切已撤销。
4. **minimal 黑字**:一级导航/更多按钮/手风琴父行/叶子默认 `text-gray-900 dark:text-gray-100`,hover/选中转主题色。
5. **defaultOpen 递归 bug**:递归 include 曾强制 `'defaultOpen' => false` 导致深层激活链只展开一层;已改为不传,每层各自按 has_active 展开(测试覆盖:三层激活链 ≥2 处 isExpanded:true)。

## 迭代五:溢出折叠细节 + 布局自适应

1. **「更多」cascade 滚动容器裁切(真 bug)**:更多面板的 `overflow-y-auto`(为 accordion 长列表设计)使 overflow-x 计算值强制变 auto → cascade flyout(弹出在面板外)被整体裁切(实测仅 5px 可见),右弹深层级撑出横向滚动条。修复:cascade 模式下条件移除限高滚动(视图 `@class` 分支,`max-h-[60vh] overflow-y-auto sn-scrollbar` 仅 accordion 保留),flyout 自由弹出,长列表交页面滚动;面板基类 `.sn-cms-nav-more-menu` 相应去掉滚动属性。
2. **「更多」按钮文字形态内边距**:纯图标保持 `px-1`,带文字改 `px-4`(与一级导航对齐)。
3. **统一子菜单弹出动画**:`.sn-cms-sub` 从 `display:none/block` 切换改为 `opacity-0 invisible scale-[0.98] pointer-events-none` + `transition-[opacity,visibility,transform] duration-150 ease-out motion-reduce:transition-none`,与「更多」面板 x-transition(150ms opacity+scale)节奏一致;覆盖主行级联、更多面板级联、桌面手风琴外层面板。
4. **侧栏自适应分栏(初版 md)**:`navigation-container` 的 `md:w-72` 写死改为 `md:grid-cols-4 / xl:grid-cols-5` 比例分栏(迭代六统一改 lg)。
5. 新增 4 项测试(更多面板 cascade/accordion 类分支、按钮图标/文字形态)。

## 迭代六:四级截断攻坚 + 暗黑体系 + 断点统一

1. **flyout 方向检测基准错误(真 bug,主行与更多共用)**:原判断 `rect.right > window.innerWidth - 8` 双重错误——① innerWidth 含滚动条(实测差 15px);② 真正的裁切线是**导航条自身右边界**(`overflow-x-clip` 所在,条在居中容器内,1447px 视口下比视口窄 115px)。四级面板实测在条右缘 1332 处被静默裁掉 99px 而 JS 判定放得下。修复:新增 `flipBounds()`(右侧取条右缘与 `documentElement.clientWidth` 的较小值)+ `positionCascade()`(默认右弹,右缘放不下翻转左弹,向左也穿出条左缘翻回右弹,margin 8px)+ `repositionOpenCascades()`(bar ResizeObserver 里除 applyOverflow 外对展开中菜单重新定向)。验证:四级链 pop-left 913-1089 完整落在条边界内;1447→1024 resize 后三层面板全部自动重定向。
2. **侧栏断点统一 md→lg(7 处)**:lg(1024)起并排(lg 1:3 / xl 1:4),以下上下堆叠侧栏在上;条件侧栏时内容列 `@class` 兜底占满整行防空轨道。文件:navigation-container、profile、profile/views、settings/profile|password|two-factor、post/posts(分类树)。**该布局已沉淀为全站规则**:`.ai/rules/views.md` + support 包 `resources/boost/guidelines/core.blade.php`(侧栏+内容比例分栏节,含右侧栏写法=内容 div 在前)。
3. **primary 暗黑适配(用户定义)**:所有 primary 表面 = 亮 `primary-500` / 暗 `primary-600`(与调用方 `sn-primary-bg` 一致),覆盖 `.sn-cms-sub`、`.sn-cms-nav-more-menu`、`.sn-cms-brothers`、`.sn-cms-nav-mobile`、`.sn-cms-nav-search-strip`;选中色 = 亮 600 / 暗 700(始终比所在表面深一档),覆盖 nav-link/more-btn/acc-row/acc-leaf 选中态。hover 保持 400(提亮,两种底均可辨)。修复了暗黑下选中 600 == 条背景 600 完全同色的问题。
4. **cascade 子菜单项选中态**:cascade-item partial 原先完全不输出 `is-active`(accordion 有、cascade 没有)→ li 按 `has_active` 输出;CSS 双皮肤:primary `bg-primary-600 dark:bg-primary-700`,minimal `bg-primary-100 text-primary-700 dark:…`。「更多」cascade 内折叠的激活项现在与更多按钮同步高亮。
5. **测试种子** `seed-nav-test.php`(主应用根目录,幂等):20 顶级项(12+8),含三个多级父项(产品中心 4 级链带激活点 /cms 与 /cms/posts、解决方案 4 级、服务支持 3 级)+ 超长名称项;1280px 下折叠 9 项。
6. **测试适配**:`more_submenu_style` / `more_submenu_trigger` / `more_icon_only` 与 `style` 同列为高频预览键——默认值文件断言跳过,渲染测试显式 `config()` 锁定,不受用户本地预览值影响。

## 当前文件地图

| 文件 | 职责 |
|---|---|
| `addons/cms/src/Models/Navigation.php` | `first_leaf_url` accessor(直达第一个可用叶子) |
| `addons/cms/src/Support/Utils.php` | `navigationConfig` + `isDesktopParentClickable` |
| `addons/cms/src/Livewire/Components/Navigation/Navigation.php` | 主导航组件(getNestedset 带 withDepth) |
| `addons/cms/src/Livewire/Components/Navigation/Brothers.php` | 同级导航(markDescendantDepth 递归标注) |
| `addons/cms/config/sn-cms.php` | `navigation` 节 8 个配置项(含 desktop_item_style) |
| `.../livewire/tradition/components/navigation/navigation.blade.php` | 主视图 + snCmsNav Alpine 组件(溢出测量/级联管理) |
| `.../navigation/partials/cascade-item.blade.php` | 递归级联行(is-active 选中态) |
| `.../navigation/partials/accordion-item.blade.php` | 递归手风琴行(variant: panel/menu + depthOffset) |
| `.../navigation/brothers.blade.php` | 同级导航卡片 |
| `.../navigation/navigation-container.blade.php` | 侧栏 + 内容比例分栏(lg 1:3 / xl 1:4) |
| `.../tradition/profile.blade.php`、`profile/views.blade.php`、`settings/{profile,password,two-factor}.blade.php`、`.../components/post/posts.blade.php` | 同款比例分栏布局(7 处统一) |
| `addons/cms/resources/css/index.css` | sn-cms-* 皮肤/状态/定位类(布局已回视图;暗黑 500/600 + 选中 600/700) |
| `addons/support/resources/css/index.css` | `[x-cloak]` 兜底 + sn-rounded 分角变体 |
| `addons/support/resources/boost/guidelines/core.blade.php` | 全站「侧栏+内容比例分栏」布局规则(供所有扩展包遵循) |
| `.ai/rules/views.md` | 同上规则的项目级 views 规则落地 |
| `seed-nav-test.php`(主应用根目录) | 「更多」按钮测试数据种子(幂等) |
| `tests/Feature/Cms/NavigationTest.php` | 18 项导航测试(配置/accessor/渲染/激活链/面板分支/按钮形态) |

## 接手注意事项

- `config/sn-cms.php` 的 `style` 及 `more_submenu_style` / `more_submenu_trigger` / `more_icon_only` 是高频预览开关,用户会来回切换;默认值测试已跳过这些键,其余键照常文件级断言。**当前用户预览值可能为 more 区 cascade + hover + 文字按钮**(包 config)。
- 测试种子 `seed-nav-test.php` 在主应用根目录(幂等可重复执行);20 个顶级项里「产品中心」链含指向 /cms 与 /cms/posts 的激活点,访问这两个页面时「更多」按钮与其内折叠项会常亮——属设计行为,勿误判为 bug。开发库另有「哦买嘎」url 被后台改为 /cms/posts,与「产品介绍」同 URL 会双激活。清理后台可按名称识别这批新顶级项。
- 用户并行开发中(勿动):`footer-rss`、`LinkStatus`、`create_sn_links_table` stub、product 的 `base.card` 组件(引用存在、定义未找到)。
- 测试环境 PHP:`cmd //c "C:\Users\XPKJ-003\.config\herd\bin\php.bat"`(bash 无 php);smallnews 机器为 `C:\Users\smallnews\.config\herd\bin\php.bat`。验证浏览器为 IAB 面板,**遮挡时 rAF/CSS 过渡会冻结**(getComputedStyle 停在过渡起始值、截图失败),JS 交互测试需先注入 requestAnimationFrame shim,视觉验证需禁用过渡(`*{transition:none!important}`)后截图;`document.visibilityState` 为 hidden 即处于此状态。
- 侧栏 + 内容比例分栏为全站统一布局模式,规则见 `.ai/rules/views.md` 与 support 包 `resources/boost/guidelines/core.blade.php`,新页面遵循同一模板。

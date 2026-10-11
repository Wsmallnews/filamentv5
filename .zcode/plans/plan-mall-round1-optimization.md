# 商城模块第一轮整体优化计划

> 来源：`tasks/商城模块/商城整体优化文档第一轮.md`（2026-10-08 整理入库）。
> 本文档是本轮优化的**唯一工作基准**：任务按优先级排序、逐条处理，完成后更新状态标记；后续会话直接接手。
> 关联：`plan-mall-module-roadmap.md`（商城总体路线）、`plan-media-library-usage-and-order-snapshot.md`（媒体库旧方案，T11 引用）、`.ai/rules/livewire.md`（组件契约规则）。

---

## 一、任务总览（优先级排序）

| 编号 | 优先级 | 任务 | 类型 | 状态 |
|---|---|---|---|---|
| T1 | P0 | step-number 组件修复重构（加号字符串拼接 bug） | 实施 | ✅ 已完成 |
| T2 | P0 | order Confirm 恢复 remark（coupon_id 复议后删除，纯引擎不预埋营销概念） | 实施 | ✅ 已完成 |
| T3 | P1 | 产品列表组件优化（contained 容器 + sn-gap + sn-motion-scale + 占位底色）+ 规则以 products 为规范参考 | 实施 + 规则 | ✅ 已完成 |
| T4 | P1 | 产品详情轮播图圆角 | 实施 | ✅ 已完成 |
| T5 | P1 | 扩展包组件通信规则整理 + 事件命名规划（含现有事件重命名） | 规则 + 实施 | ✅ 已完成 |
| T6 | P1 | order/src/Order.php 位置分析（已迁移 Features/Shortcuts/ShortcutRegistry） | 分析 | ✅ 已完成 |
| T7 | P1 | OrderPipes 逻辑解释（说明文档） | 说明 | 📋 见本文 §三 |
| T8 | P2 | 附加属性（小料/增值服务）完整可实施方案 | 实施 | ✅ 已完成（2026-10-09，extra 命名定稿） |
| T9 | P2 | 前端规格选择（PC 本地计算）+ uniapp 接口版综合方案 | 实施 | ✅ 已完成（含 F1 + 两个连带根因修复） |
| T10 | P2 | 规格图片体系优化（规格值图 vs 组合图 / 订单快照 / plain vs media） | 方案+demo 定稿；**前端形态已落地** | 🎨 前端完成（产品 7 演示），后台部分待做（组合图落库推导 / selector_style 配置 / assetPicker） |
| T11 | P2 | 媒体库（spatie/laravel-medialibrary）完整方案 | 方案 | ⏸️ **待用户确认后动工** |

排序依据：bug 和「现在就必须有」的功能先行（T1/T2）；小改动 + 规则固化次之（T3-T5）；四个大方案均为「先分析、确认后动工」（T8-T11），分析本身即本轮交付物。

---

## 二、已完成任务详情

### T1 step-number 组件修复重构 ✅

**文件**：`addons/support/resources/views/components/input/step-number.blade.php`

**根因（两个）**：
1. **`.toFixed()` 返回字符串**：`increment()` 里 `this.currentVal = Math.min(max, this.currentVal + step).toFixed(dp)` —— 第一次点击后 currentVal 变字符串 `"2"`，第二次点击 `"2" + 1` 触发字符串拼接得 `"21"`，随后 `Math.min` 又转回数字 21。减号无此问题（`"2" - 1` 会隐式转数字）。
2. **`currentVal: 0` 硬编码**：不读服务端渲染的 wire:model 初始值（如 productNum=1 时首帧显示 0，靠 entangle 回填存在时序风险）。

**重构**：`clamp()` 统一收敛（Number 强转 → min/max 钳制 → `Number(toFixed())` 保证存数值）；`init()` 从输入框 DOM 读初始值；increment/decrement/updateValue 全走 clamp；`toFixed` 只在 clamp 内出现一次且结果转回 number。

### T2 Confirm 恢复 remark / coupon_id ✅（2026-10-08 复议：coupon_id 已删除）

**文件**：`addons/order/src/Livewire/Components/Confirm.php` + `addons/order/resources/views/livewire/components/confirm.blade.php`

- 恢复 `public string $remark = ''`，boot() setParams 透传 `'remark'`（OrderCreate::saveOrder L235 本就读 `params['remark']`，此前恒 null）。
- 确认页右栏（金额汇总卡）加「订单备注」输入框（`wire:model`，随下一次请求同步，不触发金额重算）。
- **coupon_id 复议后删除**（用户拍板）：orders 表无 coupon 列、引擎不消费该参数、OrderPipes 注释明确把 coupon 列为**将来独立营销扩展包**示例——纯引擎包不应预埋营销概念，删除以解耦。将来 coupon 扩展包的正确接入：自己的管道（`OrderPipes::scene(...)->stage(calc)->after('product', CouponCalcPipe)`）从 params 读自己的参数，UI 侧由扩展包自己的选择器组件注入。
- **命名规范备注**（本轮沉淀）：Livewire 组件公开属性用驼峰（`$addressId`/`$relateItems`），作为管道/接口参数传递时才转下划线（`'address_id' => $this->addressId`）。将来若真需要 coupon 参数：`public ?int $couponId` + `'coupon_id' => $this->couponId`。

### T3 产品列表组件优化 + contained 规则固化 ✅

**文件**：`addons/product/resources/views/livewire/components/product/products.blade.php`、`.ai/rules/livewire.md`、`addons/support/resources/css/utilities.css`

1. **contained 容器**：有商品时网格外围加条件卡片皮（参考 comments 组件模式）：外层 `@class(['w-full grid ...', 'sn-container sn-padded' => $contained])`——网格卡片型的子项（商品卡）自带卡片外观，容器只穿皮 + 内边距，不干预网格本身。空态经 `<x-sn-support::empty :contained>` 已有同语义。规则已写入 `.ai/rules/livewire.md`（CanBeContained 容器模式新增「网格卡片型」形态，以 **products** 为规范参考示例）。
2. **间距**：`gap-2.5` → `sn-gap`（消费 `--sn-space-page` 令牌，<lg 1rem / ≥lg 1.5rem，整站统一刻度）。**卡片内部间距维持 `p-2.5 gap-1` 原生工具类**（2026-10-08 用户拍板：内部微间距不用 sn-padded——边距过大，sn-padded 只用于容器卡片皮）。
3. **图片缩放**：手写 `transition duration-700 ease-out group-hover:scale-105` → `sn-motion-scale`（scale-110 / duration-300，且自动获得 reduced-motion 无动画适配）。
4. **占位底色**：`bg-gray-100 dark:bg-gray-800`（图片加载期的占位底色）→ 新增 sn 原子类 `sn-media-bg`（定义在 utilities.css，`sn-image-placeholder` 的底色同步改用它，灰色调单一事实源）。

### T4 产品详情轮播图圆角 ✅

**文件**：`addons/product/resources/views/livewire/components/product/product.blade.php`

swiper 组件本身支持 `rounded` prop（true = rounded-xl，或传自定义 class）。产品详情调用处传 token 对齐的 `rounded="rounded-(--sn-radius-card)"`（消费 `--sn-radius-card` 设计令牌，与整站卡片圆角联动，不写死 rounded-xl）。缩略条由组件内 `$roundedClass` 同步应用。

### T5 扩展包组件通信规则 + 事件命名 ✅

**规则文件**：`.ai/rules/component-events.md`（新建，已登记 index.md）；规则正文见该文件，要点：

- **三层决策**：渲染期能定的 → props 传参；交互强耦合 → 复合组件（一个 Livewire 组件内部解决）；仅松耦合通知 → 事件。
- **PHP dispatch vs JS dispatch**：业务动作（下单/支付）归子组件服务端方法，**一次请求内完成动作 + dispatch 结果事件**；宿主对事件的响应是纯前端动作（跳转/滚动/刷新）时用 Alpine `$wire.on` 监听，零额外请求。禁止「JS 先发请求 → PHP 再 dispatch → 又一次请求」的三段式。
- **命名规范**：`sn-{域}-{动作}`；结果事实用过去式（created/finished/selected），意图用现在式（buy）。商城链路事件已按此重命名（见下表）。

**事件重命名落地**（原名称全部废弃）：

| 原名 | 新名 | 派发方 → 监听方 | 载荷 |
|---|---|---|---|
| `product-buy` | `sn-product-buy` | product 详情组件 buy() → shop 详情页（跳转确认页） | type, from, relate_items(json) |
| `order-create-finish` | `sn-order-created` | order Confirm create() → 宿主页（跳转收银台） | order_sn |
| `pay-start` | `sn-pay-method-selected` | pay 收银台 pay-methods → shop Cashier（发起支付） | channel, method |
| `pay-finish` | `sn-pay-finished` | shop Cashier → cashier 视图（跳转结果页，TODO） | pay_sn |
| `pay-prepay-result` | `sn-pay-prepay-result`（仅补前缀） | shop Cashier → 前端按 method 消费 SDK 结果 | pay_sn, channel, method, result |
| `sn-profile-address:selected` | 保持（已合规） | profile ChooseAddress → order Confirm | addressId |

**将来事件规划**（确认收货等订单操作，规则中已立约定）：`sn-order-received`（确认收货完成，order 包派发，宿主页刷新/跳转）、`sn-order-cancelled`、`sn-order-refunded` —— 全部走「子组件服务端方法完成流转后 dispatch，宿主纯前端响应」模式。

---

## 三、说明与分析（T6 / T7）

### T6 order/src/Order.php 位置分析 ✅（2026-10-08 已按用户拍板落地）

**用户拍板**：功能本质是 registry，参照 support 的 `Features/Composition/CompositionRegistry` 模式迁移并改名（OrderManager 名字留给将来别的用途）。

**已落地**：`src/Order.php` → **`src/Features/Shortcuts/ShortcutRegistry.php`**（`Wsmallnews\Order\Features\Shortcuts\ShortcutRegistry`），容器单例绑定同步迁移；**门面同步改名 `Facades\Order` → `Facades\ShortcutRegistry`**（2026-10-08 用户拍板：门面不得用与注册表无关的域短名绑定注册表，采用 support 最新范式——门面名与注册表类名一致，参照 `Facades\CompositionRegistry` → `Features\Composition\CompositionRegistry`），调用侧 shop ShopServiceProvider 同步更新。改动面：ShortcutRegistry 新建 + Order.php/Facades/Order.php 删除 + Facade/OrderServiceProvider/OrderCreate/OrderPipelineTest/ShopServiceProvider 引用更新，行为零变化，order 全量测试 25 用例通过。

**历史分析留档**：域管理器放包根原有 pay 包先例（PayManager/Pay 在包根），但三处 `Order` 命名重载（门面/管理器/模型）确有混淆风险；迁移后 `Models\Order`（模型）语义清晰，注册表与门面同名归位 Features 子系统目录。support 的老门面（Feed/Search/ScheduledTask → XxxRegistry）是域短名模式，新代码不效仿；新范式 = 门面名 = 注册表类名。

### T7 OrderPipes 解释（管道插拔注册表）

**一句话**：OrderPipes 是订单管道的**运行时改写注册表**——让扩展包（未来的优惠券/库存/会员价）不用改 order 包一行代码，就能在任意订单类型（scene）的任意阶段（stage）的任意管道（key）前后插入/替换/删除计价逻辑。

**模型**（三层寻址）：`scene(订单类型) + stage(阶段) + key(管道键)`。

1. **scene** = `orders.type` 的值（如 `product`）。每种订单类型有自己的「配方」（Shortcut，如 shop 的 ProductShortcut），配方声明该类型五个阶段各跑哪些管道——这是**基础清单**。
2. **stage** = fetch / check / calc / summary / creating / refund / invalid 七个常量。OrderCreate 每执行一个阶段，就问一次 OrderPipes。
3. **key** = 清单里管道的键名（如 CommonPipes 的 `start`、product 包的 `product`）——这就是**锚点**。

**注册 API**（链式，扩展包在 ServiceProvider 里调用）：

```php
use Wsmallnews\Order\Support\OrderPipes;
use Wsmallnews\Order\Support\OrderPipes\StageConstants as Stage;

// 优惠券扩展包想在自己场景里、calc 阶段、product 管道之后插一个折扣管道：
OrderPipes::scene('product')->stage(Stage::CALC)->after('product', CouponDiscountPipe::class);
// 库存扩展包想在 creating 阶段最前面加锁库管道：
OrderPipes::scene('product')->stage(Stage::CREATING)->prepend('stock-lock', LockStockPipe::class);
// 六种操作：after / before / replace / remove / prepend / append
```

**生效时机**：`OrderCreate` 每阶段执行前调 `OrderPipes::apply($type, $stage, $recipe清单)`——把配方的基础清单逐条套用所有注册的操作，得到**最终管道列表**再经 Pipeline 执行。锚点不存在时操作静默跳过（replace/after/before），remove 不存在的 key 也安全——扩展包可以面对「宿主没装某个管道」的世界。

**与配方（Shortcut）的关系**：配方决定「这个订单类型基础跑什么」（显式 array_merge CommonPipes，决策 #28——清单单一事实源留在配方类）；OrderPipes 决定「别的包临时想改点什么」（运行时插拔，决策 #19/#20/#27：库存/优惠/限购等独立扩展包都从这里进来）。当前生产代码零调用是预期状态——它是留给扩展包的接缝。

**执行序（OrderCreate::calc）**：fetch → check → calc → summary 四阶段顺序管道，`create()` 再跑 creating（事务内）+ saveOrder 落库 + 配方附表。

---

## 四、大方案（T8-T11，⏸️ 待用户确认后动工）

### T8 附加属性（小料/增值服务）✅（2026-10-09 实施完成，extra 命名定稿）

**命名**（用户拍板弃 addon）：`sn_product_extras`（库，两级）/ `sn_product_extra_items`（挂载，选项粒度）/ `Extra` / `ExtraItem` / `ExtraManager` / `ExtraType`(Single/Multiple) / `ExtraStatus`(Up/Down)；schema 节点与 relate_items 载荷键 `extras`。旧 attribute 体系（AttributeRepositoryResource/Models/ProductAttributeManager/AttributeStatus/迁移 stub）全部删除（git 可溯）。

**已交付**：

1. **数据层**：extras 两表迁移（stub + 应用侧双侧，开发库 `migrate` 平滑新增、商品数据保留）；挂载为**选项粒度**（product_id + extra_id(选项) + is_default + price_override 分）——比组粒度挂载少一层 JSON 嵌套，覆盖价/子集启用都是普通列。
2. **临时售罄 vs 永久下架**（用户问题 2 的答案）：选项级 `status`（Up/Down 永久，手动恢复）+ `sold_out_until`（date，**当天及之前置灰「已售罄」、次日惰性自动恢复，零人工零定时任务**；选未来日期=连休）。语义=餐饮 POS「估清每日清零」+ 美团收银「售卖时段自动恢复」的合体，主流首选的库存制留待 P2 库存扩展包（届时两维度互补）。
3. **ExtraManager**（`Support/`）：`resolveProductExtras`（唯一解析源：组 Up + 挂载选项 Up，售罄保留标记，有效价 = price_override ?? 库价）+ `buyCheckAndGet`（必选/售罄/单多选/起购限购/未挂载拒绝，异常走翻译键）+ `buyCalc`（整数分 + 「珍珠×2」文本）。
4. **管道**（legacy ProductAttribute 三件套迁移落位，决策 #19/#28）：`product/src/Pipes/{Check,Calc,Summary}/Extra.php` + Fetch/Product 预加载 `extraItems.extra.parent` + ProductShortcut 各阶段注册；Calc 走整数分 + FieldInfo 明细行（`order_fields.extra_amount`）；引擎 Summary 白名单不动（域中立，快照走 relate_options/amount_fields 通道）。
5. **前台**：`specSchema.extras`（组/选项/有效价 price_parts/sold_out/is_default/limit_*）+ product-buy.js 扩展（toggleExtraOption/extrasTotalMinor/extrasPayload/is_default 预选）+ 详情页 markup（售罄置灰禁用可见、数量型步进器、合计行）；buy 载荷 `product_attributes` 键改 `extras`。
6. **后台**：`Filament/Resources/Extras/` 五层（组表格 + 选项 Repeater 内联：价格/状态/售罄至/数量上下限）+ ProductForm「附加项」Section（挂载 Repeater：库选项 Select 按组分组 + 默认选中 + 覆盖价 + 行内直建选项）+ shop panel_register 注册。
7. **测试**：ExtraTest 7 用例（解析/下架剔除/校验矩阵/计价/schema/buy 载荷）+ OrderPipelineTest 附加项 e2e（珍珠×2 覆盖价 + 大杯 → 金额/快照断言）；全量 436 passed。

**真机验证（2026-10-09 全通）**：附加项渲染 2 组、珍珠 is_default 预选、芝士碎售罄 disabled+「已售罄」、保温套覆盖价 4.50（库 5.00）、本地合计 ¥9.50、确认页单价 94.50/89.50 快照含附加项名、落单 ¥89.50（extra_price=450 分 + buy_extras 结构完整落库）、提交→收银台跳转。

**T8 期间连带修复（F1 完整形态）**：SPA（wire:navigate）站内跳转经 morph 更新页面，**所有视图内联脚本（@once/@assets 注册的 Alpine）一律不执行**——shop 页面级 snShopOrderConfirm/snShopDetail/snShopCashier 同样中招（T9 验证通过是整页加载的运气）。已全部迁移：宿主跳转监听改 **PHP `#[On]` + `$this->redirect()`**（gotoConfirm/gotoCashier 范式）、pay-methods 选择改 `wire:click="choose()"` 服务端派发；规则定稿 component-events.md（跳转响应用 PHP #[On]，payload 键名与参数名精确匹配）。

**演示数据**：dev 库已建附加项库（加料组：珍珠/奶盖/芝士碎[今日售罄]；增值服务组：保温套[覆盖价 4.50]）并挂载到咖啡豆（id 5），可强刷详情页体验（资产 URL 版本恒定，需 Ctrl+F5）。


**2026-10-10 复审修订（用户反馈五项）**：

**2026-10-10 双层挂载重构（用户提议）**：ProductForm 附加项挂载改双层 Repeater（先选组→组内挑选项；选过的组兄弟行禁选、换组自动清空内层、组内行内直建自动归组）——数组状态经新增 Support/ExtraItemService 拍平落库（对标 ProductSpecService 范式：toFormState 按组聚合回填 / save 拍平 diff / strip 脱水剔除；软删选项的挂载行保留，库恢复即回归）。**表结构零改动**（扁平选项粒度挂载不变，组信息经选项 parent 推导）；CreateProduct/EditProduct 六钩子接线。真机验证：回填两组分层呈现、覆盖价 4.5 回显、保存后挂载/默认/覆盖价/顺序全保留、前台 schema 不变。
① 软删除：extras 表补 softDeletes（双侧迁移 + dev 库 ALTER 保商品数据），Extra 模型 SoftDeletes + 组行删除级联软删子项——删除语义 = 商品端自动消失（挂载行保留、恢复上架即回归）、订单快照不受影响；Extras 后台补齐软删四件套（TrashedFilter/Restore/ForceDelete/批量）。
② 命名空间/字段修复由用户完成并经回归确认：ExtraForm 统一 use FilamentForms 前缀风格；enumsToggleButtons(ExtraType::class, 'type') 显式字段名（工厂默认 status 会与状态字段冲突——工厂签名 (enum, field='status', label)）。
③ 组 description 前台显示：resolveProductExtras 输出 + 详情页组名后跟 sn-descript-text 帮助文字。
④ sold_out_until 升级 datetime（时分秒）：迁移双侧 + dev ALTER、模型 cast、DateTimePicker + seconds() + minDate(now) 禁选过去时间 + suffixAction 时钟图标「一键售罄至当天结束」(endOfDay)、helper 文案更新；判据改为 isFuture()（时间点语义）。
⑤ ProductForm 附加项 Section 后台不可见的根因：四种布局（tabs/plain/wizard/allFields）各有一处规格信息，初版只命中两处——已补齐全部四处（真机验证编辑页出现「附加项」Section 与添加按钮）。

### T9 前端规格选择（PC 本地计算）+ uniapp 接口综合方案 ✅（2026-10-08 实施完成，两确认点按推荐落地）

**已交付**：

1. **specSchema 版本化契约**（`Product::getSpecSchema`）：`schema_version=1` + `spec_type` + `specs`（组树，含子项 image——T10 复用）+ `variants`（仅可售：id/spec_ids/spec_text/price 整数分/price_parts 服务端预制展示件/original_price_parts/stock）+ `addons: []`（T8 预留节点）。序列化按「将来直接当 uniapp API 响应」标准写。
2. **本地选择/计价**：`addons/product/resources/js/components/product-buy.js`（isSelectable/matchedVariant/priceParts，与服务端原 getSpecSelectable/getChoosedVariant 同构算法）；规格点击零 Livewire 请求，价格本地切换（真机验证 45.00→85.00）；数量 step-number 改 `x-model` 纯 Alpine 状态。
3. **服务端只管信任边界**：`buy(int $variantId, int $productNum)` 在可售变体集合内校验后派发 sn-product-buy；chooseSpec/selectedSpecs/getSpecSelectable/getChoosedVariant 服务端路径全部删除（单一事实源 = JS schema）。
4. **F1 修复**：order Confirm 提交按钮改 `wire:click="submitOrder"`（create 模式重跑管道 + 落单 + 派发），删除嵌套 Alpine 脚本 snOrderConfirm；真机验证点击→落单→跳收银台全通。
5. **测试**：ProductComponentsTest 规格用例重写为 schema 契约断言 + buy 校验（无货/不存在/数量下限）；OrderPipelineTest 新增 submitOrder 落单用例。全量 428 通过。

**连带发现并修复的两个根因**（比 F1 更深）：

- **F1-a：AlpineComponent 构建产物必须 ESM**——product/support 两包 bin/build.js 缺 `format: 'esm'`，esbuild 默认产出 IIFE，`import()` 后命名空间为空 → Alpine.data 注册 false → **swiper/lightbox 前端从未真正初始化过**（轮播只显示静态首帧）。已补 format 并重建发布；js.md 规则待沉淀。
- **F1-b：boot() 捕获参数早于请求 updates 应用**——boot() 里 setParams 的 remark 是 hydration 早期旧值，同一提交请求内 defer model 更新（remark）不会进管道参数（真机表现：填备注提交后落库为空）。修复：提取 `syncOrderParams()`，boot 与 submitOrder 各刷一遍。判据：**同一请求内更新的属性在 action 里消费时必须重刷参数，不能信任 boot 构造的快照**。

**uniapp 接口版衔接现状**：schema 已按 API 响应标准输出，后续做 `GET /api/products/{id}` 时直接序列化该结构 + 客户端复刻 product-buy.js 算法（~120 行纯函数）即可。

**遗留演示数据**：dev 库本轮新增 6 笔未支付测试订单（咖啡豆 ¥45-¥255，member 3），可留作联调或清理。

**设计留档**：本地计算只负责展示态（可选矩阵/命中变体/价格显示），服务端 OrderCreate 管道永远按 DB 重算价格库存——前端只传 `product_variant_id + num + addons`，篡改无效（淘宝/京东同架构）；接口版规划 `GET /api/products/{id}` 直接序列化 specSchema + `POST /api/orders(Preview)`，参数结构与 PC 确认页一致。

### T10 规格图片体系方案

**用户提议验证**：主多规格/多单位时规格值可传图 → 规格组合不再单独传图。**结论：成立，且推荐所有 spec_type 统一「组合图 = 自动推导」**：

1. **规格值图（保留上传，交互对齐淘宝）**：规格值行保留 image 上传（现状 plainImageUpload）。前端规格选择器升级为两种模式（后台或前端开关，主流由规格值是否有图自动切换）：
   - **列表模式**（默认）：文字 badge，规格值有图时 badge 左侧带 24px 小图；
   - **大图模式**：色块/大图卡片（如淘宝颜色选择），适合服饰/外观类主规格。
2. **规格组合图（取消上传 UI，改为自动推导落库）**：ProductSpecForm 的 variantsRepeater 移除 image 上传列；`ProductSpecService::diffSyncVariants` 保存时自动填充 `variant.image = 主规格值图 ?? 第一个有图的规格值图 ?? null`。variants.image **列保留**——订单快照逻辑（Summary 管道 `relate_image = currentVariant->image ?: product 主图`）**一行不用改**，回答了「下单时图片怎么存 order_items」：快照链路不变，只是 image 的来源从人工上传变为保存时推导固化（推导在商品编辑时点完成，订单侧拿到的一直是具体 URL，符合快照不可变原则）。
3. **多规格（无主规格）场景**：任取第一个带图规格值的图（主流电商同此简化——组合图只用于订单/购物车缩略展示，精确度要求低）。
4. **plain vs media（规格值图维持 plain，理由）**：规格值图数量大（N 组 × M 值）、与规格值强生命周期绑定（删规格值即废）、无跨模型复用/缩略图/响应式需求 → media 表会为每个规格值造一条记录 + 集合管理成本，收益为零。media 轨道留给「成套内容资产」（商品主图/轮播）。若 T11 素材库落地，plain 上传的**来源**可接素材库选择器（存储轨道不变）。

**实施清单**（确认后）：ProductSpecForm 移除组合图列 + specValue 图列升级预览；ProductSpecService 推导填充；前端规格选择器图片化（依赖 T9 的本地选择基建，两任务联动实施）；Product 包测试补「组合图推导」断言。

### T11 媒体库完整方案

**先回答「之前给过的方案」**：即 `plan-media-library-usage-and-order-snapshot.md`（2026-09-22 分析，⏸️ 暂缓中），核心已确认结论——**双轨制**：模型成套内容资产（商品主图/轮播、文章图集、banner）走 `mediaImageUpload`（spatie media 表）；内联轻量图（navigation icon、规格值图）走 `plainImageUpload`（路径直存字段）。另含四项未完成任务：自定义 MediaObserver 未接入（删 media 连带删物理文件，威胁订单快照）、全系统零 conversion 缩略图、scopeable 只写不读、订单管道 mainUrl 坏点。

**本轮新问题的分析（crmeb/fastadmin 对比）**：

| | crmeb 模式 | fastadmin 模式 | spatie 模式 |
|---|---|---|---|
| 本质 | **独立素材库**：所有上传进 attachment 表，表单只从库里选 | 双按钮：直传 + 从素材库选 | **模型媒体附件**：media 记录强关联 model+collection |
| 跨模型复用 | 天然支持（存路径，多字段引用同一文件） | 天然支持 | **不支持**：post 选产品图 = 必须给 post 新建 media 记录（spatie 无「借用」API，addMedia* 会产生新文件），记录甚至物理文件重复 |
| conversion/响应式 | 无（或自建） | 无 | 内置（media 的核心价值） |
| 适用 | 图片即素材的中后台 | 通用后台 | 模型资产化管理的 Laravel 生态 |

**判断**：spatie 和国内「素材库」是**两个不同的产品**，不是用法差异——spatie 解决「一个模型的图集 + 派生尺寸 + 生命周期跟模型」；素材库解决「文件中心化 + 任意复用」。用 spatie 硬做素材库（记录复制）确实会出现用户担心的重复图片问题，方向不对。

**方案（混合，推荐）**：两轨存储保持 spatie（内容资产）+ 字段直存（内联），**新增独立素材库解决「复用与统一选择」**：

1. **新建 `sn_attachments` 素材库表**（path/origin_name/width/height/size/mime/scope_type/scope_id/team_id/usage_count），上传统一入库（宽高沿用旧方案 3.4 的 MediaHasBeenAdded 思路，plain 轨道在保存钩子读取）。
2. **Filament 侧「素材选择器」**：`FormComponents::assetPicker()` 封装——弹窗内可「上传新图」或「从素材库选择」（fastadmin 双按钮收敛为一个弹窗两个 tab）；**plain 轨道的字段**（icon、规格值图、以及任何单图字段）全面换用 assetPicker，选择 = 把 path 写进字段（多字段引用同一物理文件，零重复）。
3. **media 轨道（spatie）不做跨模型选择**：商品主图/轮播继续直传挂 collection（conversion 刚需优先）；将来若要求「商品图也从素材库选」，用 `addMediaFromDisk`（同磁盘引用，不复制物理文件，仅建 media 记录）——记录重复但文件不重复，可接受。
4. **落地顺序**（确认后）：先做旧方案 P0 两项（MediaObserver 接入 + 订单管道 mainUrl 坏点修复——这两个是正确性 bug），再做素材库（表 + picker + plain 轨道接入），最后 conversion 注册。

**待确认点**：① 是否接受「spatie 不做素材库、新建 sn_attachments」的混合架构；② 素材库后台管理页（列表/删除/占用统计）范围；③ 商品主图/轮播是否二期接入素材库选择。

---

## 五、决策记录（本轮新增）

32. **商城链路事件命名统一 `sn-{域}-{动作}`**（2026-10-08）：product-buy → sn-product-buy、order-create-finish → sn-order-created、pay-start → sn-pay-method-selected、pay-finish → sn-pay-finished、pay-prepay-result → sn-pay-prepay-result；通信三层决策规则固化 `.ai/rules/component-events.md`。
33. **产品列表为可复用组件 contained 规则的网格卡片型范本**（2026-10-08）：livewire.md CanBeContained 容器模式新增第三形态——网格卡片型（子项自带卡片外观，容器条件 `sn-container sn-padded`），以 products 组件为规范参考。
34. **图片占位底色收敛 `sn-media-bg`**（2026-10-08）：`bg-gray-100 dark:bg-gray-800` 的图片占位场景统一走 sn-media-bg 原子类（sn-image-placeholder 底色同步改用）。
35. **网格卡片内部间距用原生工具类，sn-padded 只用于容器皮**（2026-10-08 用户拍板）：商品卡内部 `p-2.5 gap-1` 维持原样——sn-padded 消费 `--sn-space-card`（1→1.5rem）刻度是「容器卡片皮 ↔ 内容」的边距语义，卡片内部微间距用它边距过大。
36. **order 纯引擎不预埋营销参数**（2026-10-08 用户拍板）：Confirm 组件 coupon_id 删除——引擎字段准入判据 = orders 表有列/引擎消费（remark ✔）；营销概念（coupon）由将来独立扩展包经 OrderPipes 管道自带参数接入。
37. **注册表类参照 support 归位 Features 子系统目录，门面名 = 注册表类名**（2026-10-08 用户拍板）：`Wsmallnews\Order\Order` → `Wsmallnews\Order\Features\Shortcuts\ShortcutRegistry`，门面 `Facades\Order` → `Facades\ShortcutRegistry`（对齐 support `Facades\CompositionRegistry` → `Features\Composition\CompositionRegistry` 最新范式；Feed/Search 等老域短名门面不效仿）；OrderManager 名字保留给将来别的用途。
38. **Livewire 组件公开属性驼峰，管道/接口参数下划线**（2026-10-08 沉淀）：`$addressId` / `$relateItems`；传参时 `'address_id' => $this->addressId`。
39. **组件交互 JS 一律走 Filament 资产管线，禁止视图内联 Alpine 脚本**（2026-10-08，F1 系定稿）：`AlpineComponent::make` + `x-load-src` + `wire:ignore`（swiper 范式）；嵌套组件内联 `@once` 脚本经 Livewire 水合不生效。服务端可承载的动作直接 `wire:click`（submitOrder 范式）。
40. **AlpineComponent 资产构建必须 `format: 'esm'`**（2026-10-08，F1-a）：esbuild 默认 IIFE → `import()` 命名空间为空 → Alpine.data 注册 false 静默失效（swiper/lightbox 曾长期中招）；product/support 两包 build.js 已补，其余包建 JS 资产时照此。
41. **Livewire boot() 参数快照陷阱**（2026-10-08，F1-b）：boot() 早于请求 updates 应用，同一请求内 defer model 更新的属性在 action 消费时必须重刷参数（Confirm::syncOrderParams 双刷范式）。
42. **规格选择本地化契约**（2026-10-08，T9）：specSchema（版本化）为 PC/uniapp 唯一选择逻辑数据源；服务端仅保留信任边界校验（buy 按可售变体集合匹配 variant_id）；数量/计价全部本地。
43. **附加项域术语 extra + 售罄双字段**（2026-10-09，T8）：弃 addon（插件歧义）用 extra（外卖行业标准词）；选项粒度挂载（覆盖价/子集为普通列，无 JSON 嵌套）；临时售罄 = sold_out_until 日期惰性恢复（估清语义），永久下架 = status；价格覆盖空值实时跟随库价、有值商品固定，订单照常快照。
44. **F1 完整形态：SPA morph 下内联脚本一律不执行，宿主跳转监听必须 PHP #[On] + redirect**（2026-10-09）：wire:navigate 站内跳转经 morph 更新页面，视图内 @once/@assets 内联脚本（页面级同样中招）不执行；宿主对事件的跳转响应统一 #[On] + redirect()（shop gotoConfirm/gotoCashier 范式），组件交互选服务端 wire:click（pay-methods choose 范式）；payload 键名与监听方法参数名精确匹配（order_sn → $order_sn，不自动驼峰）。规则定稿 component-events.md。
45. **Filament 类引入规范 + 软删级联修复**（2026-10-10 用户拍板/反馈）：Forms/Schemas 目录导入（use FilamentForms 后 FormsComponentsXxx 前缀引用，同目录多类同理如模型 casts 的 Enums）；其余顶部 use 直用；Get/Set 正确类为 FilamentSchemasComponentsUtilities{Get,Set}（规则沉淀 filament.md）。Extra::booted 删除级联修复：forceDeleting() 是注册事件回调的静态方法（实例判断是 protected 属性），级联用 deleting/forceDeleting/restored 三钩子分别对 children 软删/强删/恢复。
46. **T10 选择器形态定稿（demo public/demo/spec-selector.html 多轮评审）**（2026-10-10 用户确认）：三形态仅作用于规格——胶囊（= sn-badge 现状）/ 列表（淘宝紧凑流式：宽度随内容、短项同行多个）/ 大图（auto-fill `repeat(auto-fill, minmax(min(120px,100%),1fr))`，卡宽钳 120–160px，列数随容器：436px→3 列、600→4、750→5，不写死 grid-cols-3）；无图规格组恒胶囊；附加项不参与形态切换——无数量组 = 规格胶囊同款（无加号），有数量组 = 行式 + 步进器（容器 ≥400px 两列、更窄一列）。
47. **长名截断规则：超整行才截断，选中展开**（2026-10-10 用户纠偏）：选项按钮 `max-w-full`（上限=流式容器行宽，不是固定 max-w）+ 名称 `truncate`；选中态切 `whitespace-normal` 换行全显；按钮必须显式 `text-left`（UA 默认 `button{text-align:center}` 会让换行第二行居中）；悬停 title 全名。
48. **step-number 双形态 variant + sn-stepper-min 事件**（2026-10-10 用户确认实施）：support `input/step-number` 加 `variant` prop——control（表单皮，输入框 `text-center`，商品数量用）/ bare（裸 `− n +`，buttonClass/valueClass 由调用处传样式，附加项用，`editable=false` 纯展示）；逻辑迁 `sn-support-components-number-stepper` 资产（对齐 F1/40 号决策，废弃 @assets 内联 numberManager）；减到下限派发 `sn-stepper-min`（父级接「减到下限 = 取消选择」，附加项已接入），加到上限 :disabled 钳制；组件只管数字钳制不含业务语义。

## 六、验证基准与结果

### 自动化（2026-10-08 全绿）

- 全量套件 427 passed / 66 skipped（既有跳过项）、pint 通过；order 专项 25 用例含 ShortcutRegistry 迁移后回归。

### 真机浏览器验证（2026-10-08，http://filamentv5.test，IAB）

| 验证项 | 结果 |
|---|---|
| 产品列表（/shop/products）：网格 sn-gap 24px、sn-container sn-padded 容器皮、无图卡片灰色占位+图标、5 列宽松布局 | ✅ 通过（DOM 计算值 + 截图确认） |
| 详情轮播圆角（商品 5）：swiper 主图/缩略条 `rounded-(--sn-radius-card)` 计算值 6px | ✅ |
| step-number 连点：初始值 1（非 0）、连点三次加号 2→3→4（拼接 bug 消失）、减号正常 | ✅ |
| 规格选择（浅焙+250g）：chooseSpec 服务端请求、选中态 solid badge、「当前规格」提示、价格刷新 | ✅ |
| 立即购买：sn-product-buy → 宿主跳转确认页（relate_items 完整携带） | ✅ |
| 确认页：清单带规格快照（浅焙；250g）、金额汇总、**订单备注输入框渲染** | ✅ |
| 落单：备注「第二次验证：豆子磨粉」完整落库 orders.remark、状态 unpaid、buyer member:3 | ✅ |
| sn-order-created → 宿主跳转收银台（order_sn 携带）、收银台金额渲染 | ✅ |

### 真机发现的新问题（F1 系，T9 实施时已全部修复 ✅）

**F1（已修）：嵌套 Livewire 组件的内联 Alpine 脚本不生效**——order 包 confirm 视图的 `@once` 脚本注册 `Alpine.data('snOrderConfirm')`，经 Livewire 水合后 x-data 未初始化 → 「提交订单」按钮失效。**修复**：按钮改 `wire:click="submitOrder"` 直调服务端（真机验证通过）；组件内交互动作一律不用内联 Alpine。
**F1-a（已修，比 F1 更深）：AlpineComponent 资产构建必须 ESM**——product/support 包 bin/build.js 缺 `format: 'esm'`，IIFE 产物经 `import()` 命名空间为空 → **swiper/lightbox 轮播从未真正初始化过**（只显示静态首帧）。已补配置重建；组件 JS 今后一律走 Filament 资产管线（AlpineComponent + x-load-src），不在视图内联。
**F1-b（已修）：boot() 参数快照陷阱**——boot() 的 setParams 捕获 hydration 早期值，同一提交请求内 defer model 更新的属性（remark）不进管道参数（真机表现：备注提交后落库为空）。修复：`syncOrderParams()` 在 boot 与 submitOrder 各刷一遍。**收银台 snPayMethods（pay 包嵌套 Alpine choose）仍属此类，阶段 D 收银台前端消费时按 F1 模式重构**。

**遗留演示数据**：dev 库累计 8 笔未支付测试订单（member 3，咖啡豆 ¥45-¥255），可留作收银台/订单管理联调样例，不需要可删。

### T10 前端落地记录（2026-10-10，产品 7 演示）

- **specSchema v1 增量**：`variants[].image`（变体覆盖图，null = 前端推导）；`stock` 非库存型输出 null（低库存提示仅库存型生效）。
- **购买盒形态**（product.blade.php + product-buy.js）：规格组自动形态（组内任一规格值有图 → 列表紧凑流式卡片带「该值最低变体价」；无图 → sn-badge 胶囊；长名超整行截断/选中展开/text-left/title）；附加项双形态（无数量 = 规格胶囊同款；有数量 = 行式卡片 `@container @[400px]:grid-cols-2` 两列，未选圆形 +、选中 bare 步进器，售罄不渲染控件）；吸底结算条（组合图推导链：变体覆盖图 > 首带图规格组选中值 > 商品主图；已选回显/附加项摘要/合计（变体价+附加×数量）/「仅剩 N 件」低库存）。
- **演示数据**：产品 7「手作黑糖鹿丸鲜奶茶」＝杯型 5 值带图（含中长名/33 字超长名）× 温度 4 × 糖度 5 ＝ 73 可售变体（纪念杯仅正常冰/保温杯超大杯不可热饮/热饮无全糖/超大杯+去冰 库存 2）+ 加料多选数量组（珍珠覆盖 3.50 默认选中/燕麦/奶油顶/马蹄爆爆珠售罄）+ 加一份主角单选组（紫薯波波默认/超长名厚切芝士奶盖/生椰冻售罄）；SVG 占位图 public/demo/img/；种子脚本 .t10-ref/tmp_seed_product7.php（幂等可重跑）。
- **验证**：Product+Shop 59 用例通过；真机全链路（联动置灰/库存提示/长名展开/步进取消/单选互斥/合计分账/结算条推导）通过；视觉模型审查截图通过。
- **已知**：产品包独立 `build:styles`（tailwind）本机 MODULE_NOT_FOUND（应用消费根 app.css 经 @source 扫描已覆盖，dist/product.css 注册本就注释）；资产 URL 常量版本占位 → 改 JS 后须 `filament:assets` + 浏览器强刷（构建顺序坑：先包构建后发布）。

### T10 用户反馈修复（2026-10-11）

1. **裸步进器下限语义**：step-number 加 `min-behavior` prop——`disable`（默认，置灰+禁用，纯步进器语义）/ `dispatch`（保持可点、点击派发 sn-stepper-min 供调用方取消，附加项用）——解决「置灰但可点」的语义冲突。
2. **售罄置灰从未生效的根因**：Blade 同一标签「静态 class + @class([...])」输出两个 class 属性，浏览器丢弃后者（附加项售罄灰、profile 地址选中 ring 均中招）；修法 = 并入 `:class` 对象（商品附加项）或合并进 `@class` 数组基线（choose-address）。规则沉淀 livewire.md。售罄徽章统一 `sn-badge sn-badge-xs sn-badge-solid-gray`。
3. **列表/大图切换落地**：带图规格组标题右侧图标切换（heroicon bars-3 / squares-2x2），大图形态 = `grid-cols-[repeat(auto-fill,minmax(min(120px,100%),1fr))]` auto-fill 自适应（购买盒容器实测 4 列 @137px）+ 图上角标选中；无图组恒胶囊不参与切换；specModes 状态在 product-buy.js。
4. **顺带修复**：step-number 的 x-load 从 `visible` 改立即加载——步进器常藏于 x-show（附加项选中才显示），visible 的 IntersectionObserver 不触发，选中后存在点击无响应死区窗口（资产化迁移引入的回归，内联脚本时代无此问题）。

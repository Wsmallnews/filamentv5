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
| T8 | P2 | 附加属性（小料/增值服务）完整可实施方案 | 方案 | ⏸️ **待用户确认后动工** |
| T9 | P2 | 前端规格选择（PC 本地计算）+ uniapp 接口版综合方案 | 方案 | ⏸️ **待用户确认后动工** |
| T10 | P2 | 规格图片体系优化（规格值图 vs 组合图 / 订单快照 / plain vs media） | 方案 | ⏸️ **待用户确认后动工** |
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

### T8 附加属性（小料/增值服务）完整方案

**需求还原**：蜜雪冰城式小料 / 京东式增值服务（碎屏险、只换不修、延保）——与规格（SKU 维度，决定「买哪个货」）正交，是「买货时顺带加购的服务项」；可单选/多选、可必选/非必选、可设价格、可临时下架（小料卖完了）、可按数量计价（小料 ×2）。

**现有基础盘点**：属性库 `sn_product_attribute_repositories`（组/选项两级 + options{is_require,is_multiple,is_num,limit_*_num} + price + status）+ 商品挂载 `sn_product_attributes`（product_id → 库引用，is_default 预选）+ `ProductAttributeManager`（buyCheckAndGet 校验 / buyCalc 计价 / formatAttribute 合并比较）。骨架方向正确，问题在：命名不达意（"attribute" 与规格语义混淆）、未接入新订单管道体系、单选/多选用 Toggle 而非显式类型字段、无临时下架的前端置灰语义。

**方案（命名重构 + 对齐主流）**：

1. **领域术语与表名**（对齐主流电商「增值服务/加料」= value-added service / add-on）：
   - 属性库 → **`sn_product_addons`**（两级：组「小料/增值服务」→ 选项「珍珠 / 二年碎屏险」）；文件 `Models/Addon.php`、`Filament/Resources/Addons/`（五层结构，AddonResource 替换 AttributeRepositoryResource，旧资源删除）。
   - 商品挂载 → **`sn_product_addon_items`**（product_id + addon_id + is_default + order_column；覆盖字段 price_override 可空——同库选项在不同商品可改价）。
   - `ProductAttributeManager` → **`src/Support/AddonManager.php`**（buyCheck/buyCalc/format 三方法语义保留，金额全面整数分）。
   - 迁移按 database.md 规则：直接改原始迁移 + stub 双侧 + migrate:fresh（开发期无存量包袱）。

2. **组字段设计**（sn_product_addons 主行）：
   - `type`：enum `Single`（单选，radio 交互）/ `Multiple`（多选，checkbox）——**显式类型字段**替代 options.is_multiple Toggle；
   - `is_require`：bool（必选/非必选；必选单选 = 默认选中不可取消或必须选一个，主流为前者）；
   - `is_num`：bool（按数量计价：小料 ×2；非数量型固定 ×1，如延保）；
   - `status`：enum `Up/Down`（**临时下架**：Down 时前端置灰展示「已售罄」、不可选但可见——恢复上架自动回来，不删挂载关系）；子选项同字段（组上架 + 选项上架才可选）。
   - 选项行：`price`（整数分，加价金额）、`limit_min_num/limit_max_num`（数量型起购/限购，收敛进 options JSON）。

3. **挂载方式（双通道，同 unit 决策 #16 模式）**：ProductForm 附加项 Repeater——每行**从属性库选择**（Select 关联 addon 组，选后带出其选项）+ **行内直建**（Select 的 `createOptionForm` 直接新建库组，一步入库，既满足「先在库里建好再选」也满足「加商品时顺手建」）。挂载粒度 = 组级（商品选用「小料」组，选项全集来自库；需要子集时在挂载行勾选启用的选项——存 `sn_product_addon_items` 的 options.enabled_children）。

4. **前端详情页交互**：规格选择器下方渲染附加项组（单选 = badge 单选组；多选 = badge 复选组；数量型 = badge + step-number）；合计价本地实时累加（依赖 T9 的本地计价基建）；已售罄选项置灰 + 「已售罄」角标。

5. **下单链路（接入管道体系）**：`relate_items[].product_attributes` 字段沿用（键名建议一并改 `addons`），Fetch 阶段 AddonManager::buyCheck 校验（必选/下架/数量），Calc 阶段 buyCalc 加价并入 `amount_fields`（明细行 `sn-order::order.fields.addon` 翻译键），Summary 阶段选项名快照进 `relate_attributes`（「珍珠×2」）——全部经 product 包的 Pipes/{Fetch,Calc,Summary}/Product 或独立 `Pipes/*/Addon` 管道承载，order 引擎零改动。

6. **uniapp 接口**（与 T9 联动）：商品详情 payload 附 `addons` 节点（组/选项/价格/状态/类型），结构与 PC 前端消费的 JSON 一致，一份结构两端用。

**待确认点**：① 表名/类名是否认可（addons）；② 价格覆盖（同库选项按商品改价）是否需要；③ 下架语义选「置灰可见」还是「隐藏」；④ 旧 AttributeRepositoryResource 删除时机。

### T9 前端规格选择（PC 本地计算）+ uniapp 接口综合方案

**现状**：每次点击规格按钮 → `wire:click="chooseSpec"` Livewire 请求 → render() 重算可选矩阵（getSpecSelectable）+ 已选变体（getChoosedVariant）+ 价格。延迟 = 一次网络往返，且此逻辑将来 uniapp 要再写一遍。

**结论：应该切换为前端本地计算**，理由：

1. **零信任问题**：本地计算只负责「展示态」（哪个可选、当前命中哪个变体、显示什么价），**服务端 OrderCreate 的 Check/Calc 管道永远按 DB 重算价格与库存**——前端不传价格，只传 `product_variant_id + num + addons`，篡改无效。这是淘宝/京东/有赞的一致架构。
2. **数据已齐**：可选矩阵的计算输入 = specs 树 + 变体列表（price/stock/status/spec_ids/image），商品详情页本来就要渲染这些——打包成一份 **spec_schema JSON** 随页面下发即可，无额外请求。
3. **uniapp 复用**：把 spec_schema 定义为**版本化契约**（`schema_version` 字段），PC（Alpine）与 uniapp（JS/TS）实现同一份纯函数算法（输入 schema + 已选集合 → 可选矩阵 + 命中变体）。算法无框架依赖，可提为共享 npm 包或双端各写（量小，~100 行）。
4. **体验**：点击零延迟；附加项（T8）加价合计也在本地实时算，不用混合「规格靠请求、加料靠本地」的割裂交互。

**PC 实施形态**：

- Product 组件 render() 输出 `specSchema`（specs 树 + variants 全量 + addons）到 Alpine 组件 `snProductBuy`；
- chooseSpec 改为 Alpine 本地方法（纯 JS，不发请求）：维护 `selected`、算 `selectable`（与现 getSpecSelectable 同算法：排除本组、与可售变体 spec_ids 匹配）、算 `matchedVariant`、驱动价格/图片/库存展示；
- `productNum`、加料选择、合计价全部本地；**「立即购买」点击才发唯一一次 Livewire 请求**（buy() 校验 + dispatch sn-product-buy），或直接由宿主 JS 跳转确认页（确认页服务端全量重算）；
- Livewire 的 `chooseSpec`/`selectedSpecs` 服务端路径删除（避免双轨）。

**接口版（uniapp）规划**：`GET /api/products` 与 `GET /api/products/{id}` 响应直接复用 specSchema 序列化结构（+ addons 节点）；选规格/选加料/算价全在客户端；提交订单走 `POST /api/orders/preview`（试算）与 `POST /api/orders`（创建），参数结构与 PC 确认页一致（relate_items）。**本期不实现 API 路由**，但 T9 落地时 specSchema 的序列化必须按「将来直接当 API 响应」的标准写（资源类封装，不散拼数组）。

**待确认点**：① 是否连「数量/加价合计」也全部本地（推荐是）；② buy() 是否保留 Livewire 请求（推荐保留——服务端先校验再 dispatch，防止已下架商品跳到确认页才报错）。

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

### 真机发现的新问题（前次阶段 C 遗留，未浏览器验证过的代码）

**F1（P0，待修）：嵌套 Livewire 组件的内联 Alpine 脚本不生效**——order 包 confirm 视图的 `@once` 脚本注册 `Alpine.data('snOrderConfirm')`，但嵌套组件经 Livewire 水合后 x-data 元素未被 Alpine 初始化（`_x_dataKeeper` 为空）→ **「提交订单」按钮的 `@click="create"` 在真实浏览器中无效**（自动化测试走 Livewire 层不经过 Alpine，故未暴露）。页面级（shop 包页面视图）同模式脚本正常（snShopDetail / snShopOrderConfirm 的 `$wire.on` 均工作）；**收银台的 snPayMethods（pay 包嵌套组件，choose 选择支付方式）大概率同病**，阶段 D 收银台前端消费时一并处理。
**修复方向**（二选一，实施时定）：① 提交按钮改 `wire:click` 直调服务端方法（把 `set type=create` 与 `create()` 合并为一个 submit 动作），彻底不依赖嵌套组件内联 Alpine；② 内联脚本提升到宿主页面视图（shop 页面级脚本已证明可靠）。

**遗留演示数据**：dev 库新增两笔未支付测试订单（sn 202602094511025465003000、202602112368530000030000，member 3，¥45 咖啡豆），留作收银台/订单管理联调样例，不需要可删。

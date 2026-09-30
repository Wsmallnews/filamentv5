# 商城模块（shop/product/order/pay）总体路线图

> 状态：阶段 A（货币基建）、阶段 B（pay 重构）、阶段 C（order 管道重排）已完成；下一步阶段 D（shop 前台闭环收尾）。
> 本文档是商城轨的**唯一计划基准**：已完成内容、核心规范、剩余断点、后续阶段、决策记录。后续开发以本文档为准，完成后更新对应章节的状态标记。
> 关联文档：`tasks/商城模块/商城逻辑梳理文档.md`（需求原始输入）、`.ai/rules/addons-src.md`（货币/模块依赖规则已固化）、`.zcode/plans/plan-pay-roadmap.md`（pay 包专项路线）。

---

## 一、背景与目标

- **目标**：多租户跨境电商商城（`addons/shop` 为前台门面 + product/order/pay 等基础扩展包协作）
- **跨境策略**：核心包只做"不阻塞跨境"的基建（币种列、快照、格式化集中）；汇率/多语言数据/税务等全部归后续独立扩展
- **多租户**：单据表已具备 `team_id` + `currency` 列，扩展包层支持租户；当前环境单租户运行（`sn-support.tenant_model = null`）
- **架构基线**：Laravel 13 + Filament v5.8 + cknow/laravel-money v8（底层 moneyphp v4.9）+ yansongda/pay v3

### 总体路线

```
阶段 A 货币基建（✅ 2026-09 完成）
   ↓ 消费者：pay_records 表、支付金额、退款分摊
阶段 B pay 通用支付扩展包重构（✅ 2026-09 完成）
   ↓ 消费者：PayableInterface 契约、金额口径
阶段 C order 管道重排 + 场景注册表 + shop 接线（✅ 2026-09-25 完成）
   ↓
阶段 D 端到端闭环收尾（pay-finish 结果页/收银台前端/超时关单）（⬜ 下一步）
   ↓
P1 分类闭环 / P2 后台管理与库存扩展 / P3 购物车·跨境扩展·多租户开启
```

---

## 二、阶段 A：货币基建（✅ 已完成）

**提交**：support `7fc06df` / order `9e94d03` / pay `8d7a96c` / product `1985146` / 主仓含 MoneyTest。

### 交付内容

1. **迁移**（按 `.ai/rules/database.md` 无兼容包袱规则：直接改原始建表迁移 + 双侧同步 stub）：
   - 全部货币列 `unsignedInteger` → `unsignedBigInteger`（orders 8 列、order_items 10 列、pay_records 3 列、pay_refunds 2 列、products/variants/attributes 的 price；`score_amount` 积分字段保持不变）
   - `currency char(3)` 列：`sn_orders`/`sn_pay_records`/`sn_pay_refunds` 必填默认 CNY（创建时快照）；`sn_products` 可空（空 = 站点默认币种，跨境定价挂点）；order_items/variants 不存（随单据/SPU）
   - 三张单据表补 `team_id` 可空列 + 索引 + 模型 `team()` 关联
2. **MoneyManager**（`Wsmallnews\Support\Features\Money\MoneyManager`，`sn_money()`）：运算（add/subtract/multiply/sum，混币种抛异常）、**`allocate()` 余数分配**（100 元摊 3 单 → 3334/3333/3333 不丢分）、`format()/symbol()`（Laravel Number + intl）、`jsonFields/jsonSum/jsonFormat`（JSON 金额整数分口径）
3. **MoneyCast**：`MoneyCast::class.':currency'` 行内币种绑定（写入同步回填 currency 列，空回落默认）；Order/PayRecord/Refund/Product 已绑定
4. **默认币种解析链**：`config('app.currency')`（可选覆盖，Laravel 13 骨架无此键）→ `sn-support.currency`（包默认 CNY，消费方零配置）→ CNY 兜底；SupportServiceProvider 启动时同步 `Number::useCurrency` + `CknowMoney::setDefaultCurrency`
5. 旧 `Features/Currency` + `sn_currency()` 标记 `@deprecated`（order 管道阶段 C 迁移后删除）
6. 测试：`tests/Feature/Support/MoneyTest.php` 16 用例；规范已 record-rule 固化到 `.ai/rules/addons-src.md`

### 货币规范（六条，后续开发必须遵守）

1. 存储：整数最小货币单位（分），列类型 `unsignedBigInteger`；JSON 金额明细（amount_fields/discount_fields 等）同样存整数分（键 => 金额），**不存小数、不嵌币种对象**
2. 币种：单据级快照（orders/pay_records/pay_refunds 必有；products 可空；items/variants 随上级）；跨币种必须先换算成单一交易币种再落单
3. 输入口径：`int = 分`；`string/float = 十进制元（表单）`；Money 对象透传；禁止把浮点运算结果直接传入
4. 运算/分摊：一律走 `sn_money()`；优惠拆单用 `allocate()`，禁止手工除法四舍五入
5. 符号/千分位是纯展示层：统一 `sn_money()->format()`，禁止入库
6. **模型金额赋值必须用 `sn_money()->fromMinor(分, 币种)` 构造 Money 对象**——MoneyCast 写入侧把标量按"元"解析，直接赋 int 分会 ×100 错账（本会话已踩过并修复，强规约）

---

## 三、阶段 B：pay 通用支付扩展包重构（✅ 已完成）

**提交**：pay `617ff53`（重构）+ `c37a4ae`（合并远端 Fix styling）+ `6b58005`（用户侧余额支付适配：退款按快照钱包类型回款 + deduct meta 带 payable）；order/shop/member/主仓配套提交。

### 交付架构

**契约层**（`pay/src/Contracts/`）：
- `PayableInterface`：getScopeType/Id/Info、morphType/Id/Options、`getPayCurrency()`、isPaid、`getRemainPayFee(): int`、`getPaidFee(): int`、`checkAndPaid()`（金额全部整数分口径）
- `PayerInterface extends HasSnIdentifiable`：仅补 `payerMask()`；morph 用 Laravel 原生 getMorphClass()/getKey()
- `PayConfigInterface`：`supports(channel)` + `getChannelConfig(channel, tenant)`——配置源可整体替换（调用方注入 → 容器绑定 → 默认 ConfigPayConfig 读 sn-pay.php；将来数据库配置管理界面绑定即切）
- `ThirdAdapterInterface`：prepay / verifyNotify / verifyRefundNotify / buildNotifyResponse
- `WalletOperator`：钱包契约（sufficient/deduct/credit），接口注释写明**两跳换算模型**（订单币种 →市场汇率→ 本位币 →锚定汇率→ 钱包币种）与**快照回款原则**（退款按 PayRecord.options.wallet 快照逆向换算，绝不重新换算）

**驱动层**（`pay/src/Adapters/`）：
- `WechatAdapter` / `AlipayAdapter`（yansongda v3，共享 `Concerns/ManagesYansongda` 装配）：method 路由 mp/mini/h5/app/scan/web/wap；openid 只经 extra 传入；微信仅 CNY；支付宝金额自动分↔元；微信退款异步（Ing→退款回调完成）、支付宝/余额同步完成
- `MoneyAdapter`：调容器绑定的 WalletOperator，扣款与支付单落库同事务，快照固化 `PayRecord.options.wallet`
- **聚合平台/境外渠道零内置**：`PayManager::extend('huifu', Adapter::class | Closure)` 注册即用（测试有 FakeThirdAdapter 完整示范）

**编排层**（`PayOperator`，吸收了旧 PayRecord 服务类）：
- `pay(?int amount, array extra)`：金额 ≤ 剩余应付（支持分批/定金）→ 建单 → 余额直接完成（事务+事件）/ 第三方自动 prepay 返回 sdkResult
- `notify(Request)` / `handleNotify(NotifyPayload)`：验签 → **按 pay_sn 幂等**（已支付直接应答成功）→ 金额防篡改 → 加锁事务标记 → 反查 payable 调 checkAndPaid() → PaySucceeded
- `refund(PayRecord, ?fee, params)`：重查防陈旧模型、渠道匹配、剩余可退校验、三种完成路径、RefundSucceeded/RefundFailed
- `refundNotify` 同构幂等

**回调**：`POST /pay/notify/{channel}/{method}` 统一入口（`sn-pay.notify` 路由），薄 PayController；notify_url 归 pay 包（机器间通信），return_url 由调用方 extra 传入（用户浏览器跳转）

**配置**（`sn-pay.php` `channels` 结构）：每渠道 `enabled` + `methods` 白名单 + `tenants`（yansongda _config 多租户键）；证书 `.pem/.crt` 相对路径自动解析 `storage/app/private/`；`Utils::getAvailableMethods()` 供收银台读取（杜绝 UI 有选项无驱动）

**接入现状**：Order 实现 PayableInterface（Payable trait，金额走 sn_money、currency 快照）；OrderOperate::checkAndPaid 修复（bcsub Money 对象崩溃 + fromMinor 赋值）；OrderCreate 固化 currency；Member use UserPayerable 实现 PayerInterface；shop Cashier 重写（payer+payable+channel 调用链、payMethods 配置驱动、金额 sn_money 展示）；order/shop/member composer 声明 pay 依赖；OrderTest 6 + PayTest 13 用例（契约/fake 渠道/余额支付/余额不足/分批/超付拒绝/回调幂等/防篡改/未知单号/全额退款/部分退款+超退拒绝）

### 已知边界（B 期不含）

- 真实商户配置联调未做（需商户号/证书，`.env` 开 `SN_PAY_WECHAT_ENABLED` / `SN_PAY_ALIPAY_ENABLED` 后验证）
- 钱包扩展本体未做（WalletOperator 契约就绪，绑定后 money 通道即启用）
- stripe/paddle/聚合平台未实现（抽象就绪）——国外侧分析结论：cashier 订阅向不适用商城；Stripe 直连（PaymentIntent）多币种卡支付；Paddle 为 MoR 模式（代处理全球税务，跨境试水省心但费率高），二期按目标市场选型
- pay 后台 Filament 资源未做（P2）

---

## 四、阶段 C：order 管道重排 + 场景注册表 + shop 接线（✅ 2026-09-25 完成）

> 交付形态与原计划有架构升级（管道归属重排 + 扩展机制），C1-C6 断点全部修复，端到端测试兜底。

### 交付架构（重排后）

```
support   Rocket 基类修复（原三个 trait 文件从未迁移、类加载即 fatal → 直接实现 ArrayAccess/JsonSerializable）
order     纯引擎：OrderCreate（fetch/check/calc/summary/creating 五阶段管道执行器）
          OrderPipes 注册表（scene=orders.type + stage + key 锚点，after/before/replace/remove/prepend/append）
          Order 场景管理器（registerShortcut(type, class)；退款/失效经 resolveShortcut 反查）
          Pipes/Common/*（通用管道）+ CommonPipes 聚合器 + Shortcuts/Base（配方基类，含 SavesOrderItems）
          RefundSucceeded 监听 → OrderOperate::refunded()（退款状态流转 + 场景补偿管道骨架）
          依赖：pay + support（不再依赖 product/user）
product   商品域管道：Pipes/{Fetch,Check,Calc,Summary}/Product（字段名修正 + 整数分计价）
          require order（新增）；original_price 划线原价列（products + variants，双侧迁移同步）
shop      Order/Shortcuts/ProductShortcut（type='product' 配方）+ 确认页接 sn-order::components.confirm
legacy    order/legacy/ 存档被移除的管道（LimitBuy/Money/Score + 原 Shop.php，限购/余额抵扣/库存留待扩展包）
```

### 关键决策落地（详见会话决策记录 19-27）

- C1/C2 全部断点修复：`->show()`→`->up()`、attributes 管道移除、`sku_type→spec_type`、`convert_num→stock_convert_num`、`product_sku_text→product_spec_text`、`mainUrl→image`
- C3 金额管道全面整数分重写：`radarAdditionMinor`、JSON 字段集 `键=>分`、模型赋值 `fromMinor`、`fields_infos` 十进制串仅展示
- BuyerInterface **整体删除**（morph 用 Laravel 原生；buyer = ?Model）——接口负担归零
- 退款 payload 预留 item 级结构（售后扩展包将来填充 refund_items）
- OrderAction 操作人参数化（?Model $operator，缺省 system）
- price_change 定时改价修复：SPU + 多规格变体统一改价

### C6 端到端验收（✅）

`tests/Feature/Order/OrderPipelineTest.php` 11 用例：场景注册表反查、OrderPipes 六种插拔、试算整数分断言、落单全字段断言、多商品合计、库存拒绝（含多单位换算）、**落单→余额支付→paid→部分退款 hasrefund→全额 refunded→快照回款**、确认组件渲染。全仓 408 用例 1431 断言全绿。

### 原 C4 决策点的最终拍板

1. original_price：加列（products + variants，可空，空=无划线价）
2. 限购：移除，规划独立扩展包（legacy 存档）
3. BuyerInterface：删除（见上）
4. 库存：移除占位管道，规划库存销量扩展包（legacy 存档）

### C1 Get 管道断点（一调即崩）【✅ 已修复，留档】

- `Pipes/Shop/Get/Product.php:25`：`->show()` → Product 模型只有 `scopeUp`（补 scopeShow 或改 scopeUp，语义上=可购买态 Up|Hidden）
- 同文件 `with(['variants', 'attributes' => ...])`：Product 无 `attributes()` 关联（只有 specs）——attribute 体系已搁置（用户决策），改为 `with(['variants'])` 并移除 ProductAttribute 相关管道的注册

### C2 字段名错位（静默错账，ThinkPHP→Laravel 迁移改名未同步）

| Pipe 里的旧字段 | 实际字段 |
|---|---|
| `$product['sku_type']` | `spec_type` |
| `$product['original_price']` / `$variant['original_price']` | **列不存在**（原价方案见 C4 决策） |
| `$currentVariant['convert_num']` | `stock_convert_num` |
| `$product_sku_text` | `product_spec_text` |
| `$variant['mainUrl']` / `$product['mainUrl']` | `image` |
| `limit_type` / `limit_num`（LimitBuy 管道） | **列不存在**（限购方案见 C4 决策） |

### C3 金额管道按新规范重写（核心）

- Calc/Summary/Shortcuts 全部 `sn_currency()` 调用点迁移到 `sn_money()`：运算方法名兼容度高（add/subtract/multiply），注意输入口径差异（旧 parseMoney 标量按元、新 money() int 按分）
- **JSON 金额改整数分**：`OrderCreate::saveOrder` / `Shortcuts/Shop::save` 的 `formatByDecimal(...)` 全部替换为 `sn_money()->jsonFields(...)`（存整数分）；`fields_infos` 展示性字段可冗余存格式化串但不参与运算
- 模型金额赋值一律 `fromMinor()`（规范第 6 条）
- Creating/Money、Creating/Score 管道中被注释的 pay 调用：Money 管道改为预留（余额抵扣属钱包扩展域，等钱包落地再接），本期移除或空实现

### C4 需要拍板的决策点（开工前确认）

1. **original_price（划线原价）**：products/variants 无此列。方案 a) 加列（简单）b) 用定时任务/字段 `original_price` 进 options JSON。倾向 a，但涉及 product 表结构
2. **限购（limit_type/limit_num）**：本期做（加列 + LimitBuy 管道）还是移除管道占位后置？
3. **BuyerInterface 落地**：Order 已有 morphs('buyer')；需要 User/Member 实现 BuyerInterface（getBuyerInfo 之类）+ `orders()` morphMany 关联；Confirm 组件 `?BuyerInterface $buyer` 才有真实买家的来源

### C5 其余断点

- `OrderCreate::saveOrderAddress()` 引用不存在的 Address 类（死方法，删或接 user 包 Address）
- `OrderAction::add()` 操作人硬编码 `['type' => 'user', 'id' => 1]` → 参数化传入（buyer 或 admin operator）
- `Shortcuts/Shop::getRefundPipes()/getInvalidPipes()` 引用 8 个不存在的类（StockBackPipe 等）——本期只保留真实管道，库存扣减/回补留 P2 专门做
- shop 确认页 `order/confirm.blade.php` 是 `@sn todo` 空态 → 嵌入 `sn-order-confirm` 组件，relate_items 查询参数格式对齐
- order 的 confirm.blade 引用 `sn-user::components.choose-address`（组件未迁移）→ user 包补该组件或确认页先隐藏地址块
- `OrderRocket`/`OrderCreate` 遗留 `$radars['options']` 未定义变量（`?? []` 兜住，顺手清理）

### C6 端到端冒烟测试（验收标准）

Feature 测试：创建商品（含变体+价格）→ 构造 relate_items → OrderCreate calc → create 落单（金额/JSON 快照断言）→ fake 渠道支付 → 回调 → 订单 paid → 部分退款。跑通即阶段 C 验收。

---

## 五、阶段 D + P1/P2/P3（后续路线）

### 阶段 D：shop 前台闭环收尾

- pay-finish 结果页 + 收银台各 method 的前端消费（扫码二维码展示/H5 跳转/JSAPI 唤起）——后端已返回 sdkResult，前端按 method 渲染
- 确认页收货地址选择（依赖 user 包 choose-address）
- 支付超时关单（订单过期时间 + 队列，OrderOperate::created 里被注释的旧逻辑重写）

### P1 商品分类闭环（需求原始输入 #2/#4）

1. `sn_category_product` pivot 迁移（范本 cms 的 `sn_category_post`：`foreignIdFor` + 复合主键 + cascadeOnDelete；建议独立迁移不动存量——用户曾倾向并入 product 原迁移重跑，按 database.md 可 `migrate:fresh`，两可）
2. ProductForm 加分类字段（category 包，KalnoyNestedsetSelectTree 或 Select multiple）
3. 前台分类页/列表筛选（复用 `scopeCategoryIds` + category 包 `has_category()` 体系）
4. 顺手修：ProductTable ForceDelete 的 `categories()->delete()` 无效调用

### P2 交易周边

- **库存**：Creating 管道锁/扣 `variants.stock`，取消/失效回补（Shortcuts 里 8 个占位管道的真实实现）
- **后台管理**：OrderResource（五层结构，状态操作/退款操作）、PayRecord/Refund 资源——pay/order 的 `panel_register` 目前为空
- **收货地址**：user 包地址功能完善 + choose-address 前端组件

### P3 增值与跨境预留

- 购物车（独立 cart 扩展或 shop 自建；当前详情页直连结算可用）
- 数据多语言选型（建议 spatie/laravel-translatable json 模式，独立扩展；**选型前不动商品文本字段**）
- 跨境扩展：汇率/多币种定价/展示币种切换/物流关税（消费 MoneyManager 规范即可插）
- 钱包扩展：WalletOperator 实现本体（积分/余额/佣金/虚拟币 + 锚定汇率配置 + 两跳换算）
- 多租户开启（见"遗留风险"）

---

## 六、遗留问题与风险清单

| # | 事项 | 状态/建议 |
|---|---|---|
| 1 | 真实商户配置联调（微信/支付宝） | 待用户提供商户号/证书，`.env` 开 enabled 后走一遍 prepay+notify |
| 2 | 多租户开启前置：`GeneralSettings::repository()` 在 tenancy 开启时返回 `'team_database'`，但 `config/settings.php` 未注册该 repository——配置 tenant_model 即崩 | 开租户前必须在 settings.php 注册 team_database repository（DatabaseSettingsRepository + 租户库连接） |
| 3 | 多租户验证结论（已验证）：admin 单租户面板不受 tenant_model 干扰（`has_tenancy()` 由"当前请求是否解析到租户"决定，非配置）；Filament 租户上下文按 panel 请求级隔离，可建第二多租户 panel 并存 | 数据面：admin 建的数据 team_id=null，租户 panel 过滤时不可见（平台数据语义，预期行为） |
| 4 | product 包小 bug：定时任务 price_change 写不存在的 original_price 列；`src/Resources/` v3 死代码（AttributeRepositoryResource 用户要求保留参考，CreateProduct 已删） | 触碰 product 时顺手修 |
| 5 | order 配置 `sn-order.models.pay_record` 与 `sn-pay.models.pay_record` 双源 | 阶段 C 清理 order 侧（trait 已用 pay 的解析） |
| 6 | product composer 隐性依赖 medialibrary/money 未显式声明；shop 曾缺 order/pay 声明（已补 pay） | 补声明 |
| 7 | `cknow Money::getDefaultCurrency()` 返回 string（非 Currency 对象） | 已知即可 |
| 8 | 测试缺口：product 包 ProductSpecForm 笛卡尔积重算无测试；order/shop/pay 包内 tests 均为脚手架 | 按需补 |
| 9 | pint 对 addons 目录整跑会产生行尾幻影 M（autocrlf=true + 仓库存 LF） | 提交时自动归一化，无实质影响；尽量按文件跑 |

---

## 七、会话决策记录（已拍板，勿重新讨论）

1. **货币选型**：保留 cknow/money（底层 moneyphp），brick/money 备选不引入；akaunting 不用
2. **JSON 金额**：存整数分（键=>金额），不存小数不嵌币种对象；分摊用 allocate 余数分配
3. **币种存放**：单据级快照（orders/pay_records/pay_refunds），products 可空预留，items/variants 不存
4. **默认币种**：`app.currency`（可选覆盖）→ `sn-support.currency`（CNY）→ 兜底；不用 Laravel 框架配置（13 骨架无此键）
5. **pay 定位**：基础扩展包，只做统一收付抽象/记账/幂等/回调路由/退款编排；不做进件/分账/钱包账本/汇率源/税务（防"支付坦克"）
6. **配置抽象**：PayConfigInterface 可整体替换（一期文件源，多租户阶段换 DB 源，证书加密存储）
7. **回调**：notify_url 统一入口归 pay 包；return_url 调用方传入；extra.notify_url 逃生舱
8. **PayerInterface extends HasSnIdentifiable**；pay() 便捷方法归 trait 不进契约
9. **余额支付**：WalletOperator 契约先行（两跳换算 + 快照回款）；钱包扩展后续落地，未绑定则 money 通道禁用
10. **聚合平台/境外渠道**：只留约定（extend + AdapterInterface），使用者自行实现，不必等内置
11. **order 硬依赖 pay**（composer require），删除 order 本地 PayableInterface；composition 规则已更新
12. **幂等语义**：回调按 pay_sn 幂等（重复通知只生效一次）；创建侧每次点击支付**新建** PayRecord，不复用未支付单
13. **alipay 本期实现**（yansongda 原生）；pay 的 Filament 管理资源后置 P2
14. **地址/发票归 user 包**（Address 已是 morphs 结构）；后续 userInvoice 同理
15. **ProductStatus 四态合理**（Up/Down/Hidden/Draft，符合 support 色板）；Check 管道需拒绝 Down/Draft、放行 Hidden
16. **unit（单位库）不建独立资源页**：Select 的 createOptionForm 内联创建；产品表存 label 不存外键；暂留 product 包（models 映射可替换）
17. **config 以包内为最全基准**（主仓发布版落后无所谓）；迁移改结构直接改原迁移 + stub 双侧 + migrate:fresh
18. **多租户**：扩展包必须支持（team_id/scope 已备），环境暂单租户；第二多租户 panel 可与 admin 并存
19. **管道归属**（阶段 C）：商品域管道归 product 包（product require order），场景配方归消费模块（shop 的 ProductShortcut），通用管道留 order 的 Pipes/Common；契约（PipeInterface + OrderRocket）留 order
20. **场景标识 = orders.type 值**：OrderPipes 注册表按 `scene(type) + stage + key` 寻址；配方注册 `Order::registerShortcut(type, class)`，退款/失效经 type 反查（存 type 不存类名）
21. **订单类型正交维度**：type 只承载"计价/创建配方"差异（小闭合集）；履约方式（快递/自提/外送/核销）独立字段；营销玩法（团购等）走 OrderPipes 插管道；商品形态归商品域；门店维度留待 store 包（scope 方案待议）
22. **退款/失效统一管道化**：同一 Shortcut 承载场景全生命周期；售后（aftersale）是独立状态机不进 shortcut，交汇于退款执行（RefundSucceeded 事件），item 级 refund payload 结构已预留
23. **BuyerInterface 删除**：buyer = ?Model（morph 原生）；「接口下沉判据」——纯身份/展示能力归 support 契约，域知识能力不上公共模型（消费方包加关系或域包 adapter）
24. **公共身份模型（User/Member 包内模型）保持零域依赖**：use/implements 只允许 support + 显式声明的轻增强；宿主 App\User 是自由组装层不受限（PayerInterface 下沉 support 已列入 pay 路线 T3）
25. **Rocket 不引入 yansongda/artful**：自有 100 行实现足够（模式参考）；三个缺失 trait 以直接实现接口替代
26. **充值不走 order**：wallet Recharge 直连 PayableInterface（单标的直付判据）；会员购买看形态（套餐直付直连 / 商品化 SKU 走 order）
27. **限购/余额抵扣/库存均规划独立扩展包**：本期移除占位管道（order/legacy/ 存档），落地后经 OrderPipes 注册表插回
28. **配方管道清单用显式组合，不用模板方法**（2026-09-26 定稿）：Shortcut 基类不做 get/钩子双层隐藏拼接——继承者直接实现接口方法，自己 `array_merge(CommonPipes::xxx(), 业务段)`。理由：清单单一事实源彻底留在配方类（与决策 #20 引擎不注入的原则自洽）；配方是小闭合集 + 单维护者，「忘 merge 通用段」风险由端到端测试金额断言兜底，不需要 final 语言层强制。Base 收缩为薄基类（SavesOrderItems 载体 + 范式注释，abstract）
29. **fields_infos 契约与金额展示分工**（2026-09-26）：FieldInfo 双职责（make 填充 / sorted+render 解析，text/desc 存翻译键、amount 存整数分、order_column 排序锚、high_light→sn-primary-text+bold / sn-neutral-text）；着重显示用 `<x-sn-support::amount>` 组件（色名双通道 sn_text_color），行内循环用 FieldInfo/render 拼串；`formatParts()` 拆分符号与金额。规范沉淀 `.ai/rules/amount-display.md`
30. **身份统一约束 + PayerInterface 删除**（2026-09-27）：所有身份角色（order 的 buyer / pay 的 payer）统一约束为 support 的 `HasSnIdentifiable`；PayerInterface + UserPayerable 删除（payerMask 内联 PayOperator::payerMark，WalletOperator 等签名同步）；**member 首次达成零域包接口依赖**（composer 仅 support+profile+comment+preference 声明）。域包对用户模型约束公式：HasSnIdentifiable（身份）+ morph 原生（引用）+ 域包自己查数据（forBuyer scope 预留）+ 域包自己算展示策略。匿名购买将来经 GuestBuyer 模型（实现 HasSnIdentifiable，参照 Shopify 游客 Customer 模式）接入，收编合并机制届时再议——本期只铺接口统一，不建表

---

## 八、快速参考

- 发起支付：`app('sn-pay')->payer($member)->payable($order)->channel('wechat', 'h5')->pay(null, ['openid' => ...])`
- 注册自定义渠道：`PayManager::extend('huifu', HuifuAdapter::class)`（或 `app('sn-pay')->addChannel(...)`）
- 金额运算：`sn_money()->add/subtract/multiply/sum/allocate/minor/decimal/format`；**模型赋值 `sn_money()->fromMinor(分, 币种)`**
- 回调入口：`POST /pay/notify/{channel}/{method}`；事件 `PaySucceeded/PayFailed/RefundSucceeded/RefundFailed`
- 支付方式清单：`Wsmallnews\Pay\Support\Utils::getAvailableMethods()`
- 测试：`php artisan test --compact tests/Feature/Pay/PayTest.php`（PHP 路径 `C:/Users/XPKJ-003/.config/herd/bin/php84/php.exe`，bash 里 php 不在 PATH）

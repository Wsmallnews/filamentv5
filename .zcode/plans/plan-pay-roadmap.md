# pay 通用支付扩展包路线图

> 状态：阶段 B（pay 重构）已完成；wallet 余额通道已由 wallet 包落地绑定（`WalletOperator::class → PayWalletOperator`）。
> 本文档是 pay 包的**唯一计划基准**：现状基线、渠道扩展机会、分期计划、架构改进项、渠道接入清单。
> 关联文档：`.zcode/plans/plan-mall-module-roadmap.md`（商城总路线，pay 是其阶段 B 交付）、`.ai/rules/addons-src.md`（货币/依赖规则）。
> 创建：2026-09-25（发现 yansongda/pay v3 已支持 8 渠道后整理）。

---

## 一、现状基线（阶段 B 交付摘要）

- **契约层**：PayableInterface / PayerInterface / PayConfigInterface / ThirdAdapterInterface / WalletOperator（`pay/src/Contracts/`）
- **驱动层**：WechatAdapter / AlipayAdapter（yansongda/pay v3）+ MoneyAdapter（余额，调容器 WalletOperator）
- **编排层**：PayOperator（pay / notify / refund / refundNotify，按 pay_sn 幂等 + 金额防篡改）
- **回调**：`POST /pay/notify/{channel}/{method}` 统一入口
- **扩展点**：`PayManager::extend('channel', Adapter::class | Closure)` 注册即用
- **余额通道**：✅ wallet 包已实现 PayWalletOperator 并绑定容器，money 通道开启即用（两跳换算 + 快照回款）

---

## 二、渠道扩展机会（yansongda/pay v3 支持 8 渠道）

yansongda/pay v3 当前原生支持：**支付宝、微信、抖音、PayPal、Stripe、银联、江苏银行（JSB）、Airwallex**，每渠道基本都覆盖 支付/查询/退款/关闭/取消/回调（JSB 无关闭/取消）。

**对 pay 包的意义**：B 期的 ThirdAdapterInterface 抽象已就位，且 yansongda 各渠道走统一 API 形状——**新增一个渠道 = 一个薄 Adapter（prepay/verifyNotify/verifyRefundNotify 映射）+ 一段 channels 配置 + 一组测试**，不再需要对接各渠道原生 API。

**渠道分类与定位**：

| 渠道 | 分类 | 定位 | 优先级建议 |
|---|---|---|---|
| 支付宝 / 微信 | 国内主流 | ✅ B 期已接 | T0 联调 |
| 抖音 | 国内补充 | 抖音生态（小程序/APP 支付） | T1 按业务接入 |
| 银联 | 国内补充 | 银行卡/云闪付，覆盖不用微信支付宝的人群 | T1 按业务接入 |
| 江苏银行 | 区域银行 | 特定行业场景才需要 | 按需 |
| Stripe | 跨境 | 国际卡支付，多币种，跨境试水首选之一 | T2 选型 |
| PayPal | 跨境 | 海外主流电子钱包，欧美用户习惯 | T2 按目标市场 |
| Airwallex 空中云汇 | 跨境 | 跨境收款/换汇/多币种账户 | T2 选型 |

**概念澄清**（记录当时的讨论）：Airwallex 与汇付天下/随行付**不是同类**——汇付/随行付是国内聚合收单机构（一次接入聚合微信/支付宝等国内渠道）；Airwallex 是跨境支付平台（外币收款、换汇、多币种账户）。分类上都是"持牌支付机构渠道"，但一个服务国内收单、一个服务跨境收款。国内聚合收单（汇付类）在我们架构里对应 `PayManager::extend()` 自定义 Adapter（聚合平台只给一个商户号，回调统一），不适用 yansongda 的分渠道模式。

**P3 结论更新**（覆盖商城路线图旧结论）：原计划"Stripe 直连 PaymentIntent 自建 / Paddle MoR 模式二选一"→ 更新为 **yansongda 统一接入优先**（Stripe/PayPal/Airwallex 都走 yansongda Adapter，接入成本一个文件），`PayManager::extend()` 逃生舱保留给 yansongda 未覆盖的渠道（含国内聚合收单）。

---

## 三、分期计划

### T0：真实商户配置联调（短期，阻塞在商户号/证书）

- [ ] 微信：`.env` 开 `SN_PAY_WECHAT_ENABLED`，配置商户号/证书，走通 prepay + notify + 退款回调
- [ ] 支付宝：同上（`SN_PAY_ALIPAY_ENABLED`）
- [ ] 联调通过后把「已知边界」中的"真实商户配置联调未做"关闭

### T1：国内渠道补充（按业务需要触发）

- [ ] 抖音 Adapter（`pay/src/Adapters/DouyinAdapter.php`）+ `sn-pay.php` channels.douyin 配置块 + method 白名单
- [ ] 银联 Adapter（同构）
- [ ] 各渠道 PayTest fake 用例（复用 FakeThirdAdapter 模式）

### T2：跨境渠道（P3 期，按目标市场选型后实施）

- [ ] 按目标市场选定首发渠道（建议：欧美卡支付 → Stripe；东南亚/换汇需求强 → Airwallex；PayPal 按用户画像按需）
- [ ] 对应 Adapter ×（1~3 个）+ 配置 + 测试
- [ ] 跨币种链路验证：外币支付 → PayRecord.currency 快照 → 退款按快照回款（WalletOperator 两跳换算已有契约）
- [ ] 注意：境外渠道主体资质、结算周期、税务合规——接入前业务侧确认，不在 pay 包职责内（决策记录 #5：不做进件/分账/税务）

### T3：架构改进项（穿插进行）

- [x] ~~**PayerInterface 下沉 support**~~ → **已升级为直接删除**（2026-09-27 实施）：付款人约束统一用 support 的 `HasSnIdentifiable`（身份统一契约，User/Member 早已实现）；`payerMask()` 单号标识逻辑内联 `PayOperator::payerMark()`；`UserPayerable` trait 删除（便捷入口统一 `app('sn-pay')->payer($member)`）；WalletOperator/PayPayload/RefundPayload 签名同步改 HasSnIdentifiable；member 摘除 pay 依赖（member 首次达成零域包接口依赖）
- [ ] **PayConfig 数据库化**（多租户开启前置）：实现 PayConfigInterface 的 DB 源（租户级渠道配置 + 证书加密存储），文件源作为默认回退；决策记录 #6 已预留
- [ ] **pay 后台 Filament 资源**（对齐商城路线图 P2）：PayRecord / PayRefund 资源（五层结构，含渠道/金额/回调日志查看）
- [ ] 渠道健康检查命令（`sn-pay:diagnose`：配置完整性/证书可读性/回调路由可达性），T0 联调时顺手做

---

## 四、新渠道接入清单（模板，每个渠道照抄）

1. `pay/src/Adapters/XxxAdapter.php` implements ThirdAdapterInterface
   - `prepay()`：yansongda 统一 API 映射（注意 method 路由：mp/mini/h5/app/scan/web/wap）
   - `verifyNotify()` / `verifyRefundNotify()`：验签 + NormalizeNotifyPayload（金额口径核对！）
   - `buildNotifyResponse()`：渠道要求的应答格式（微信 XML/JSON、支付宝 success 等）
2. `sn-pay.php` 增加 `channels.xxx` 配置块：`enabled` + `methods` 白名单 + 证书路径（相对 `storage/app/private/`）
3. `Utils::getAvailableMethods()` 自动纳入（收银台下拉自动出现，杜绝 UI 有选项无驱动）
4. 金额口径核对：yansongda 各渠道对分/元的接受度不同（支付宝自动换算、微信仅 CNY 整数分），Adapter 内统一在入口处转好
5. 币种约束标注：渠道支持的币种清单写进 Adapter 类注释（微信仅 CNY；Stripe/Airwallex 多币种）
6. PayTest 补 fake 用例：支付/回调幂等/金额防篡改/退款完成路径

---

## 五、注意事项与风险

| # | 事项 | 说明 |
|---|---|---|
| 1 | yansongda 版本锁定 | 锁 `^3.7`，渠道 API 变化跟随其 minor 升级；升级前跑 PayTest 全量 |
| 2 | 金额换算口径 | yansongda 内部各渠道有分/元差异，Adapter 是唯一换算点，禁止在调用方换算 |
| 3 | JSB 渠道能力缺口 | 江苏银行无关闭/取消 API，close/cancel 操作需在 Adapter 层抛不支持异常 |
| 4 | 境外渠道合规 | Stripe/AirPal/Airwallex 涉及主体资质与结算合规，pay 包只做技术接入 |
| 5 | cknow Money::getDefaultCurrency() 返回 string | 已知即可（商城路线图遗留 #7） |
| 6 | 回调幂等依赖 pay_sn | 任何新渠道的 notify 都必须能取出稳定单号，否则不能接入（契约红线） |

---

## 六、pay 侧决策记录（不重复商城路线图已拍板的 18 条）

1. **新渠道一律走 yansongda Adapter**，不自建渠道 API 对接（除非 yansongda 不支持 → PayManager::extend）
2. **国内聚合收单（汇付/随行付类）不属于 yansongda 渠道**，走 extend 自定义 Adapter（一次接入多渠道，回调按平台规范）
3. **PayerInterface 下沉 support** 已列入 T3（2026-09-25 拍板，member 纯净化联动）
4. **P3 跨境原选型结论作废**，以本文档第二节"P3 结论更新"为准

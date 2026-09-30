---
paths:
  - 'addons/*/resources/views/**'
  - 'addons/order/src/**'
  - 'addons/product/src/**'
---

# 金额展示与 fields_infos 契约

## 金额展示两个场景，严格分工

- **着重显示**（商品大价格、划线原价、促销价、确认页应付合计）→ `<x-sn-support::amount>` 组件（小符号 + 大数字，items-baseline）
  - `color`：六色名（primary 默认 / danger / success / info / warning / gray）→ 预置类；hex / `Color::Xxx` 色板数组 → `sn_text_color()` 动态变量通道
  - `symbol-size` / `amount-size`：Tailwind 字号类（默认 text-sm / text-2xl；列表卡片用 text-xl）
  - `amount`：int 分 / Money 对象 / 元字符串均可（组件内 `sn_money()->formatParts()` 统一）
- **行内 / 循环汇总**（费用明细、订单详情 fields_infos 循环）→ 直接用拼好的字符串，**不要**嵌组件
  - 单值场景：`sn_money()->format($value)`（带符号）或 `formatParts()`（要拆符号时）
  - fields_infos 循环：`\Wsmallnews\Order\Support\FieldInfo::sorted($fields)` 排序 + `FieldInfo::render($item)` 返回整行 HtmlString，视图只写 foreach，禁止在 blade 里重写字段渲染逻辑（翻译/金额格式化/颜色映射全部收编在 FieldInfo）

## FieldInfo 数据契约（make 填充 / sorted+render 解析，唯一所有者）

- `text` / `desc` 存**翻译键**（不是译文），渲染时 `__($text, $text_params)` 求值；键缺失原样回显兼容存量数据
- `field_type=amount`（默认）时 `value` 存**整数分**；其他类型（text 等）存展示值
- `order_column` 是跨管道排序锚（商品 1 / 运费 2 / 优惠券 3...），新计价管道必须声明
- `high_light`：true → `sn-primary-text font-bold`；false → `sn-neutral-text`（常规正文近黑色，纯颜色类，勿用捆绑字号的 sn-content-text）

## 颜色类纪律

- 纯颜色 `sn-*-text` 族：`sn-primary-text` / `sn-danger-text` / `sn-success-text` / `sn-info-text` / `sn-warning-text` / `sn-gray-text`（弱化）/ `sn-neutral-text`（常规近黑）
- 自定义动态色走 `sn_text_color()`（返回 class+style），不要在视图里手拼 `text-{色名}-600` 动态类名（Tailwind JIT 不可见）
- 新增颜色类必须是纯颜色（不带字号/字重），带排版的用 `sn-h*-text` / `sn-content-text` 族

## formatParts 规范

`sn_money()->formatParts($value, $currency = null): array{symbol, amount}`——符号与千分位金额一次返回（保证同币种/locale 一致）；非两位小数币种（JPY/KWD）将来只在 MoneyManager 此方法内适配。禁止调用方自行组合 `Number::format` + `decimal`。

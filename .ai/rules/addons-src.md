---
paths:
  - 'addons/*/src/**'
---

# Addons Src

## Eloquent 查询链顺序：无参 scope → 带参 scope → with → where → 终结方法
Eloquent 查询链统一顺序：① 无参数的模型 scope（normal()/published()/hidden()/ordered() 等）最前；② 带参数的 scope（snScope()/scopeable()/自定义带参 scope）其次；③ with()/withDepth() 等预加载修饰；④ where()/whereHas() 条件；⑤ 终结方法（first()/find()/get()/paginate()）。例：Model::query()->normal()->snScope(...$scopeable)->where('type', $type)->first();

## purpose 槽位注册 meta 形态与跨包翻译时机陷阱
purpose 槽位注册（CompositionRegistry::registerPurposes）用 meta 数组形态：['label' => ..., 'positions' => ['left', 'right', '自定义' => '标签或翻译键'], 'default' => ..., 'context' => fn (array $params, array $scopeable): array => ['post' => ...]]。positions 标准位置（left/right/top/bottom）label 存 sn-support::composition.position.* 翻译键，自定义位置 键=>标签；重复注册：字符串仅覆盖 label（保留 meta），数组整体替换。**关键陷阱：注册时机禁止调用 __() 跨包命名空间**——provider boot 顺序不定（cms 早于 support），过早翻译会在翻译 hint 注册前触发分组加载并缓存空组，整个 sn-support::composition.* 组全请求失效；label/position 标签一律延迟到消费方（表单/渲染）翻译。resolveForPurpose(purpose, module, scopeType, scopeId, $params) 的 $params 是调用方路由参数（如 ['slug' => ...]），经槽位 context 提供者映射为 pageContext（null 值剔除=未命中不注入），路由壳因此不感知业务模型（薄壳）。purpose=null 仍是通用展示编排（Page 绑定通道），槽位机制互不干扰。

## purpose 槽位注册 meta 形态（闭包标签/layout_mode/context 提供者）与跨包翻译时机陷阱
purpose 槽位注册（CompositionRegistry::registerPurposes）用 meta 数组形态：['label' => fn () => __('...'), 'positions' => ['left', 'right', '自定义' => '标签|翻译键|闭包'], 'default' => ..., 'layout_mode' => 'stack'|'rows'（可选，未声明按位置语义推导：左/右=stack 单列堆叠、上/下=rows 行式、无槽位=rows）, 'context' => fn (array $params, array $scopeable): array => [...]]。**标签一律闭包或纯文本（消费时经 resolveLabel 求值），禁止注册时跨包 __()**——provider boot 顺序不定（cms 早于 support），过早翻译会抢在翻译 hint 注册前触发分组加载并缓存空组，整个 sn-support::composition.* 组全请求失效。标准位置 label 由 Registry 自动生成翻译键闭包，注册方无需接触 support 键。resolveForPurpose(purpose, module, scopeType, scopeId, $params) 的 $params 是调用方路由参数（如 ['slug' => ...]），经槽位 context 提供者映射为 pageContext（null 值剔除），路由壳保持薄壳。表单侧：CompositionForm 堆叠模式隐藏分栏开关与右栏、按钮文案「添加侧栏块」、位置/槽位切换经 syncRowsToLayoutMode 强制行通栏；purpose=null 仍是通用展示编排（Page 绑定通道）。

## 模块依赖关系：member/user 平级，直接引用即声明；pay 契约归 pay 包（order 硬依赖）
member 与 user 是平级包：user = 认证体系（guard/登录注册组件/UserConfig/2FA），member = 会员实体（Member 模型 + ResolveMember 把 guard 用户解析成 Member，多租户用）。前端模块（cms/shop/order）认证经 config 的 auth_user_type（'member'|'user'，默认 member）切换，路由按其值追加 ResolveMember 中间件。composer 声明原则：代码直接引用的包都要声明——shop 声明 cms+member+pay+preference+product+support+user（cms 自身漏声明 user 属历史遗漏，勿效仿）。pay 契约（阶段 B 重构后）：PayableInterface/PayerInterface 归 pay 包，order **硬依赖** pay（composer require，PayRecord 模型经 sn-pay.models 配置解析）；Order 的 Payable trait 实现 pay 契约（金额整数分口径，currency 行内快照）；Member use UserPayerable 实现 PayerInterface。自定义渠道（聚合平台/境外）经 PayManager::extend() 注册适配器，不进 pay 包内置。

## ModuleRegistry 模块登记与组件链接注入约定；Livewire 4 livewire 标签必须全名闭合
模块身份登记：各包 ServiceProvider packageRegistered() 里 `ModuleRegistry::register(new Module(id: static::$name, namespace: 'Wsmallnews\Xxx', plugin: XxxPlugin::class))`（无 Filament 插件的包 plugin 传 null）。ModuleRegistry（Wsmallnews\Support\Modules，纯静态）提供：moduleOf($class) 类名反查（HasModuleContext::getOwnerModule 的默认实现，组件不再手写归属）、has/get/all/require（功能注册表入口校验模块存在）、plugin($id) 插件实例。后续模块级元信息在 Module 描述符上加 readonly 属性，登记侧写法不变。组件内不生成跳转链接：用户区等页面级内容由调用方以命名 slot 注入（如导航组件 $userZone），链接与组件 props 在调用方模块语境里生成。**Livewire 4 陷阱：livewire 标签带子内容（slot）时闭合标签必须全名 `</livewire:sn-xxx::components.yyy>`，`</livewire>` 简写会导致组件不渲染且闭合标签泄漏为文本**。导航树的跨模块链接节点用 Route/Url 类型（Page/首页类型节点链接固定生成 cms 路由，页面内容本就归 cms 域）。

## 异常消息必须英文或多语言键；安装命令命名 XxxInstallCommand；功能子系统归 Features/
抛出异常的消息文本必须是纯英文或经翻译体系（__() 多语言键）输出，禁止硬编码中文异常消息（中文只允许出现在注释与翻译文件 zh_CN 中）。理由：异常消息会被日志/监控/检索工具消费，硬编码中文在非中文环境不可检索。新代码一律遵守；存量中文异常消息在触碰到该文件时顺手改为英文。另外：安装命令类命名统一为 XxxInstallCommand（对齐 cms/member/product 既有惯例）。support 的功能子系统一律放 src/Features/<子系统>/ 目录（Search/Feed/Seo/Modules 等），不要在 src/ 下新开顶层功能目录。

## 金额处理统一走 sn_money()（MoneyManager），禁止绕过规范手写金额逻辑
1) 存储一律整数最小单位（分），列类型 unsignedBigInteger（score_amount 等积分字段除外）。
2) 币种单据级快照：sn_orders/sn_pay_records/sn_pay_refunds 必有 currency char(3)（默认 CNY）；sn_products.currency 可空=站点默认；order_items/变体不存币种随单据/SPU。
3) JSON 金额明细（amount_fields 等）存整数分（键=>金额），不存小数不嵌币种。
4) 运算/分摊/格式化唯一入口 sn_money()（MoneyManager）：int=分、string/float=元、Money 透传；优惠拆单用 allocate() 余数分配，禁止手工除法四舍五入。
5) 模型 cast 用 MoneyCast::class.':currency' 绑定行内币种列（写入标量视为元）。
6) 默认币种唯一事实源 config('app.currency')（.env APP_CURRENCY），SupportServiceProvider 已激活 Number::useLocale/useCurrency。
7) 旧 Features/Currency + sn_currency() 已 @deprecated，order 管道阶段 C 改造后删除。

## 异常消息约定：业务异常抛出点翻译，系统错误保持英文
用户可见的业务异常（余额不足、库存不足等）在抛出点直接经 __() 翻译并注入业务参数（如钱包类型名），调用方 catch 后 $e->getMessage() 即可直接返回前端：throw new XxxException(__('pkg::errors.insufficient', ['type' => $type->name]))。系统/配置类错误（类型未注册、快照缺失、参数非法等开发者错误）保持英文消息供日志/监控检索。约定记录在 .ai/rules，不要写在异常类 docblock 里。

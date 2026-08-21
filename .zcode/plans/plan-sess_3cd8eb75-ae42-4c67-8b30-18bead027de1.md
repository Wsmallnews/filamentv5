# ProductSpecForm 七项改进计划

先说研究结论,再逐条给方案。核心发现:Filament v5 Repeater table 的**表列与 schema 字段按顺序一一对应**,字段 `visible(false)` 时表头 `th` 仍会输出(这正是问题 6"空列"的根因);`TableColumn` 原生**不支持** toggleable(源码只有 `width/alignment/label` 方法),问题 3 需要自定义实现。

---

## 1. 封装 isSpecXxx 快捷方法

在 `ProductSpecForm` 新增内部统一方法 + 4 个快捷方法:

```php
protected static function isSpecOf(Component $livewire, ProductSpecType ...$types): bool
{
    return static::isSpecType(static::specTypeOf($livewire), ...$types);
}

public static function isSpecSingle(Component $livewire): bool
public static function isSpecMultiple(Component $livewire): bool
public static function isSpecMainMultiple(Component $livewire): bool
public static function isSpecUnit(Component $livewire): bool
```

保留 `isSpecType(mixed $value, ...)` 作为统一方法(`computeVariants` 中传原始 specType 的场景继续用它)。全文 11 处 `static::isSpecType(static::specTypeOf($livewire), ...)` 替换为快捷方法,`hasSpecs()` 也改走 `isSpecOf`。

## 2. Unit 模式 itemLabel 显示「未命名规格」

根因:specs 项的 `name` state 为空时(如切换类型前建的空项,`default` 闭包只对新建项生效,`formatStateUsing` 只在 hydrate 时求值,切换类型都不会重跑),itemLabel 落到兜底文案。两层修复:

- `specsRepeater()` 的 `itemLabel` 闭包注入 `$livewire`(已确认 Filament 闭包默认支持注入),Unit 时直接返回 `Spec::UNIT_NAME`,不看 state;
- `specTypeField()` 切换到 Unit 时,在 `afterStateUpdated` 中把 `specs` 各项 name 归一化为「单位」(`$set('specs', ...)` 保留 children),让 state 本身正确、显示与保存一致。

## 3. 列可切换显示(自定义 toggleable)

原生不支持,采用「**集中列定义 + session 偏好 + 表头设置 Action**」方案,同时覆盖 specValuesRepeater 和 variantsRepeater:

- 新增列定义方法,每列含 key、表头、宽度、字段闭包、是否可选(`optional`,只有重量/货号这类属性列可选,名称/图片/规格列固定);
- schema 字段数组与 `table()` 列数组**从同一份定义动态生成**(table 已支持闭包),保证字段与列严格对齐、不再手写两份;
- repeater 头部加一个「列设置」icon Action(`Filament\Actions\Action` + modal 内 `CheckboxList`),勾选结果写入 session(`sn-product.variant_columns` 等 key);
- 列读取闭包从 session 读偏好,默认全显;被隐藏列的字段不进 schema(state 保留在 Livewire data,保存走 `getRawState()`,现有机制已验证不丢数据)。

## 4. 规格列改为纯文字

`Placeholder` 在 v5 已废弃,官方替代是 `TextEntry`(`Filament\Infolists\Components\TextEntry`,通用 schema 组件,可在 repeater cell 中渲染)。用 `TextEntry::make('product_spec_text')->hiddenLabel()` 替换 disabled TextInput:

- Entry 从 item state 读取回填值,`computeVariants` 生成的文本直接显示;
- Entry 不参与脱水,保存不受影响——`ProductSpecService::save` 本来就从 `spec_names` 重建 `product_spec_text`,不依赖表单字段。

## 5. 换算比例字段封装

新增 `protected static function convertNumField(string $name): TextInput`,包含现有的 numeric/minValue/default/helperText(Unit 提示)逻辑,`variantsRepeater` 调用处替换。

## 6. 换算比例空列

根因:`table([...])` 是静态数组,非 Unit 模式下表头始终输出「换算比例」,而字段 `visible(false)` 只渲染隐藏 td → 表头多出空列。采用方案 3 的集中列定义后自动解决:换算比例列的可见性(仅 Unit)与字段从同一份定义生成。

## 7. 图片上传撑高表格

FileUpload 是 FilePond 渲染,空状态 dropzone 默认高度大、文字换行,列宽 6rem 下被撑得很高。CSS 方案(改 `addons/product/resources/css/index.css`,已有 `.sn-spec-upload` 钩子):

```css
/* 空状态 dropzone 压缩为 4.5rem 正方形,与已上传预览(已有规则)一致 */
.sn-spec-upload .filepond--root { height: 4.5rem; width: 4.5rem; }
.sn-spec-upload .filepond--drop-label { padding: 0.125rem !important; font-size: 0.65rem; line-height: 1.2; }
```

改后跑根项目 `npm run build`(CSS 经 `resources/css/app.css` @import 引入)。具体高度参数以实际渲染微调,目标是行高与普通输入行接近。

---

## 文件改动

| 文件 | 改动 |
|---|---|
| `addons/product/src/Filament/Resources/Products/Schemas/ProductSpecForm.php` | 问题 1-6 全部(重构主战场) |
| `addons/product/resources/css/index.css` | 问题 7 紧凑上传样式 |

## 测试计划

- 现有 13 个测试保持通过(重构不动数据契约);
- `tests/Feature/Product/ProductSpecTest.php` 新增用例:Unit 模式 itemLabel 含「单位」;非 Unit 模式 html 不含「换算比例」表头、Unit 模式含;规格组合首列为 TextEntry 纯文本(断言渲染文本、无 disabled input);
- 改完跑 `php artisan test --compact --filter=ProductSpec` 与 `vendor/bin/pint --dirty --format agent`。
---
paths:
  - 'addons/cms/resources/views/**'
---

# Views

## 前台链接� 须用 x-filament::button 或 generate_href_html
前台需要跳转的元素:优先 `<x-filament::button tag="a" href="...">`(按钮样式统一,且 href 自带 SPA/wire:navigate 模式判断);若用裸 `<a>` 标签,href 必须经 `\Filament\Support\generate_href_html($url, $target)` 输出(自带转义与 target 处理),不要手写 href 属性拼接。纯展开/无跳转语义的占位链接固定 `href="javascript:;"`。

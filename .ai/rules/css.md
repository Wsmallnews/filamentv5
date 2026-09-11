---
paths:
  - 'addons/*/resources/css/**'
---

# Css

## �  PHP 源码中的 Tailwind 类需手动加� � @source 才会编译
各包 index.css 的 Tailwind 扫描范围只覆盖 @source 声明的路径（默认 ../views/**/*；PHP 里写的工具类不会自动扫描）。在包 PHP 源码（如 ActionComponents、ColumnComponents 这类工厂类）中输出 Tailwind 工具类时，必须把该 PHP 文件追加到对应包 resources/css/index.css 的 @source，然后应用根目录 npm run build 重新编译，否则类静默无样式。改完 CSS 后浏览器需强刷。

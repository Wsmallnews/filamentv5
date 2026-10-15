---
paths:
  - 'addons/*/resources/css/**'
---

# Css

## 包样式的唯一分发机制：包内 index.css + 框架层 @import + 应用 Vite 统一编译
各扩展包**不自建 CSS 构建产物**（不走 esbuild 打包 css、不注册 FilamentAsset Css）。正确链路：
1. 包内 `resources/css/index.css` 内容 = `@source '../views/**/*';`（Tailwind v4 扫描本包视图生成工具类）+ 包自定义类/令牌。**禁止在包 css 里 `@import` Filament theme 或其他框架样式**（方向反了：主题由框架层引入；反例：member 包旧文件曾整文件只 import 主题，等于无效文件）。
2. 框架层两处引入（随应用 Vite 编译）：前台 `resources/css/app.css`、后台 `resources/css/filament/admin/theme.css`，各加一段：
   `/* wsmallnews/xxx */ @import "../../vendor/wsmallnews/xxx/resources/css/index.css";`
   **两处都要加齐**（theme.css 曾只引到 product 为止）；新增包时同步补两处，漏补 = 该包视图样式全乱。
3. 改包 css 或包视图新增工具类后：应用根目录 `npm run build` 重新编译，浏览器强刷。
新建包没有 index.css / 未在框架层引入时，页面样式会裸奔（不是"没跑 build"，是根本没接入此机制）。

## 包 PHP 源码中的 Tailwind 类需手动加入 @source 才会编译
各包 index.css 的 Tailwind 扫描范围只覆盖 @source 声明的路径（默认 ../views/**/*；PHP 里写的工具类不会自动扫描）。在包 PHP 源码（如 ActionComponents、ColumnComponents 这类工厂类）中输出 Tailwind 工具类时，必须把该 PHP 文件追加到对应包 resources/css/index.css 的 @source，然后应用根目录 npm run build 重新编译，否则类静默无样式。改完 CSS 后浏览器需强刷。

## 包内 JS 资产走 esbuild 构建（bin/build.js → resources/dist），不经框架 Vite
包的 Alpine/前端 JS 与 css 分流：`package.json`（esbuild）+ `bin/build.js`（多入口 → `outdir: './resources/dist/'`，**必须 `format: 'esm'`**——AlpineComponent 资产经 x-load 动态 import() 消费，IIFE 产物 Alpine.data 注册失败会前端静默失效；多入口且共同目录是 components 时需 `outbase: './resources/js/'` 保持 dist/components/*.js 布局）。产物 dist 提交入库（support 惯例），ServiceProvider 的 AlpineComponent 指向 dist。改 JS 后：包内 `npm run build` → 应用根目录 `php artisan filament:assets` 重新发布 → 浏览器强刷。

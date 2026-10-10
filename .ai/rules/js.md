---
paths:
  - 'addons/*/resources/js/**'
---

# Js

## AlpineComponent 资产构建必须 format: 'esm'
各包 `bin/build.js` 编译 AlpineComponent 资产时**必须显式 `format: 'esm'`**：esbuild 默认产出 IIFE，Filament x-load 的 `import()` 加载 IIFE 模块命名空间为空 → 组件函数解析为 false → `Alpine.data` 注册失败，**前端静默失效无任何报错**（swiper/lightbox 曾长期中招：轮播只显示静态首帧）。product/support 包已补；新建 JS 资产照此。改产物后两步生效：包内 `npm run build:scripts` + 根目录 `php artisan filament:assets`，且资产 URL 版本号恒定占位（浏览器需强刷或经 fetch cache:'reload' 预热）。

## addons 包 JS 改动需重建并重新发布资产
修改 addons 包内 resources/js 后，必须两步才会生效：1) 在对应包目录跑 `npm run build:scripts` 重新生成 resources/dist；2) 项目根目录跑 `php artisan filament:assets` 把 dist 重新发布到 public/js|css。只改源码或只构建都不会更新 public 下的副本，页面会一直加载旧 JS/CSS。

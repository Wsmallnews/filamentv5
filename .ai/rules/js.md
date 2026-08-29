---
paths:
  - 'addons/*/resources/js/**'
---

# Js

## addons �  JS 改动需重建并重新发布资产
修改 addons 包内 resources/js 后，必须两步才会生效：1) 在对应包目录跑 `npm run build:scripts` 重新生成 resources/dist；2) 项目根目录跑 `php artisan filament:assets` 把 dist 重新发布到 public/js|css。只改源码或只构建都不会更新 public 下的副本，页面会一直加载旧 JS/CSS。

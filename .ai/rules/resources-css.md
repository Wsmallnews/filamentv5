---
paths:
  - 'addons/support/resources/css/**'
---

# Resources Css

## support �  CSS 分层：� �� � sn-* 类在 utilities.css，� 自有在 index.css
support 包 CSS 分层：utilities.css = 公共 sn-* 工具类（跨包/前后台通用，@import tokens.css 设计令牌）；index.css = 入口（@source 扫描 + @import utilities/content + support 组件自有样式如 sn-search-submit）。新增公共工具类放 utilities.css，包组件自有样式放 index.css；其他扩展包的 index.css 只放包自有样式。改动后需应用根目录 npm run build 重建。

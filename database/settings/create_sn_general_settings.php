<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // 站点基础
        $this->migrator->add('cms_general.site_name', '');
        $this->migrator->add('cms_general.site_slogan', '');
        $this->migrator->add('cms_general.logo', '');
        $this->migrator->add('cms_general.logo_with_site_name', true);
        $this->migrator->add('cms_general.favicon', '');
        $this->migrator->add('cms_general.homepage_banner', '');
        $this->migrator->add('cms_general.default_og_image', '');

        // SEO 默认值
        $this->migrator->add('cms_general.seo_description', '');
        $this->migrator->add('cms_general.analytics_code', '');

        // 联系方式
        $this->migrator->add('cms_general.wechat', '');
        $this->migrator->add('cms_general.phone', '');
        $this->migrator->add('cms_general.email', '');
        $this->migrator->add('cms_general.address', '');
        $this->migrator->add('cms_general.work_time', '');

        // 二维码
        $this->migrator->add('cms_general.wechat_qrcode', '');
        $this->migrator->add('cms_general.wechat_official_qrcode', '');

        // 版权与备案
        $this->migrator->add('cms_general.copyright', '');
        $this->migrator->add('cms_general.copytime', '');
        $this->migrator->add('cms_general.beian_no', '');
        $this->migrator->add('cms_general.beian_url', '');
        $this->migrator->add('cms_general.beian_police_no', '');
        $this->migrator->add('cms_general.beian_police_url', '');
    }
};

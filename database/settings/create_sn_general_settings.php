<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_general.wechat', '');
        $this->migrator->add('cms_general.phone', '');
        $this->migrator->add('cms_general.email', '');
        $this->migrator->add('cms_general.address', '');
        $this->migrator->add('cms_general.wechat_qrcode', '');
        $this->migrator->add('cms_general.wechat_official_qrcode', '');
        $this->migrator->add('cms_general.copyright', '');
        $this->migrator->add('cms_general.copytime', '');
        $this->migrator->add('cms_general.beian_no', '');
        $this->migrator->add('cms_general.beian_url', '');
    }
};

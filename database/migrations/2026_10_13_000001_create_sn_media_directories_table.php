<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_media_directories', function (Blueprint $table) {
            $table->comment('素材逻辑目录（嵌套集树，层级最多 3 级；文件物理路径不含目录，归属经 media.directory_id）');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('租户ID(NULL=无租户上下文)');
            $table->nestedSet();
            $table->string('name', 50)->comment('目录名');
            $table->timestamps();

            $table->index('team_id');
        });

        Schema::table('sn_media', function (Blueprint $table) {
            $table->foreignId('directory_id')->nullable()->comment('逻辑目录(NULL=未归类)')
                ->constrained('sn_media_directories', 'id')->nullOnDelete()
                ->cascadeOnUpdate();
            $table->index('directory_id');
        });
    }

    public function down(): void
    {
        Schema::table('sn_media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('directory_id');
        });
        Schema::dropIfExists('sn_media_directories');
    }
};

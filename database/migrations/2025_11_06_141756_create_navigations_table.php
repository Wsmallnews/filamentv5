<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('navigations', function (Blueprint $table) {
            $table->comment('导航');
            $table->engine = 'InnoDB';
            $table->id();
            $table->nestedSet();        // Nested Set fields for hierarchical structure
            $table->string('type')->nullable()->comment('类型');
            $table->string('name')->nullable()->comment('名称');
            $table->string('description')->nullable()->comment('描述');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigations');
    }
};

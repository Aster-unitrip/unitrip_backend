<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComponentAttractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('component_attractions', function (Blueprint $table) {
            $table->id();
            $table->char('data_id', 20)->nullable()->index()->comment('觀光局資料ID');
            $table->string('name', 512)->comment('名稱');
            $table->string('website', 100)->nullable()->comment('網站');
            $table->char('tel', 100)->nullable()->comment('電話');
            $table->string('historic_level', 6)->comment('古蹟等級');
            $table->string('org_id', 20)->comment('管理機關單位');
            $table->string('city_id')->index()->comment('縣市ID');
            $table->string('town_id')->index()->comment('地區ID');
            $table->string('address')->nullable()->comment('地址');
            $table->string('content', 4096)->nullable()->comment('詳述');
            $table->string('memo', 4096)->nullable()->comment('備註');
            $table->text('experience')->nullable()->comment('合作經驗');
            $table->json('raw_data')->nullable()->comment('原始JSON');
            $table->json('revise')->nullable()->comment('修改後JSON');
            $table->TinyInteger('is_display')->default(1)->comment('顯示狀態 0 不顯示 1 顯示');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('component_attractions');
    }
}

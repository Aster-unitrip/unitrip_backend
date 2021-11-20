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
            $table->string('name', 100)->comment('名稱');
            $table->string('website', 100)->nullable()->comment('網站');
            $table->char('tel', 100)->nullable()->comment('電話');
            $table->string('historic_level', 6)->comment('古蹟等級');
            $table->string('org_id', 20)->comment('管理機關單位');
            $table->string('category', 10)->comment('地址');
            $table->json('categories')->comment('景點分類');
            $table->string('address_city')->index()->comment('縣市');
            $table->string('address_town')->index()->comment('鄉鎮市區');
            $table->string('address')->comment('地址');
            $table->double('lng')->nullable()->comment('經度');
            $table->double('lat')->nullable()->comment('緯度');
            $table->json('bussiness_time')->comment('營業時間');
            $table->integer('stay_time')->nullable()->comment('建議停留時間');
            // 圖片另存一張表多對多
            $table->string("intro_summary", 150)->nullable()->comment('簡介');
            $table->string("description", 300)->nullable()->comment('詳細介紹');
            $table->json("ticket")->comment('票價');
            $table->string('memo', 4096)->nullable()->comment('備註');
            $table->string('parking', 500)->nullable()->comment('停車資訊');
            $table->string('attention', 500)->nullable()->comment('警告及注意事項');
            $table->text('experience')->nullable()->comment('合作經驗');
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

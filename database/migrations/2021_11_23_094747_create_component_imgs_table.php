<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComponentImgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('component_imgs', function (Blueprint $table) {
            $table->id();
            $table->char('component_type', 20)->nullable()->index()->comment('元件分類');
            $table->integer('component_id')->comment('元件ID');
            $table->integer('img_id')->comment('圖片ID');
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
        Schema::dropIfExists('component_imgs');
    }
}

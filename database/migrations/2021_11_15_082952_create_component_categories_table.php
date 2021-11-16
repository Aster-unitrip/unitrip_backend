<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComponentCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('component_categories', function (Blueprint $table) {
            $table->id();
            // $table->unsignedTinyInteger('list_id')->comment('1 景點 2 餐廳 3 飯店 4 體驗 5 交通工具 6 導遊 7 領隊 8 行程');
            $table->string('parent_category', 10)->comment('上層分類');
            $table->string('category_name', 32)->comment('分類名稱');
            $table->TinyInteger('sort')->nullable()->default(0)->comment('排序 由大到小');
            $table->timestamps();
        });
        DB::table('component_categories')->insert(
            [
                ['parent_category' => '景點', 'category_name' => '文化類'],
                ['parent_category' => '景點', 'category_name' => '生態類'],
                ['parent_category' => '景點', 'category_name' => '古蹟類'],
                ['parent_category' => '景點', 'category_name' => '廟宇類'],
                ['parent_category' => '景點', 'category_name' => '藝術類'],
                ['parent_category' => '景點', 'category_name' => '小吃/特產類'],
                ['parent_category' => '景點', 'category_name' => '國家公園類'],
                ['parent_category' => '景點', 'category_name' => '國家風景區類'],
                ['parent_category' => '景點', 'category_name' => '休閒農業類'],
                ['parent_category' => '景點', 'category_name' => '溫泉類'],
                ['parent_category' => '景點', 'category_name' => '自然風景類'],
                ['parent_category' => '景點', 'category_name' => '遊憩類'],
                ['parent_category' => '景點', 'category_name' => '體育健身類'],
                ['parent_category' => '景點', 'category_name' => '觀光工廠類'],
                ['parent_category' => '景點', 'category_name' => '都會公園類'],
                ['parent_category' => '景點', 'category_name' => '森林遊樂區類'],
                ['parent_category' => '景點', 'category_name' => '林場類'],
                ['parent_category' => '景點', 'category_name' => '其他'],
                ['parent_category' => '餐廳', 'category_name' => '異國料理'],
                ['parent_category' => '餐廳', 'category_name' => '火烤料理'],
                ['parent_category' => '餐廳', 'category_name' => '中式美食'],
                ['parent_category' => '餐廳', 'category_name' => '夜市小吃'],
                ['parent_category' => '餐廳', 'category_name' => '甜點冰品'],
                ['parent_category' => '餐廳', 'category_name' => '伴手禮'],
                ['parent_category' => '餐廳', 'category_name' => '地方特產'],
                ['parent_category' => '餐廳', 'category_name' => '素食'],
                ['parent_category' => '餐廳', 'category_name' => '其他'],
                ['parent_category' => '飯店', 'category_name' => '國際觀光旅館'],
                ['parent_category' => '飯店', 'category_name' => '一般觀光旅館'],
                ['parent_category' => '飯店', 'category_name' => '一般旅館'],
                ['parent_category' => '飯店', 'category_name' => '民宿'],
                ['parent_category' => '體驗', 'category_name' => 'DIY課程'],
                ['parent_category' => '體驗', 'category_name' => '主題樂園'],
                ['parent_category' => '體驗', 'category_name' => '刺激冒險'],
                ['parent_category' => '體驗', 'category_name' => '博物館&展覽'],
                ['parent_category' => '體驗', 'category_name' => '城市觀光'],
                ['parent_category' => '體驗', 'category_name' => '戶外休閒'],
                ['parent_category' => '體驗', 'category_name' => '文化節慶'],
                ['parent_category' => '體驗', 'category_name' => '文史藝術'],
                ['parent_category' => '體驗', 'category_name' => '水上活動'],
                ['parent_category' => '體驗', 'category_name' => '特色表演'],
                ['parent_category' => '體驗', 'category_name' => '當地美食'],
                ['parent_category' => '體驗', 'category_name' => '自然生態'],
                ['parent_category' => '體驗', 'category_name' => '豪華遊輪'],
                ['parent_category' => '體驗', 'category_name' => '身心療癒'],
                ['parent_category' => '體驗', 'category_name' => '運動賽事'],
                ['parent_category' => '體驗', 'category_name' => '露營'],
                ['parent_category' => '體驗', 'category_name' => '其他'],
                ['parent_category' => '交通工具', 'category_name' => '小客車'],
                ['parent_category' => '交通工具', 'category_name' => '休旅車'],
                ['parent_category' => '交通工具', 'category_name' => '7-9人座'],
                ['parent_category' => '交通工具', 'category_name' => '小巴'],
                ['parent_category' => '交通工具', 'category_name' => '遊覽車'],
                ['parent_category' => '交通工具', 'category_name' => '高鐵'],
                ['parent_category' => '交通工具', 'category_name' => '台鐵'],
                ['parent_category' => '導遊', 'category_name' => '一日遊'],
                ['parent_category' => '導遊', 'category_name' => '兩日以上'],
                ['parent_category' => '導遊', 'category_name' => '6人以內小團'],
                ['parent_category' => '導遊', 'category_name' => '7-19中型團'],
                ['parent_category' => '導遊', 'category_name' => '20人以上大型團'],
                ['parent_category' => '導遊', 'category_name' => '登山(合格嚮導證)'],
                ['parent_category' => '導遊', 'category_name' => '購物團'],
                ['parent_category' => '導遊', 'category_name' => '台灣北部'],
                ['parent_category' => '導遊', 'category_name' => '台灣中部'],
                ['parent_category' => '導遊', 'category_name' => '台灣東部'],
                ['parent_category' => '導遊', 'category_name' => '台灣南部'],
                ['parent_category' => '導遊', 'category_name' => '台灣西部'],
                ['parent_category' => '導遊', 'category_name' => '學生團'],
                ['parent_category' => '導遊', 'category_name' => '企業團'],
                ['parent_category' => '導遊', 'category_name' => '高端團'],
                ['parent_category' => '領隊', 'category_name' => '一日遊'],
                ['parent_category' => '領隊', 'category_name' => '兩日以上'],
                ['parent_category' => '領隊', 'category_name' => '6人以內小團'],
                ['parent_category' => '領隊', 'category_name' => '7-19中型團'],
                ['parent_category' => '領隊', 'category_name' => '20人以上大型團'],
                ['parent_category' => '領隊', 'category_name' => '登山(合格嚮導證)'],
                ['parent_category' => '領隊', 'category_name' => '購物團'],
                ['parent_category' => '領隊', 'category_name' => '台灣北部'],
                ['parent_category' => '領隊', 'category_name' => '台灣中部'],
                ['parent_category' => '領隊', 'category_name' => '台灣東部'],
                ['parent_category' => '領隊', 'category_name' => '台灣南部'],
                ['parent_category' => '領隊', 'category_name' => '台灣西部'],
                ['parent_category' => '領隊', 'category_name' => '學生團'],
                ['parent_category' => '領隊', 'category_name' => '企業團'],
                ['parent_category' => '領隊', 'category_name' => '高端團'],
                ['parent_category' => '語言', 'category_name' => '中文'],
                ['parent_category' => '語言', 'category_name' => '英文'],
                ['parent_category' => '語言', 'category_name' => '日文'],
                ['parent_category' => '語言', 'category_name' => '韓文'],
                ['parent_category' => '語言', 'category_name' => '西班牙文'],
                ['parent_category' => '語言', 'category_name' => '法文'],
                ['parent_category' => '語言', 'category_name' => '義大利文'],
                ['parent_category' => '語言', 'category_name' => '德文'],
                ['parent_category' => '語言', 'category_name' => '俄文'],
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('component_categories');
    }
}

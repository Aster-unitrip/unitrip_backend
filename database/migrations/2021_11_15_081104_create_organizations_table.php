<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->char('organ_code', 10)->comment('機關代碼');
            $table->string('organ_name', 32)->comment('機關名稱');
            $table->timestamps();
        });
        DB::table('organizations')->insert(
            [
                ['organ_name' => '交通部觀光局', 'organ_code' => '315080000H'],
                ['organ_name' => '馬祖國家風景區管理處', 'organ_code' => '315081000H'],
                ['organ_name' => '澎湖國家風景區管理處', 'organ_code' => '315080600H'],
                ['organ_name' => '北海岸及觀音山國家風景區管理處', 'organ_code' => '315081500H'],
                ['organ_name' => '東北角暨宜蘭海岸國家風景區管理處', 'organ_code' => '315081800H'],
                ['organ_name' => '參山國家風景區管理處', 'organ_code' => '315081200H'],
                ['organ_name' => '日月潭國家風景區管理處', 'organ_code' => '315081100H'],
                ['organ_name' => '阿里山國家風景區管理處', 'organ_code' => '315081300H'],
                ['organ_name' => '西拉雅國家風景區管理處', 'organ_code' => '315081700H'],
                ['organ_name' => '雲嘉南濱海國家風景區管理處', 'organ_code' => '315081600H'],
                ['organ_name' => '茂林國家風景區管理處', 'organ_code' => '315081400H'],
                ['organ_name' => '大鵬灣國家風景區管理處', 'organ_code' => '315080900H'],
                ['organ_name' => '花東縱谷國家風景區管理處', 'organ_code' => '315080800H'],
                ['organ_name' => '東部海岸國家風景區管理處', 'organ_code' => '315080500H'],
                ['organ_name' => '太魯閣國家公園管理處', 'organ_code' => '301021100G'],
                ['organ_name' => '台江國家公園管理處', 'organ_code' => '301021400G'],
                ['organ_name' => '玉山國家公園管理處', 'organ_code' => '301020900G'],
                ['organ_name' => '海洋國家公園管理處', 'organ_code' => '301020700G'],
                ['organ_name' => '金門國家公園管理處', 'organ_code' => '301021300G'],
                ['organ_name' => '雪霸國家公園管理處', 'organ_code' => '301021200G'],
                ['organ_name' => '陽明山國家公園管理處', 'organ_code' => '301021000G'],
                ['organ_name' => '墾丁國家公園管理處', 'organ_code' => '301020800G'],
                ['organ_name' => '宜蘭縣政府(工商旅遊處)', 'organ_code' => '376420000A'],
                ['organ_name' => '基隆市政府(交通旅遊處)', 'organ_code' => '376570000A'],
                ['organ_name' => '臺北市政府(觀光傳播局)', 'organ_code' => '379000000A'],
                ['organ_name' => '新北市政府(觀光旅遊局)', 'organ_code' => '382000000A'],
                ['organ_name' => '桃園縣政府(觀光行銷局)', 'organ_code' => '376430000A'],
                ['organ_name' => '新竹縣政府(觀光旅遊處)', 'organ_code' => '376440000A'],
                ['organ_name' => '新竹市政府(觀光局)', 'organ_code' => '376580000A'],
                ['organ_name' => '苗栗縣政府(國際文化觀光局)', 'organ_code' => '376450000A'],
                ['organ_name' => '臺中市政府(觀光局)', 'organ_code' => '387000000A'],
                ['organ_name' => '南投縣政府(觀光局)', 'organ_code' => '376480000A'],
                ['organ_name' => '彰化縣政府(觀光旅遊局)', 'organ_code' => '376470000A'],
                ['organ_name' => '雲林縣政府(文化處)', 'organ_code' => '376490000A'],
                ['organ_name' => '嘉義縣政府(建設局觀光課)', 'organ_code' => '376500000A'],
                ['organ_name' => '嘉義市政府(觀光局)', 'organ_code' => '376600000A'],
                ['organ_name' => '臺南市政府(文化觀光處)', 'organ_code' => '395000000A'],
                ['organ_name' => '高雄市政府(觀光局)', 'organ_code' => '397000000A'],
                ['organ_name' => '屏東縣政府(觀光局)', 'organ_code' => '376530000A'],
                ['organ_name' => '台東縣政府(旅遊局)', 'organ_code' => '376540000A'],
                ['organ_name' => '花蓮縣政府(觀光旅遊處)', 'organ_code' => '376550000A'],
                ['organ_name' => '澎湖縣政府(觀光局)', 'organ_code' => '376560000A'],
                ['organ_name' => '福建省金門縣政府(觀光局)', 'organ_code' => '371020000A'],
                ['organ_name' => '連江縣政府(觀光局)', 'organ_code' => '371030000A'],
                ['organ_name' => '教育部', 'organ_code' => 'A09000000E'],
                ['organ_name' => '教育部體育署', 'organ_code' => 'A09010000E'],
                ['organ_name' => '工研院', 'organ_code' => '313020000G'],
                ['organ_name' => '農委會', 'organ_code' => '345000000G'],
                ['organ_name' => '營建署', 'organ_code' => '301020000G'],
                ['organ_name' => '退輔會', 'organ_code' => '331000000A'],
                ['organ_name' => '文化部', 'organ_code' => 'A25000000E'],
                ['organ_name' => '林務局', 'organ_code' => '345040000G'],
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
        Schema::dropIfExists('organizations');
    }
}

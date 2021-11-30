<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CompanyUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('company_id');
            $table->timestamps();
        });

        DB::table('company_user')->insert(
            [
                [ 
                    'user_id' => '1', 
                    'company_id' => '1',
                ],
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
        Schema::dropIfExists('company_user');
    }
}

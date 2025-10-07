<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exempted_case_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exempted_case_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained(); // user nga nag create
            $table->string('attachment')->nullable(); // para sa basis sa discount, like reosultions, etc.
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
        Schema::dropIfExists('exemption_case_histories');
    }
};

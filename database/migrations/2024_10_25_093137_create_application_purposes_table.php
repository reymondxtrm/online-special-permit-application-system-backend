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
        Schema::create('application_purposes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_permit_type_id')->nullable()->constrained();
            $table->string('name');
            $table->string('type'); //temporary, permanent
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
        Schema::dropIfExists('application_purposes');
    }
};

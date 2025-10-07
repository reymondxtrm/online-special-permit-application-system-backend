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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_type_id')->nullable()->constrained();
            $table->foreignId('gender_type_id')->nullable()->constrained();
            $table->string('business_code')->nullable();
            $table->string('control_no')->nullable();
            $table->string('business_permit')->nullable();
            $table->string('plate_no')->nullable();
            $table->boolean('with_sticker')->default(false);
            $table->string('name', 500);
            $table->string('owner', 500);
            $table->string('status', 500);
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
        Schema::dropIfExists('businesses');
    }
};

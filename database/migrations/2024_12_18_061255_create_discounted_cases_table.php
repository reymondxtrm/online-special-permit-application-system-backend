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
        Schema::create('discounted_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_permit_type_id')->nullable()->constrained();
            $table->string('name')->nullable();
            $table->string('attachment')->nullable(); // para sa basis sa discount, like reosultions, etc.
            $table->float('discount_percent')->nullable();

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
        Schema::dropIfExists('discounted_cases');
    }
};

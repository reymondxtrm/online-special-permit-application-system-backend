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
        Schema::table('special_permit_applications', function (Blueprint $table) {
            $table->string('event_date_to')->nullable()->change();
            $table->string('event_time_to')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('special_permit_applications', function (Blueprint $table) {
            $table->string('event_date_to')->nullable(false)->change();
            $table->string('event_time_to')->nullable(false)->change();
        });
    }
};

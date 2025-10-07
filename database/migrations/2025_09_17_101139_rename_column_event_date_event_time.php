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
            $table->renameColumn('event_date', 'event_date_from');
            $table->renameColumn('event_time', 'event_time_from');
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
            //
        });
    }
};

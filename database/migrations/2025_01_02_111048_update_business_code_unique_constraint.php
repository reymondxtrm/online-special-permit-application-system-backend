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
        Schema::table('businesses', function (Blueprint $table) {
            // Drop the unique constraint from the 'business_code' column
            $table->dropUnique(['business_code']);

            // Ensure the column remains nullable (if necessary)
            $table->string('business_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('businesses', function (Blueprint $table) {
            // Re-add the unique constraint
            $table->string('business_code')->unique()->nullable()->change();
        });
    }
};

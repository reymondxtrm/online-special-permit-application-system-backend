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
              $table->index('status'); 
              $table->index('created_at'); 
              $table->index('year'); 
              $table->index('business_code'); 
              $table->index('permit_type_id'); 
              $table->index('gender_type_id'); 
              $table->index('control_no'); 
              $table->index('business_permit'); 
              $table->index('plate_no'); 
              $table->index('name'); 
              $table->index('owner'); 
        });
        Schema::table('business_stages', function (Blueprint $table) {
              $table->index('business_id'); 
              $table->index('stage_id'); 
              $table->index('created_at'); 
        });
        Schema::table('permit_types', function (Blueprint $table) {
              $table->index('id'); 
        });
        Schema::table('gender_types', function (Blueprint $table) {
              $table->index('id'); 
        });
        Schema::table('stages', function (Blueprint $table) {
              $table->index('id'); 
        });
        Schema::table('users', function (Blueprint $table) {
              $table->index('id'); 
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
        $table->dropIndex(['status']);
        $table->dropIndex(['created_at']);
        // $table->dropIndex(['year']);
        $table->dropIndex(['business_code']);
        // $table->dropIndex(['permit_type']);
        $table->dropIndex(['permit_type_id']);
        $table->dropIndex(['gender_type_id']);
        $table->dropIndex(['control_no']);
        $table->dropIndex(['business_permit']);
        $table->dropIndex(['plate_no']);
        $table->dropIndex(['name']);
        $table->dropIndex(['owner']);
    });
    Schema::table('business_stages', function (Blueprint $table) {
        $table->dropIndex(['business_id']);
        $table->dropIndex(['stage_id']);
        $table->dropIndex(['created_at']);
    });
  
    }
};

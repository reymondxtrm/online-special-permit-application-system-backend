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
        Schema::create('caraga_geolocations', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (optional, remove if not needed)
            $table->integer('region_id');
            $table->integer('prov_id');
            $table->integer('mun_city_id');
            $table->integer('barangay_id');
            $table->string('psgc_id', 50);
            $table->string('location_desc', 255);
            $table->string('correspondence_code', 50)->nullable();
            $table->string('geographic_level', 50)->nullable();
            $table->text('old_names')->nullable();
            $table->string('city_class', 50)->nullable();
            $table->string('income_classification', 50)->nullable();
            $table->string('urban_rural', 50)->nullable();
            $table->timestamps(); // Adds `created_at` and `updated_at` columns (optional)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('caraga_geolocations');
    }
};

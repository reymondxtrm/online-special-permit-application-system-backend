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
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_permit_application_id')->nullable()->constrained();
            $table->string('police_clearance')->nullable();
            $table->string('community_tax_certificate')->nullable();
            $table->string('barangay_clearance')->nullable();
            $table->string('official_receipt')->nullable();
            $table->string('fiscal_clearance')->nullable();
            $table->string('court_clearance')->nullable();
            $table->string('request_letter')->nullable();
            $table->string('route_plan')->nullable();
            $table->string('certificate_of_employment')->nullable();
            $table->string('id_picture')->nullable();
            $table->string('health_certificate')->nullable();
            $table->string('training_certificate')->nullable();
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
        Schema::dropIfExists('uploaded_files');
    }
};

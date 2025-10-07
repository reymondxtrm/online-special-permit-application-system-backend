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
        Schema::create('completed_special_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_permit_application_id')->nullable()->constrained();
            $table->foreignId('applicant_id')->nullable()->constrained('users')->onDelete('cascade'); // For the applicant
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('cascade'); // For the admin
            $table->string('file');
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
        Schema::dropIfExists('completed_special_permits');
    }
};

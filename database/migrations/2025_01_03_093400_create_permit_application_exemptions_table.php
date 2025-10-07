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
        Schema::create('permit_application_exemptions', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('special_permit_application_id')->nullable()->constrained();

            $table->unsignedBigInteger('special_permit_application_id');
            $table->foreign('special_permit_application_id', 'permit_application_id')
                ->references('id')->on('special_permit_applications');
            $table->foreignId('exempted_case_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained(); //kung kinsa ang mag approve
            $table->string('attachment')->nullable(); // for proof sa client
            $table->string('status')->nullable(); // pending, approved, declined
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
        Schema::dropIfExists('permit_application_exemptions');
    }
};

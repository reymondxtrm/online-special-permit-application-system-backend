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
        Schema::create('special_permit_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('special_permit_type_id')->nullable()->constrained();
            $table->foreignId('application_purpose_id')->nullable()->constrained();
            $table->foreignId('user_address_id')->nullable()->constrained();
            $table->string('requestor_name')->nullable();
            $table->string('event_name')->nullable();
            $table->string('event_date')->nullable();
            // $table->date('date')->nullable();
            $table->string('event_time')->nullable();
            $table->foreignId('special_permit_status_id')->nullable()->constrained();
            // $table->string('surname');
            // $table->string('first_name');
            // $table->string('middle_initial')->nullable();
            // $table->string('suffix')->nullable();
            // $table->string('sex');
            // $table->string('email');
            // $table->string('contact_no');

            // $table->string('province')->nullable();
            // $table->string('city')->nullable();
            // $table->string('barangay')->nullable();
            // $table->string('additional_address')->nullable();
            // $table->string('or_no');
            // $table->string('paid_amount');
            // $table->string('name_of_employeer')->nullable();


            // $table->string('permit_type'); //mayors_permit, good_moral, event, motorcade, parade, recorrida, use_of_government_property, occupational_permit
            // $table->string('status')->default('pending'); //pendng, for_payment, paid, accomplished

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
        Schema::dropIfExists('special_permit_applications');
    }
};

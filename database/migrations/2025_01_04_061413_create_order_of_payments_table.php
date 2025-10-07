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
        Schema::create('order_of_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_permit_application_id')->nullable()->constrained();
            $table->foreignId('permit_application_exemption_id')->nullable()->constrained();
            $table->foreignId('exempted_case_id')->nullable()->constrained();
            // $table->foreignId('user_id')->nullable()->constrained(); //kinsay nag buhat sa order of payment
            $table->foreignId('applicant_id')->nullable()->constrained('users')->onDelete('cascade'); // For the applicant
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('cascade'); // For the admin
            $table->float('billed_amount');
            $table->float('exemption_amount')->default(0);
            $table->float('total_amount')->nullable(); //including discounts
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
        Schema::dropIfExists('order_of_payments');
    }
};

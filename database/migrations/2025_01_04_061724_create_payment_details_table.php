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
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->nullable()->constrained(); // kung kinsa ang nag approved or nag check sa or nga attachment sa iyang payment nga over the counter
            $table->foreignId('applicant_id')->nullable()->constrained('users')->onDelete('cascade'); // For the applicant
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('cascade'); // For the admin
            $table->foreignId('order_of_payment_id')->nullable()->constrained();
            $table->foreignId('special_permit_application_id')->nullable()->constrained();
            $table->decimal('paid_amount', 15, 2);
            $table->string('reference_no')->nullable();
            $table->string('or_no')->nullable();
            $table->string('attachment')->nullable(); //picture sa recibo in case over the counter ang bayad
            $table->string('payment_type'); //over_the_counter, online, waived (para sa kadtong naay mga exemption)
            $table->string('status'); //pending, approved, waived( para sa kadtong naay mga exemption )
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
        Schema::dropIfExists('payment_details');
    }
};

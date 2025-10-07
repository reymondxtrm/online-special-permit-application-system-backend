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
        Schema::create('permit_receivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained();
            $table->foreignId('id_type_id')->nullable()->constrained();
            $table->string('receiver_name');
            $table->string('receiver_relationship_to_owner')->nullable();
            $table->text('receiver_signature');
            $table->text('receiver_photo');
            $table->string('receiver_id_no');
            $table->string('receiver_email')->nullable();
            $table->string('receiver_phone_no');
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
        Schema::dropIfExists('permit_receivers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tron_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('address');
            $table->text('private_key');
            $table->integer('index');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('payment_id')->nullable();
            $table->enum('status', ['available', 'assigned', 'expired'])->default('available');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tron_addresses');
    }
};

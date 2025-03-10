<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('usdt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->decimal('received_amount', 20, 6)->nullable();
            $table->string('address');
            $table->string('tx_id')->nullable();
            $table->string('network')->nullable(); // bep20 | trc20 (should be contract type)
            $table->enum('status', ['pending', 'success', 'expired', 'failed'])->default('pending');
            $table->unsignedInteger('address_index')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('funds_moved')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('usdt_payments');
    }
};

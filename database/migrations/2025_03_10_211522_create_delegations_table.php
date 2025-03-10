<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->string('from_address');
            $table->string('to_address');
            $table->bigInteger('energy_amount');
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delegations');
    }
};

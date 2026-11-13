<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_bootstrap_invitations', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('email')->nullable();
            $table->char('token_hash', 64)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_bootstrap_invitations');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        Schema::create('demo_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor'); $table->string('action'); $table->string('category');
            $table->string('status')->default('Completed'); $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('demo_activity_logs');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};

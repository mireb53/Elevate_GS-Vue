<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('class_instructors')) {
            Schema::create('class_instructors', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('user_id');
                $table->string('role_in_class', 50)->default('instructor');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['class_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_instructors');
    }
};

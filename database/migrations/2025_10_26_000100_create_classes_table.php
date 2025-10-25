<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('classes')) {
            Schema::create('classes', function (Blueprint $table) {
                $table->bigIncrements('class_id');
                $table->unsignedBigInteger('user_id');
                $table->string('program')->nullable();
                $table->string('class_name');
                $table->string('section', 100);
                $table->string('subject_code', 50);
                $table->string('course_name')->nullable();
                $table->text('description')->nullable();
                $table->string('status', 50)->default('active');
                $table->string('class_code', 50)->unique();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};

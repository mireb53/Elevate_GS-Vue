<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('classwork')) {
            Schema::create('classwork', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('class_id');
                $table->string('title');
                $table->string('type', 50)->default('Material');
                $table->text('description')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->longText('rubric_json')->nullable();
                $table->longText('extra_json')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('classwork_submissions')) {
            Schema::create('classwork_submissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('classwork_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('submitted_at')->nullable();
                $table->longText('files_json')->nullable();
                $table->longText('answers_json')->nullable();
                $table->longText('grade_json')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('classwork_submissions');
        Schema::dropIfExists('classwork');
    }
};

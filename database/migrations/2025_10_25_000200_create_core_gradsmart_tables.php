<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('classes')) {
            Schema::create('classes', function (Blueprint $table) {
                $table->increments('class_id');
                $table->string('class_name');
                $table->string('section', 100);
                $table->string('subject_code', 50);
                $table->string('school_year', 20)->nullable();
                $table->string('semester', 50)->nullable();
                $table->string('program')->nullable();
                $table->string('course_type', 50)->nullable();
                $table->string('course_name')->nullable();
                $table->integer('units')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->unsignedBigInteger('user_id');
                $table->string('class_code', 50)->nullable();
                $table->string('status', 20)->default('active');
                $table->string('excel_file_path', 500)->nullable();
                $table->string('term_status', 50)->default('midterm_ongoing');
                $table->json('grading_formula')->nullable();
                $table->string('midterm_excel_path', 255)->nullable();
                $table->enum('midterm_status', ['ongoing','completed','locked'])->nullable();
                $table->timestamp('midterm_completed_at')->nullable();
                $table->string('final_excel_path', 255)->nullable();
                $table->enum('final_status', ['not_started','ongoing','completed','locked'])->default('not_started');
                $table->timestamp('final_completed_at')->nullable();
                $table->json('grading_weights')->nullable();
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('class_instructors')) {
            Schema::create('class_instructors', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('class_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('role_in_class', ['owner','instructor','ta'])->default('instructor');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['class_id','user_id']);
                $table->index('class_id');
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('joined_classes')) {
            Schema::create('joined_classes', function (Blueprint $table) {
                $table->increments('joined_id');
                $table->unsignedBigInteger('user_id');
                $table->integer('class_id');
                $table->string('program', 100)->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamp('joined_at')->useCurrent();
                $table->unique(['user_id','class_id']);
                $table->index('user_id');
                $table->index('class_id');
            });
        }

        if (!Schema::hasTable('classwork_items')) {
            Schema::create('classwork_items', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('class_id');
                $table->string('type', 32);
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('due_at')->nullable();
                $table->json('rubric_json')->nullable();
                $table->json('extra_json')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
                $table->integer('points_possible')->nullable();
                $table->enum('term_type', ['midterm','final'])->default('midterm');
                $table->integer('category_id')->nullable();
                $table->index('class_id');
                $table->index('type');
                $table->index(['class_id','term_type']);
            });
        }

        if (!Schema::hasTable('classwork_submissions')) {
            Schema::create('classwork_submissions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('classwork_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('submitted_at')->useCurrent();
                $table->json('grade_json')->nullable();
                $table->json('extra_json')->nullable();
                $table->index('classwork_id');
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('user_id');
                $table->integer('class_id')->nullable();
                $table->integer('classwork_id')->nullable();
                $table->string('type', 50);
                $table->string('title', 255)->nullable();
                $table->text('message');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('read_at')->nullable();
                $table->index('user_id');
                $table->index('class_id');
                $table->index('classwork_id');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'notifications',
            'classwork_submissions',
            'classwork_items',
            'joined_classes',
            'class_instructors',
            'classes',
        ] as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
    }
};

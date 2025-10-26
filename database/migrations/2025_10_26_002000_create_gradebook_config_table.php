<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gradebook_config')) {
            Schema::create('gradebook_config', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('class_id');
                $table->integer('midterm_percentage')->default(50);
                $table->integer('finals_percentage')->default(50);
                $table->longText('midterm_tables')->nullable();
                $table->longText('finals_tables')->nullable();
                $table->longText('grades')->nullable();
                $table->timestamps();
                
                $table->unique('class_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gradebook_config');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classwork_submissions') && !Schema::hasColumn('classwork_submissions','answers_json')) {
            $hasFilesJson = Schema::hasColumn('classwork_submissions','files_json');
            if ($hasFilesJson) {
                Schema::table('classwork_submissions', function (Blueprint $table) {
                    $table->longText('answers_json')->nullable()->after('files_json');
                });
            } else {
                Schema::table('classwork_submissions', function (Blueprint $table) {
                    $table->longText('answers_json')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('classwork_submissions') && Schema::hasColumn('classwork_submissions','answers_json')) {
            Schema::table('classwork_submissions', function (Blueprint $table) {
                $table->dropColumn('answers_json');
            });
        }
    }
};

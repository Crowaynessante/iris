<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $t) {
                $t->increments('id');
                $t->string('username', 50)->unique();
                $t->string('email', 100)->unique();
                $t->string('password', 255);
                $t->enum('role', ['admin', 'user'])->default('user');
                $t->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('ranking_bodies')) {
            Schema::create('ranking_bodies', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name', 100);
                $t->string('short_name', 20)->unique();
            });
        }

        if (!Schema::hasTable('rankings')) {
            Schema::create('rankings', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('ranking_body_id');
                $t->integer('year');
                $t->string('category', 100)->nullable();
                $t->string('global_rank', 50)->nullable();
                $t->integer('rank_value')->nullable();
                $t->string('ph_rank', 50)->nullable();
                $t->integer('ph_rank_value')->nullable();
                $t->string('note', 255)->nullable();
                $t->foreign('ranking_body_id')->references('id')->on('ranking_bodies')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('ranking_breakdowns')) {
            Schema::create('ranking_breakdowns', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('ranking_body_id');
                $t->integer('year');
                $t->string('group_label', 100)->nullable();
                $t->string('item_label', 200);
                $t->string('rank_display', 50)->nullable();
                $t->integer('rank_value')->nullable();
                $t->string('note', 255)->nullable();
                $t->foreign('ranking_body_id')->references('id')->on('ranking_bodies')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('colleges')) {
            Schema::create('colleges', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name', 150);
                $t->string('short_code', 20);
                $t->decimal('contribution_percent', 5, 2);
                $t->integer('year');
            });
        }

        if (!Schema::hasTable('programs')) {
            Schema::create('programs', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name', 150);
                $t->unsignedInteger('college_id');
                $t->integer('national_rank');
                $t->decimal('score', 5, 2);
                $t->integer('movement')->default(0);
                $t->integer('year');
                $t->foreign('college_id')->references('id')->on('colleges')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('accreditations')) {
            Schema::create('accreditations', function (Blueprint $t) {
                $t->increments('id');
                $t->string('program_name', 150);
                $t->string('accrediting_body', 100)->default('AUN-QA');
                $t->integer('year');
                $t->string('assessment_date', 100)->nullable();
                $t->string('criterion', 150);
                $t->string('score', 50)->nullable();
                $t->decimal('numeric_score', 4, 2)->nullable();
            });
        }

        if (!Schema::hasTable('uploads_log')) {
            Schema::create('uploads_log', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('uploaded_by');
                $t->string('filename', 255);
                $t->string('file_type', 10)->nullable();
                $t->string('upload_type', 50);
                $t->integer('rows_inserted')->default(0);
                $t->timestamp('uploaded_at')->useCurrent();
                $t->foreign('uploaded_by')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        foreach (['uploads_log','accreditations','programs','colleges','ranking_breakdowns','rankings','ranking_bodies','users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

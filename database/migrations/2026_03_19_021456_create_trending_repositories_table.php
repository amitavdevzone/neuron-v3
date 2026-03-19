<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trending_repositories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->unsignedBigInteger('github_id')->unique();
            $table->string('name');
            $table->string('full_name')->unique();
            $table->string('owner');
            $table->text('description')->nullable();
            $table->string('language')->nullable();
            $table->unsignedInteger('stars_count');
            $table->unsignedInteger('forks_count');
            $table->unsignedInteger('open_issues_count');
            $table->string('html_url');
            $table->timestamp('github_created_at');
            $table->timestamp('fetched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trending_repositories');
    }
};

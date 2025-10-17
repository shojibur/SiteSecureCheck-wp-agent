<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('domain');
            $table->string('wp_api_base');
            $table->text('wp_api_key');
            $table->string('region_mode')->default('OTHER');
            $table->boolean('auto_fix')->default(true);
            $table->string('teams_webhook')->nullable();
            $table->string('email')->nullable();
            $table->integer('last_score')->nullable();
            $table->timestamps();
        });

        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('site_id')->index();
            $table->string('status')->default('queued');
            $table->integer('score')->nullable();
            $table->json('issues')->nullable();
            $table->json('plan')->nullable();
            $table->boolean('applied')->default(false);
            $table->json('raw')->nullable();
            $table->timestamps();
        });

        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('site_id');
            $table->unsignedBigInteger('scan_id')->nullable();
            $table->string('type');
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actions');
        Schema::dropIfExists('scans');
        Schema::dropIfExists('sites');
    }
};


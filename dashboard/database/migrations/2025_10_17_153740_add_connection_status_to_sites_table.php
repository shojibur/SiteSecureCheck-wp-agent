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
        Schema::table('sites', function (Blueprint $table) {
            $table->string('connection_status')->default('unknown')->after('last_score'); // unknown, connected, error
            $table->timestamp('connection_checked_at')->nullable()->after('connection_status');
            $table->text('connection_error')->nullable()->after('connection_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['connection_status', 'connection_checked_at', 'connection_error']);
        });
    }
};

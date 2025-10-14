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
        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropForeign(['template']);
            $table->unsignedBigInteger('template')->nullable()->change();
            $table->foreign('template')->references('id')->on('email_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropForeign(['template']);
            $table->unsignedBigInteger('template')->nullable(false)->change();
            $table->foreign('template')->references('id')->on('email_templates')->onDelete('cascade');
        });
    }
};

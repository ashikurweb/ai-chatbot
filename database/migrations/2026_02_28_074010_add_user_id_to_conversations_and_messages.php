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
        // This migration used to add a `user_id` column to conversations and
        // messages. Both tables now include the column in their create
        // migrations, so there is nothing to do here. Keeping the file around
        // prevents Laravel from re‑running earlier migrations that would break.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op; the column is defined in the original table creations
    }
};

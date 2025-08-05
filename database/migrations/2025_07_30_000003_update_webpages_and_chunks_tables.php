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
        // Add missing columns to webpages table
        Schema::table('webpages', function (Blueprint $table) {
            $table->string('status')->default('processing')->after('title'); // processing, completed, failed
            $table->text('error_message')->nullable()->after('status');
        });

        // Drop old webpage_chunks table
        Schema::dropIfExists('webpage_chunks');

        // Recreate webpage_chunks table with correct structure
        Schema::create('webpage_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('webpage_id'); // References webpages.webpage_id
            $table->text('chunk');
            $table->json('embedding')->nullable();
            $table->timestamps();

            $table->foreign('webpage_id')
                  ->references('webpage_id')
                  ->on('webpages')
                  ->onDelete('cascade');

            $table->index('webpage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the added columns
        Schema::table('webpages', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message']);
        });

        // Drop new webpage_chunks table
        Schema::dropIfExists('webpage_chunks');

        // Recreate original webpage_chunks table
        Schema::create('webpage_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webpages_id');
            $table->text('chunk');
            $table->text('embedding')->nullable();
            $table->timestamps();
            $table->foreign('webpages_id')->references('id')->on('webpages')->onDelete('cascade');
            $table->index('webpages_id');
        });
    }
};

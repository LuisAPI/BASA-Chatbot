<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webpage_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webpages_id'); // Foreign key to webpages table
            $table->text('chunk');
            $table->text('embedding')->nullable();
            $table->timestamps();
            $table->foreign('webpages_id')->references('id')->on('webpages')->onDelete('cascade');
            $table->index('webpages_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webpage_chunks');
    }
};

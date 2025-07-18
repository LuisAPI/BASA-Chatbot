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
        Schema::create('webpages', function (Blueprint $table) {
            $table->id();
            $table->string('webpage_id')->unique(); // md5 hash of URL
            $table->string('url')->unique();
            $table->string('title')->nullable();
            $table->timestamps();
        });
        Schema::table('webpage_chunks', function (Blueprint $table) {
            $table->unsignedBigInteger('webpages_id')->nullable()->after('webpage_id');
            $table->foreign('webpages_id')->references('id')->on('webpages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webpage_chunks', function (Blueprint $table) {
            $table->dropForeign(['webpages_id']);
            $table->dropColumn('webpages_id');
        });
        Schema::dropIfExists('webpages');
    }
};

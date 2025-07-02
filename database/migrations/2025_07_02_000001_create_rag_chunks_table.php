<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('rag_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('source')->nullable(); // file name or doc id
            $table->text('chunk');
            $table->json('embedding');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rag_chunks');
    }
};

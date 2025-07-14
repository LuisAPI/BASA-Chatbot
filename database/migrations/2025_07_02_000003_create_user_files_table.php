<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('original_name'); // Original filename
            $table->string('storage_path'); // Path in storage
            $table->bigInteger('file_size')->default(0);
            $table->string('file_type')->nullable();
            $table->boolean('is_public')->default(false);
            $table->json('shared_with_users')->nullable(); // Array of user IDs
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'processing_status']);
            $table->index(['is_public', 'processing_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_files');
    }
}; 
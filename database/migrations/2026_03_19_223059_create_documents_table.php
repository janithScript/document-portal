<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('uploader_name');
            $table->string('original_path');          // stored file path
            $table->string('signed_path')->nullable(); // after signing
            $table->integer('page_count')->default(1);
            $table->enum('status', ['uploaded','signing','signed'])->default('uploaded');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

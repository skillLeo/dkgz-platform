<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Proof of qualification and of professional liability cover.
 *
 * Previously a single path on the assessors row, which could hold only one file
 * and was never downloadable — an administrator approving a partner could see
 * that a document existed but not what it said. Approval is the one decision on
 * this platform that needs the evidence in front of it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessor_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['qualification', 'liability', 'other'])->default('other');
            $table->string('path');
            $table->string('original_name');
            $table->unsignedInteger('size_bytes');
            $table->string('mime_type', 120);
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['assessor_id', 'type']);
        });

        // Carry the existing single file over so nothing already submitted is
        // lost; the column stays for now and is dropped once nothing reads it.
        DB::table('assessors')
            ->whereNotNull('qualification_document_path')
            ->orderBy('id')
            ->chunkById(100, function ($assessors) {
                foreach ($assessors as $assessor) {
                    DB::table('assessor_documents')->insert([
                        'assessor_id' => $assessor->id,
                        'type' => 'qualification',
                        'path' => $assessor->qualification_document_path,
                        'original_name' => basename($assessor->qualification_document_path),
                        'size_bytes' => 0,
                        'mime_type' => 'application/octet-stream',
                        'uploaded_at' => $assessor->created_at ?? now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessor_documents');
    }
};

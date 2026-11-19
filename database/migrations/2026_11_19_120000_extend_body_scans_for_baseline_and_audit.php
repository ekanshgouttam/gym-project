<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scan rules: baseline at joining, strict 30-day interval, untouched originals,
 * admin corrections with an immutable audit trail, and cached AI comparisons.
 */
class ExtendBodyScansForBaselineAndAudit extends Migration
{
    public function up()
    {
        Schema::table('body_scans', function (Blueprint $table) {
            $table->boolean('is_baseline')->default(false)->after('user_id');
            $table->decimal('height_cm', 5, 1)->nullable()->after('weight_kg');
            // Untouched uploads: {front: {path, sha256, bytes, mime}, ...}. Display copies live in *_path.
            $table->json('originals')->nullable()->after('back_path');
            // Server-side computer-vision checks per photo: brightness, contrast, sharpness, size, warnings.
            $table->json('quality')->nullable()->after('originals');
            $table->unsignedTinyInteger('analysis_attempts')->default(0)->after('status');
            $table->timestamp('voided_at')->nullable()->index();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->index(['user_id', 'created_at']);
        });

        // Admin-granted permission to upload before the 30-day window ends (one use).
        Schema::create('body_scan_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_for_scan_id')->nullable()->constrained('body_scans')->nullOnDelete();
            $table->timestamps();
        });

        // Append-only record of every upload and correction.
        Schema::create('scan_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('body_scan_id')->nullable()->constrained('body_scans')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('reason')->nullable();
            $table->json('changes')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['member_id', 'created_at']);
        });

        Schema::create('scan_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_scan_id')->constrained('body_scans')->cascadeOnDelete();
            $table->foreignId('to_scan_id')->constrained('body_scans')->cascadeOnDelete();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('summary')->nullable();
            $table->json('analysis')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();
            $table->unique(['from_scan_id', 'to_scan_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('scan_comparisons');
        Schema::dropIfExists('scan_audit_logs');
        Schema::dropIfExists('body_scan_overrides');
        Schema::table('body_scans', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['is_baseline', 'height_cm', 'originals', 'quality', 'analysis_attempts', 'voided_at', 'void_reason']);
        });
    }
}

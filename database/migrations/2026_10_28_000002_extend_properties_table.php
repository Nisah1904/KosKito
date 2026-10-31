<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->text('address')->nullable()->after('location');
            $table->foreignId('campus_id')->nullable()->after('address')->constrained('campuses')->nullOnDelete();
            $table->text('rules')->nullable();
            $table->boolean('electricity_included')->default(false);
            $table->boolean('internet_included')->default(false);
            $table->unsignedInteger('electricity_fee')->default(0);
            $table->unsignedInteger('internet_fee')->default(0);
            $table->string('phone')->nullable();
            $table->string('source')->default('owner')->index();
            $table->string('source_url')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamp('data_verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'address', 'campus_id', 'rules', 'electricity_included', 'internet_included',
                'electricity_fee', 'internet_fee', 'phone', 'source', 'source_url',
                'is_demo', 'data_verified_at', 'verified_by', 'rejection_reason',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->text('rejection_reason')->nullable()->after('note');
            $table->timestamp('responded_at')->nullable()->after('rejection_reason');
            $table->timestamp('cancelled_at')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['code', 'rejection_reason', 'responded_at', 'cancelled_at']);
        });
    }
};

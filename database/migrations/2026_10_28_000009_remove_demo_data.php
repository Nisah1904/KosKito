<?php

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $demoIds = Property::where('is_demo', true)->pluck('id');

        foreach (DB::table('property_photos')->whereIn('property_id', $demoIds)->pluck('path') as $path) {
            if (!str_starts_with($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }

        DB::table('property_photos')->whereIn('property_id', $demoIds)->delete();
        DB::table('favorites')->whereIn('property_id', $demoIds)->delete();
        DB::table('bookings')->whereIn('property_id', $demoIds)->delete();
        Property::whereIn('id', $demoIds)->delete();

        $demoUsers = User::whereIn('email', ['pemilik@kostly', 'penyewa@kostly'])->pluck('id');
        DB::table('favorites')->whereIn('user_id', $demoUsers)->delete();
        DB::table('bookings')->whereIn('tenant_id', $demoUsers)->delete();
        DB::table('ai_conversations')->whereIn('user_id', $demoUsers)->delete();
        DB::table('sessions')->whereIn('user_id', $demoUsers)->delete();
        Property::whereIn('owner_id', $demoUsers)->update(['owner_id' => User::where('role', 'admin')->value('id')]);
        User::whereIn('id', $demoUsers)->delete();
    }

    public function down(): void
    {
    }
};

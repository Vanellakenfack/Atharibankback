<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        $agencyId = 3;

        // abort if agency 9 doesn't exist
        if (! DB::table('agencies')->where('id', $agencyId)->exists()) {
            return;
        }

        $now = Carbon::now();
        $userIds = DB::table('users')->pluck('id');

        $inserts = [];

        foreach ($userIds as $userId) {
            $exists = DB::table('agency_user')
                ->where('user_id', $userId)
                ->where('agency_id', $agencyId)
                ->exists();

            if (! $exists) {
                $inserts[] = [
                    'user_id'    => $userId,
                    'agency_id'  => $agencyId,
                    'is_primary' => false,
                    'assigned_at'=> $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($inserts)) {
            DB::table('agency_user')->insert($inserts);
        }
    }

    public function down(): void
    {
        DB::table('agency_user')->where('agency_id', 9)->delete();
    }
};

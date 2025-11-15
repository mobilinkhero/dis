<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if firstname and lastname columns exist, if not add them
        if (!Schema::hasColumn('users', 'firstname')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('firstname')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('users', 'lastname')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('lastname')->nullable()->after('firstname');
            });
        }

        // Update any existing users with null firstname/lastname
        DB::table('users')
            ->whereNull('firstname')
            ->orWhereNull('lastname')
            ->get()
            ->each(function ($user) {
                $nameParts = explode(' ', $user->name ?? $user->email ?? 'User', 2);
                
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'firstname' => $nameParts[0] ?? 'User',
                        'lastname' => $nameParts[1] ?? ''
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop these columns as they might contain important data
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn(['firstname', 'lastname']);
        // });
    }
};

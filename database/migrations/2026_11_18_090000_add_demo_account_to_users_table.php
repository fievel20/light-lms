<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The demo account of a promotion: a learner account the trainer hands
     * out to visitors, emptied at each logout.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('demo_promotion_id')->nullable()->unique()->after('current_promotion_id')
                ->constrained('promotions')->cascadeOnDelete();
            // Kept (encrypted) so the trainer can read it again.
            $table->text('demo_password')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('demo_promotion_id');
            $table->dropColumn('demo_password');
        });
    }
};

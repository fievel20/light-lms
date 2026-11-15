<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each promotion orders its paths. Existing assignments keep the
     * alphabetical order they were shown in so far.
     */
    public function up(): void
    {
        Schema::table('learning_path_promotion', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('learning_path_id');
        });

        $rows = DB::table('learning_path_promotion')
            ->join('learning_paths', 'learning_paths.id', '=', 'learning_path_promotion.learning_path_id')
            ->orderBy('learning_path_promotion.promotion_id')
            ->orderBy('learning_paths.name')
            ->get(['learning_path_promotion.promotion_id', 'learning_path_promotion.learning_path_id']);

        foreach ($rows->groupBy('promotion_id') as $promotionRows) {
            foreach ($promotionRows->values() as $index => $row) {
                DB::table('learning_path_promotion')
                    ->where('promotion_id', $row->promotion_id)
                    ->where('learning_path_id', $row->learning_path_id)
                    ->update(['position' => $index + 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_path_promotion', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};

<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('attachmentables', static function (Blueprint $table): void {
            $table->dropPrimary();
        });

        DB::statement('ALTER TABLE attachmentables ALTER COLUMN id DROP DEFAULT');

        Schema::table('attachmentables', static function (Blueprint $table): void {
            $table->primary('id');
        });
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('attachmentables', static function (Blueprint $table): void {
            $table->dropPrimary();
        });

        DB::statement('ALTER TABLE attachmentables ALTER COLUMN id SET DEFAULT gen_random_uuid()');

        Schema::table('attachmentables', static function (Blueprint $table): void {
            $table->primary('id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->decimal('quantity', 10, 2);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // who moved it
            $table->dateTime('moved_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['org_id', 'item_id']);
            $table->index('from_location_id');
            $table->index('to_location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_movements');
    }
};

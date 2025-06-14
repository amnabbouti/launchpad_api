<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entity_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->string('entity_type', 50);
            $table->string('entity_prefix', 10);
            $table->unsignedBigInteger('sequence_number');
            $table->string('public_id', 50)->storedAs("CONCAT(entity_prefix, '-', LPAD(sequence_number, 8, '0'))");
            $table->unsignedBigInteger('entity_internal_id');
            $table->timestamps();
            $table->unique(['org_id', 'entity_type', 'sequence_number'], 'entity_ids_unique_sequence');
            $table->unique(['org_id', 'public_id'], 'entity_ids_unique_public');
            $table->unique(['org_id', 'entity_type', 'entity_internal_id'], 'entity_ids_unique_internal');
            $table->index(['org_id', 'entity_type']);
            $table->index('public_id');
        });

        $entityTypes = [
            'item' => ['table' => 'items', 'prefix' => 'ITM'],
            'stock' => ['table' => 'stocks', 'prefix' => 'BCH'],
            'maintenance' => ['table' => 'maintenances', 'prefix' => 'MNT'],
            'check_in_out' => ['table' => 'check_ins_outs', 'prefix' => 'TXN'],
            'category' => ['table' => 'categories', 'prefix' => 'CAT'],
            'supplier' => ['table' => 'suppliers', 'prefix' => 'SUP'],
            'location' => ['table' => 'locations', 'prefix' => 'LOC'],
            'user' => ['table' => 'users', 'prefix' => 'USR'],
        ];

        foreach ($entityTypes as $entityType => $config) {
            if (! Schema::hasTable($config['table'])) {
                continue;
            }

            $orgs = DB::table('organizations')->pluck('id');

            foreach ($orgs as $orgId) {
                DB::transaction(function () use ($orgId, $entityType, $config) {
                    $records = DB::table($config['table'])
                        ->where('org_id', $orgId)
                        ->orderBy('id')
                        ->lockForUpdate() 
                        ->get();

                    $batchData = [];
                    foreach ($records as $index => $record) {
                        $batchData[] = [
                            'org_id' => $orgId,
                            'entity_type' => $entityType,
                            'entity_prefix' => $config['prefix'],
                            'sequence_number' => $index + 1,
                            'entity_internal_id' => $record->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (! empty($batchData)) {
                        DB::table('entity_ids')->insert($batchData);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_ids');
    }
};

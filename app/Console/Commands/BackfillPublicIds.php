<?php

namespace App\Console\Commands;

use App\Models\EntityId;
use App\Services\EntityIdService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPublicIds extends Command
{
    protected $signature = 'public-id:backfill {model? : The model to backfill (e.g., item, location, supplier)}';
    protected $description = 'Backfill public IDs for existing records that don\'t have them';

    protected EntityIdService $entityIdService;

    public function __construct(EntityIdService $entityIdService)
    {
        parent::__construct();
        $this->entityIdService = $entityIdService;
    }

    public function handle()
    {
        $modelType = $this->argument('model');
        
        if ($modelType) {
            $this->backfillSingleModel($modelType);
        } else {
            $this->backfillAllModels();
        }
    }

    protected function backfillAllModels()
    {
        $models = [
            'item' => \App\Models\Item::class,
            'location' => \App\Models\Location::class,
            'supplier' => \App\Models\Supplier::class,
            'category' => \App\Models\Category::class,
            'maintenance' => \App\Models\Maintenance::class,
            'stock' => \App\Models\Stock::class,
            'check_in_out' => \App\Models\CheckInOut::class,
            'item_status' => \App\Models\ItemStatus::class,
            'organization' => \App\Models\Organization::class,
            'role' => \App\Models\Role::class,
            'unit_of_measure' => \App\Models\UnitOfMeasure::class,
            'item_supplier' => \App\Models\ItemSupplier::class,
            'item_location' => \App\Models\ItemLocation::class,
            'maintenance_category' => \App\Models\MaintenanceCategory::class,
            'maintenance_condition' => \App\Models\MaintenanceCondition::class,
            'maintenance_detail' => \App\Models\MaintenanceDetail::class,
            'attachment' => \App\Models\Attachment::class,
        ];

        foreach ($models as $type => $class) {
            $this->info("Backfilling {$type}...");
            $this->backfillModel($class, $type);
        }
    }

    protected function backfillSingleModel(string $modelType)
    {
        $models = [
            'item' => \App\Models\Item::class,
            'location' => \App\Models\Location::class,
            'supplier' => \App\Models\Supplier::class,
            'category' => \App\Models\Category::class,
            'maintenance' => \App\Models\Maintenance::class,
            // 'stock_item' => \App\Models\StockItem::class, // Removed as we now use the consolidated Item model
            'stock' => \App\Models\Stock::class,
            'check_in_out' => \App\Models\CheckInOut::class,
            'item_status' => \App\Models\ItemStatus::class,
            'organization' => \App\Models\Organization::class,
            'role' => \App\Models\Role::class,
            'unit_of_measure' => \App\Models\UnitOfMeasure::class,
            'item_supplier' => \App\Models\ItemSupplier::class,
            'item_location' => \App\Models\ItemLocation::class,
            'maintenance_category' => \App\Models\MaintenanceCategory::class,
            'maintenance_condition' => \App\Models\MaintenanceCondition::class,
            'maintenance_detail' => \App\Models\MaintenanceDetail::class,
            'attachment' => \App\Models\Attachment::class,
        ];

        if (!isset($models[$modelType])) {
            $this->error("Unknown model type: {$modelType}");
            $this->line("Available models: " . implode(', ', array_keys($models)));
            return;
        }

        $class = $models[$modelType];
        $this->info("Backfilling {$modelType}...");
        $this->backfillModel($class, $modelType);
    }

    protected function backfillModel(string $modelClass, string $entityType)
    {
        // Get all models that don't have a public ID yet
        $modelsWithoutPublicId = $modelClass::whereDoesntHave('entityId', function ($query) use ($entityType) {
            $query->where('entity_type', $entityType);
        })->get();

        if ($modelsWithoutPublicId->isEmpty()) {
            $this->line("  No {$entityType} records need backfilling.");
            return;
        }

        $bar = $this->output->createProgressBar($modelsWithoutPublicId->count());
        $bar->start();

        $successful = 0;
        $failed = 0;

        foreach ($modelsWithoutPublicId as $model) {
            try {
                $this->entityIdService->generatePublicId(
                    $model->org_id,
                    $entityType,
                    $model->id
                );
                $successful++;
            } catch (\Exception $e) {
                $this->error("\n  Failed to generate public ID for {$entityType} ID {$model->id}: " . $e->getMessage());
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->line("\n  Backfilled {$successful} {$entityType} records. Failed: {$failed}");
    }
}

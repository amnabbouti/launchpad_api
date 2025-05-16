<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCondition;
use App\Models\MaintenanceDetail;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $org = \App\Models\Organization::first() ?? \App\Models\Organization::factory()->create();

        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
        ]);

        $testUser = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
        ]);

        $users = User::factory(8)->create([
            'organization_id' => $org->id,
        ]);
        $allUsers = collect([$admin, $testUser])->merge($users);

        // Seed nested categories
        $root = Category::firstOrCreate(['name' => 'Root', 'parent_id' => null]);
        $electronics = Category::firstOrCreate(['name' => 'Electronics', 'parent_id' => $root->id]);
        $computers = Category::firstOrCreate(['name' => 'Computers', 'parent_id' => $electronics->id]);
        $laptops = Category::firstOrCreate(['name' => 'Laptops', 'parent_id' => $computers->id]);
        $desktops = Category::firstOrCreate(['name' => 'Desktops', 'parent_id' => $computers->id]);
        $phones = Category::firstOrCreate(['name' => 'Phones', 'parent_id' => $electronics->id]);
        $office = Category::firstOrCreate(['name' => 'Office Supplies', 'parent_id' => $root->id]);
        $furniture = Category::firstOrCreate(['name' => 'Furniture', 'parent_id' => $office->id]);
        $chairs = Category::firstOrCreate(['name' => 'Chairs', 'parent_id' => $furniture->id]);
        $desks = Category::firstOrCreate(['name' => 'Desks', 'parent_id' => $furniture->id]);
        $kitchen = Category::firstOrCreate(['name' => 'Kitchen', 'parent_id' => $root->id]);
        $appliances = Category::firstOrCreate(['name' => 'Appliances', 'parent_id' => $kitchen->id]);

        // Seed deeply nested locations and collect them for later use
        $locationModels = [];
        $hq = Location::firstOrCreate(['name' => 'HQ', 'code' => 'HQ', 'is_active' => true, 'parent_id' => null]);
        $locationModels[] = $hq;
        $floor1 = Location::firstOrCreate(['name' => 'Floor 1', 'code' => 'F1', 'is_active' => true, 'parent_id' => $hq->id]);
        $locationModels[] = $floor1;
        $floor2 = Location::firstOrCreate(['name' => 'Floor 2', 'code' => 'F2', 'is_active' => true, 'parent_id' => $hq->id]);
        $locationModels[] = $floor2;
        $room101 = Location::firstOrCreate(['name' => 'Room 101', 'code' => 'R101', 'is_active' => true, 'parent_id' => $floor1->id]);
        $locationModels[] = $room101;
        $shelfA = Location::firstOrCreate(['name' => 'Shelf A', 'code' => 'S-A', 'is_active' => true, 'parent_id' => $room101->id]);
        $locationModels[] = $shelfA;
        $box1 = Location::firstOrCreate(['name' => 'Box 1', 'code' => 'B1', 'is_active' => true, 'parent_id' => $shelfA->id]);
        $locationModels[] = $box1;
        $compartmentA = Location::firstOrCreate(['name' => 'Compartment A', 'code' => 'C-A', 'is_active' => true, 'parent_id' => $box1->id]);
        $locationModels[] = $compartmentA;
        $compartmentB = Location::firstOrCreate(['name' => 'Compartment B', 'code' => 'C-B', 'is_active' => true, 'parent_id' => $box1->id]);
        $locationModels[] = $compartmentB;
        $shelfB = Location::firstOrCreate(['name' => 'Shelf B', 'code' => 'S-B', 'is_active' => true, 'parent_id' => $room101->id]);
        $locationModels[] = $shelfB;
        $room102 = Location::firstOrCreate(['name' => 'Room 102', 'code' => 'R102', 'is_active' => true, 'parent_id' => $floor1->id]);
        $locationModels[] = $room102;
        $lockerA = Location::firstOrCreate(['name' => 'Locker A', 'code' => 'L-A', 'is_active' => true, 'parent_id' => $room102->id]);
        $locationModels[] = $lockerA;
        $lockerB = Location::firstOrCreate(['name' => 'Locker B', 'code' => 'L-B', 'is_active' => true, 'parent_id' => $room102->id]);
        $locationModels[] = $lockerB;
        $room201 = Location::firstOrCreate(['name' => 'Room 201', 'code' => 'R201', 'is_active' => true, 'parent_id' => $floor2->id]);
        $locationModels[] = $room201;
        $cabinet1 = Location::firstOrCreate(['name' => 'Cabinet 1', 'code' => 'CAB1', 'is_active' => true, 'parent_id' => $room201->id]);
        $locationModels[] = $cabinet1;
        $drawerA = Location::firstOrCreate(['name' => 'Drawer A', 'code' => 'DRA', 'is_active' => true, 'parent_id' => $cabinet1->id]);
        $locationModels[] = $drawerA;
        $drawerB = Location::firstOrCreate(['name' => 'Drawer B', 'code' => 'DRB', 'is_active' => true, 'parent_id' => $cabinet1->id]);
        $locationModels[] = $drawerB;
        $retail = Location::firstOrCreate(['name' => 'Retail Store', 'code' => 'RETAIL', 'is_active' => true, 'parent_id' => null]);
        $locationModels[] = $retail;
        $retailBack = Location::firstOrCreate(['name' => 'Back Room', 'code' => 'RETAIL-BACK', 'is_active' => true, 'parent_id' => $retail->id]);
        $locationModels[] = $retailBack;
        $retailSafe = Location::firstOrCreate(['name' => 'Safe', 'code' => 'SAFE', 'is_active' => true, 'parent_id' => $retailBack->id]);
        $locationModels[] = $retailSafe;
        $retailSafeDrawer = Location::firstOrCreate(['name' => 'Safe Drawer', 'code' => 'SAFE-DR', 'is_active' => true, 'parent_id' => $retailSafe->id]);
        $locationModels[] = $retailSafeDrawer;

        $stockData = [
            ['serial_number' => 'REG-001', 'barcode' => 'STOCK-REG-001', 'notes' => 'Items in regular inventory', 'is_active' => true],
            ['serial_number' => 'RES-001', 'barcode' => 'STOCK-RES-001', 'notes' => 'Items held in reserve', 'is_active' => true],
            ['serial_number' => 'DAM-001', 'barcode' => 'STOCK-DAM-001', 'notes' => 'Items that are damaged', 'is_active' => true],
            ['serial_number' => 'CLR-001', 'barcode' => 'STOCK-CLR-001', 'notes' => 'Items on clearance', 'is_active' => true],
            ['serial_number' => 'DIS-001', 'barcode' => 'STOCK-DIS-001', 'notes' => 'Items that are discontinued', 'is_active' => false],
        ];

        $stockModels = [];
        foreach ($stockData as $data) {
            $stockModels[] = Stock::firstOrCreate(
                ['serial_number' => $data['serial_number']],
                $data
            );
        }

        $unitOfMeasureData = [
            ['name' => 'Each', 'code' => 'ea', 'symbol' => 'ea', 'type' => UnitOfMeasure::TYPE_QUANTITY, 'is_active' => true],
            ['name' => 'Kilogram', 'code' => 'kg', 'symbol' => 'kg', 'type' => UnitOfMeasure::TYPE_WEIGHT, 'is_active' => true],
            ['name' => 'Liter', 'code' => 'L', 'symbol' => 'L', 'type' => UnitOfMeasure::TYPE_VOLUME, 'is_active' => true],
            ['name' => 'Meter', 'code' => 'm', 'symbol' => 'm', 'type' => UnitOfMeasure::TYPE_DISTANCE, 'is_active' => true],
            ['name' => 'Box', 'code' => 'box', 'symbol' => 'box', 'type' => UnitOfMeasure::TYPE_QUANTITY, 'is_active' => true],
            ['name' => 'Pair', 'code' => 'pr', 'symbol' => 'pr', 'type' => UnitOfMeasure::TYPE_QUANTITY, 'is_active' => true],
            ['name' => 'Pack', 'code' => 'pk', 'symbol' => 'pk', 'type' => UnitOfMeasure::TYPE_QUANTITY, 'is_active' => true],
            ['name' => 'Dozen', 'code' => 'dz', 'symbol' => 'dz', 'type' => UnitOfMeasure::TYPE_QUANTITY, 'is_active' => false],
        ];

        foreach ($unitOfMeasureData as $data) {
            UnitOfMeasure::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        // Create suppliers if they don't exist
        $supplierData = [
            ['name' => 'Tech Supplies Inc.', 'contact_name' => 'John Smith', 'email' => 'john@techsupplies.com', 'phone' => '555-123-4567', 'is_active' => true],
            ['name' => 'Office Depot', 'contact_name' => 'Jane Doe', 'email' => 'jane@officedepot.com', 'phone' => '555-234-5678', 'is_active' => true],
            ['name' => 'Furniture World', 'contact_name' => 'Bob Johnson', 'email' => 'bob@furnitureworld.com', 'phone' => '555-345-6789', 'is_active' => true],
            ['name' => 'Kitchen Supplies Co.', 'contact_name' => 'Alice Brown', 'email' => 'alice@kitchensupplies.com', 'phone' => '555-456-7890', 'is_active' => true],
            ['name' => 'Discontinued Vendor', 'contact_name' => 'Tom Wilson', 'email' => 'tom@discontinued.com', 'phone' => '555-567-8901', 'is_active' => false],
        ];

        $supplierModels = [];
        foreach ($supplierData as $data) {
            $supplierModels[] = Supplier::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        // Create items if they don't exist
        $itemData = [
            [
                'name' => 'Laptop Computer',
                'code' => 'COMP-001',
                'description' => 'High-performance laptop for business use',
                'quantity' => 25,
                'price' => 1200.00,
                'unit' => 'Each',
                'category_id' => 2,
                'user_id' => $admin->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['processor' => 'Intel i7', 'ram' => '16GB', 'storage' => '512GB SSD']),
            ],
            [
                'name' => 'Desktop Computer',
                'code' => 'COMP-002',
                'description' => 'Powerful desktop workstation',
                'quantity' => 15,
                'price' => 1500.00,
                'unit' => 'Each',
                'category_id' => 2,
                'user_id' => $admin->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['processor' => 'Intel i9', 'ram' => '32GB', 'storage' => '1TB SSD']),
            ],
            [
                'name' => 'Smartphone',
                'code' => 'PHONE-001',
                'description' => 'Latest model smartphone',
                'quantity' => 50,
                'price' => 800.00,
                'unit' => 'Each',
                'category_id' => 3,
                'user_id' => $testUser->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['screen' => '6.5 inch', 'camera' => '12MP', 'storage' => '128GB']),
            ],
            [
                'name' => 'Wireless Mouse',
                'code' => 'ACC-001',
                'description' => 'Ergonomic wireless mouse',
                'quantity' => 100,
                'price' => 25.00,
                'unit' => 'Each',
                'category_id' => 4,
                'user_id' => $users[0]->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['connectivity' => 'Bluetooth', 'battery' => 'Rechargeable']),
            ],
            [
                'name' => 'Wireless Keyboard',
                'code' => 'ACC-002',
                'description' => 'Compact wireless keyboard',
                'quantity' => 75,
                'price' => 45.00,
                'unit' => 'Each',
                'category_id' => 4,
                'user_id' => $users[0]->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['connectivity' => 'Bluetooth', 'battery' => 'Rechargeable']),
            ],
            [
                'name' => 'Printer Paper',
                'code' => 'OFFICE-001',
                'description' => 'A4 printer paper, 500 sheets',
                'quantity' => 200,
                'price' => 5.00,
                'unit' => 'Pack',
                'category_id' => 5,
                'user_id' => $users[1]->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['size' => 'A4', 'weight' => '80gsm']),
            ],
            [
                'name' => 'Office Chair',
                'code' => 'FURN-001',
                'description' => 'Ergonomic office chair',
                'quantity' => 30,
                'price' => 150.00,
                'unit' => 'Each',
                'category_id' => 7,
                'user_id' => $users[2]->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['material' => 'Mesh', 'color' => 'Black', 'adjustable' => true]),
            ],
            [
                'name' => 'Standing Desk',
                'code' => 'FURN-002',
                'description' => 'Adjustable height standing desk',
                'quantity' => 20,
                'price' => 300.00,
                'unit' => 'Each',
                'category_id' => 8,
                'user_id' => $users[2]->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['material' => 'Wood/Metal', 'color' => 'Brown', 'electric' => true]),
            ],
            [
                'name' => 'Coffee Maker',
                'code' => 'KITCH-001',
                'description' => 'Programmable coffee maker',
                'quantity' => 15,
                'price' => 75.00,
                'unit' => 'Each',
                'category_id' => 10,
                'user_id' => $users[3]->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['capacity' => '12 cups', 'programmable' => true]),
            ],
            [
                'name' => 'Microwave Oven',
                'code' => 'KITCH-002',
                'description' => 'Countertop microwave oven',
                'quantity' => 10,
                'price' => 120.00,
                'unit' => 'Each',
                'category_id' => 10,
                'user_id' => $users[3]->id,
                'stock_id' => $stockModels[0]->id,
                'is_active' => true,
                'specifications' => json_encode(['power' => '1000W', 'capacity' => '1.2 cubic feet']),
            ],
            [
                'name' => 'Discontinued Item',
                'code' => 'DISC-001',
                'description' => 'This item is no longer available',
                'quantity' => 5,
                'price' => 10.00,
                'unit' => 'Each',
                'category_id' => 5,
                'user_id' => $users[4]->id,
                'stock_id' => $stockModels[4]->id,
                'is_active' => false,
                'specifications' => json_encode(['status' => 'discontinued']),
            ],
        ];

        $itemModels = [];
        foreach ($itemData as $data) {
            $itemModels[] = Item::firstOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        foreach ($itemModels as $index => $item) {
            if ($index < count($itemModels) - 1) {
                $randomLocations = collect($locationModels)
                    ->where('is_active', true)
                    ->random(rand(1, 3));

                foreach ($randomLocations as $location) {
                    if (! $item->locations()->where('location_id', $location->id)->exists()) {
                        $item->locations()->attach($location->id, [
                            'quantity' => rand(1, 10),
                        ]);
                    }
                }
            }
        }

        foreach ($itemModels as $index => $item) {
            if ($index < count($itemModels) - 1) {
                $randomSuppliers = collect($supplierModels)
                    ->where('is_active', true)
                    ->random(rand(1, 2));

                foreach ($randomSuppliers as $supplier) {
                    if (! $item->suppliers()->where('supplier_id', $supplier->id)->exists()) {
                        $item->suppliers()->attach($supplier->id, [
                            'supplier_part_number' => 'SP'.rand(1000, 9999),
                            'price' => $item->price * (rand(80, 95) / 100),
                            'lead_time' => rand(1, 30),
                            'is_preferred' => rand(0, 1),
                        ]);
                    }
                }
            }
        }

        // Seed check-in/out records
        $this->call(CheckInOutSeeder::class);

        $maintenanceCategoryData = [
            ['remarks' => 'Routine Maintenance - Regular scheduled maintenance'],
            ['remarks' => 'Repair - Fixing broken or damaged items'],
            ['remarks' => 'Inspection - Regular safety and operational inspections'],
            ['remarks' => 'Calibration - Calibration of sensitive equipment'],
            ['remarks' => 'Software Update - Updates to software or firmware'],
        ];

        $maintenanceCategoryModels = [];
        foreach ($maintenanceCategoryData as $data) {
            $maintenanceCategoryModels[] = MaintenanceCategory::create($data);
        }

        $maintenanceConditionData = [
            [
                'mail_on_warning' => true,
                'mail_on_maintenance' => true,
                'maintenance_recurrence_quantity' => 90,
                'maintenance_warning_date' => now()->addDays(80),
                'maintenance_date' => now()->addDays(90),
                'quantity_for_warning' => 80.00,
                'quantity_for_maintenance' => 90.00,
                'recurrence_unit' => 'days',
                'price_per_unit' => 50.00,
                'is_active' => true,
                'maintenance_category_id' => $maintenanceCategoryModels[0]->id,
            ],
            [
                'mail_on_warning' => true,
                'mail_on_maintenance' => true,
                'maintenance_recurrence_quantity' => 180,
                'maintenance_warning_date' => now()->addDays(170),
                'maintenance_date' => now()->addDays(180),
                'quantity_for_warning' => 170.00,
                'quantity_for_maintenance' => 180.00,
                'recurrence_unit' => 'days',
                'price_per_unit' => 75.00,
                'is_active' => true,
                'maintenance_category_id' => $maintenanceCategoryModels[1]->id,
            ],
            [
                'mail_on_warning' => true,
                'mail_on_maintenance' => true,
                'maintenance_recurrence_quantity' => 30,
                'maintenance_warning_date' => now()->addDays(25),
                'maintenance_date' => now()->addDays(30),
                'quantity_for_warning' => 25.00,
                'quantity_for_maintenance' => 30.00,
                'recurrence_unit' => 'days',
                'price_per_unit' => 30.00,
                'is_active' => true,
                'maintenance_category_id' => $maintenanceCategoryModels[2]->id,
            ],
        ];

        $maintenanceConditionModels = [];
        foreach ($maintenanceConditionData as $data) {
            $maintenanceConditionModels[] = MaintenanceCondition::create($data);
        }

        // Create maintenance records
        $dates = [
            now()->subMonths(6),
            now()->subMonths(3),
            now()->subMonths(1),
            now()->subDays(15),
            now()->subDays(5),
        ];

        foreach ([0, 1, 2, 7, 8, 9] as $itemIndex) {
            if (isset($itemModels[$itemIndex])) {
                $item = $itemModels[$itemIndex];
                $stock = $stockModels[array_rand($stockModels)];
                $supplier = $supplierModels[array_rand($supplierModels)];
                $employee = $allUsers->random();
                // Create 1-2 maintenance records per item
                $recordCount = rand(1, 2);
                for ($i = 0; $i < $recordCount; $i++) {
                    $maintenanceDate = $dates[array_rand($dates)];
                    $expectedBackDate = $maintenanceDate->copy()->addDays(rand(3, 14));
                    $actualBackDate = rand(0, 1) ? $expectedBackDate->copy()->addDays(rand(-2, 5)) : null;
                    $isRepair = rand(0, 1);
                    $maintenanceRecord = Maintenance::create([
                        'is_repair' => $isRepair,
                        'remarks' => $isRepair ? 'Repair needed for '.$item->name : 'Routine maintenance for '.$item->name,
                        'invoice_nbr' => 'INV-'.rand(10000, 99999),
                        'cost' => rand(50, 500),
                        'date_expected_back_from_maintenance' => $expectedBackDate,
                        'date_back_from_maintenance' => $actualBackDate,
                        'date_in_maintenance' => $maintenanceDate,
                        'supplier_id' => $supplier->id,
                        'item_id' => $item->id,
                        'employee_id' => $employee->id,
                        'status_out_id' => null,
                        'status_in_id' => null,
                    ]);
                    // Add maintenance details
                    MaintenanceDetail::create([
                        'value' => rand(1, 5) * 10.0,
                        'maintenance_condition_id' => $maintenanceConditionModels[array_rand($maintenanceConditionModels)]->id,
                        'maintenance_id' => $maintenanceRecord->id,
                    ]);
                }
            }
        }
    }
}

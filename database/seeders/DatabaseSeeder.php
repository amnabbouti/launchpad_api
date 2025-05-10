<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if admin user exists, create if not
        $admin = User::where('email', 'admin@example.com')->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]);
        }

        // Check if test user exists, create if not
        $testUser = User::where('email', 'test@example.com')->first();
        if (!$testUser) {
            $testUser = User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
            ]);
        }

        // Create regular users
        $users = User::factory(8)->create();
        $allUsers = collect([$admin, $testUser])->merge($users);

        // Create categories if they don't exist
        $categoryData = [
            ['name' => 'Electronics', 'parent_id' => null],
            ['name' => 'Computers', 'parent_id' => 1],
            ['name' => 'Smartphones', 'parent_id' => 1],
            ['name' => 'Accessories', 'parent_id' => 1],
            ['name' => 'Office Supplies', 'parent_id' => null],
            ['name' => 'Furniture', 'parent_id' => null],
            ['name' => 'Chairs', 'parent_id' => 6],
            ['name' => 'Desks', 'parent_id' => 6],
            ['name' => 'Kitchen', 'parent_id' => null],
            ['name' => 'Appliances', 'parent_id' => 9],
        ];

        // First create parent categories (null parent_id)
        foreach ($categoryData as $data) {
            if ($data['parent_id'] === null) {
                Category::firstOrCreate(
                    ['name' => $data['name'], 'parent_id' => null],
                    $data
                );
            }
        }

        // Then create child categories
        foreach ($categoryData as $data) {
            if ($data['parent_id'] !== null) {
                Category::firstOrCreate(
                    ['name' => $data['name'], 'parent_id' => $data['parent_id']],
                    $data
                );
            }
        }

        // Create locations if they don't exist
        $locationData = [
            ['name' => 'Main Warehouse', 'code' => 'MAIN-WH', 'is_active' => true],
            ['name' => 'Secondary Warehouse', 'code' => 'SEC-WH', 'is_active' => true],
            ['name' => 'Office Building', 'code' => 'OFFICE', 'is_active' => true],
            ['name' => 'Retail Store', 'code' => 'RETAIL', 'is_active' => true],
            ['name' => 'Old Warehouse', 'code' => 'OLD-WH', 'is_active' => false],
        ];

        $locationModels = [];
        foreach ($locationData as $data) {
            $locationModels[] = Location::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        // Create stocks if they don't exist
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

        // Create units of measure if they don't exist
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

        // Assign items to locations
        foreach ($itemModels as $index => $item) {
            // Skip the last item (discontinued)
            if ($index < count($itemModels) - 1) {
                // Assign to 1-3 random locations
                $randomLocations = collect($locationModels)
                    ->where('is_active', true)
                    ->random(rand(1, 3));

                foreach ($randomLocations as $location) {
                    // Check if the relationship already exists
                    if (!$item->locations()->where('location_id', $location->id)->exists()) {
                        $item->locations()->attach($location->id, [
                            'quantity' => rand(1, 10)
                        ]);
                    }
                }
            }
        }

        // Assign items to suppliers
        foreach ($itemModels as $index => $item) {
            // Skip the last item (discontinued)
            if ($index < count($itemModels) - 1) {
                // Assign to 1-2 random suppliers
                $randomSuppliers = collect($supplierModels)
                    ->where('is_active', true)
                    ->random(rand(1, 2));

                foreach ($randomSuppliers as $supplier) {
                    // Check if the relationship already exists
                    if (!$item->suppliers()->where('supplier_id', $supplier->id)->exists()) {
                        $item->suppliers()->attach($supplier->id, [
                            'supplier_part_number' => 'SP' . rand(1000, 9999),
                            'price' => $item->price * (rand(80, 95) / 100), // 80-95% of retail price
                            'lead_time' => rand(1, 30), // Lead time in days
                            'is_preferred' => rand(0, 1)
                        ]);
                    }
                }
            }
        }
    }
}

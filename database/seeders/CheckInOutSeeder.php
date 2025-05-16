<?php

namespace Database\Seeders;

use App\Models\CheckInOut;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckInOutSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            $users = collect([User::create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ])]);
        }

        $items = Item::all();
        if ($items->isEmpty()) {
            $this->command->info('No items found. Please seed items first!');

            return;
        }

        $locations = Location::all();
        if ($locations->isEmpty()) {
            $locations = collect([Location::create([
                'name' => 'Default Location',
                'code' => 'DEFAULT',
                'is_active' => true,
            ])]);
        }

        $statuses = DB::table('stock_statuses')->get();
        if ($statuses->isEmpty()) {
            $statusId = DB::table('stock_statuses')->insertGetId([
                'name' => 'Default Status',
                'code' => 'DEFAULT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $statuses = collect([DB::table('stock_statuses')->where('id', $statusId)->first()]);
        }

        foreach ($items as $index => $item) {
            $checkout = CheckInOut::create([
                'user_id' => $users->random()->id,
                'item_id' => $item->id,
                'checkout_location_id' => $locations->random()->id,
                'checkout_date' => now()->subDays(rand(1, 30)),
                'quantity' => rand(1, 3),
                'status_out_id' => $statuses->random()->id,
                'expected_return_date' => now()->addDays(rand(1, 14)),
                'reference' => 'REF-'.strtoupper(substr(md5(rand()), 0, 6)),
                'notes' => 'Checkout notes for '.$item->name,
                'is_active' => true,
            ]);

            if ($index % 2 == 0) {
                $checkout->update([
                    'checkin_user_id' => $users->random()->id,
                    'checkin_location_id' => $locations->random()->id,
                    'checkin_date' => now()->subDays(rand(1, 5)),
                    'checkin_quantity' => $checkout->quantity,
                    'status_in_id' => $statuses->random()->id,
                    'notes' => $checkout->notes.' | Check-in notes added.',
                    'is_active' => false,
                ]);
            }

            if ($index % 3 == 0) {
                CheckInOut::create([
                    'user_id' => $users->random()->id,
                    'item_id' => $item->id,
                    'checkout_location_id' => $locations->random()->id,
                    'checkout_date' => now()->subDays(rand(60, 90)),
                    'quantity' => rand(1, 3),
                    'status_out_id' => $statuses->random()->id,
                    'checkin_user_id' => $users->random()->id,
                    'checkin_location_id' => $locations->random()->id,
                    'checkin_date' => now()->subDays(rand(31, 59)),
                    'checkin_quantity' => rand(1, 3),
                    'status_in_id' => $statuses->random()->id,
                    'expected_return_date' => now()->subDays(rand(40, 50)),
                    'reference' => 'OLD-'.strtoupper(substr(md5(rand()), 0, 6)),
                    'notes' => 'Old checkout record for '.$item->name,
                    'is_active' => false,
                ]);
            }
        }

        $this->command->info('CheckInOut records created successfully!');
    }
}

<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

final class OrganizationFactory extends Factory {
    protected $model = Organization::class;

    public function definition(): array {
        return [
            'name'            => $this->faker->company,
            'email'           => $this->faker->unique()->companyEmail,
            'telephone'       => $this->faker->phoneNumber,
            'street'          => $this->faker->streetName,
            'street_number'   => $this->faker->buildingNumber,
            'city'            => $this->faker->city,
            'province'        => $this->faker->state,
            'postal_code'     => $this->faker->postcode,
            'remarks'         => $this->faker->optional()->sentence,
            'website'         => $this->faker->optional()->url,
            'logo'            => $this->faker->optional()->imageUrl(200, 200, 'business'),
            'industry'        => $this->faker->optional()->word,
            'tax_id'          => $this->faker->optional()->bothify('??########'),
            'billing_address' => $this->faker->optional()->address,
            'country'         => $this->faker->country,
            'timezone'        => $this->faker->timezone,
            'status'          => 'active',
            // plans/subscription removed
            'settings'   => null,
            'created_by' => null,
        ];
    }
}

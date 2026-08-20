<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['expense', 'income']);

        return [
            'name' => fake()->unique()->word(),
            'name_en' => fake()->unique()->word(),
            'type' => $type,
            'user_id' => null,
        ];
    }

    /**
     * Indicate that the category belongs to the given user.
     */
    public function ownedBy(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}

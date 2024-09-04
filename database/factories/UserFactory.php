<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
           // 'name' => fake()->name(),
           'idnumber' => $this->faker->unique()->randomNumber(5),
           'fname' => $this->faker->firstName,
           'mname' => $this->faker->lastName,  // Assuming mname is for middle name
           'lname' => $this->faker->lastName,
           'sex' => $this->faker->randomElement(['Male', 'Female']),
           'usertype' => $this->faker->randomElement(['admin', 'student', 'teacher']),  // Example user types
           'email' => $this->faker->unique()->safeEmail(),
           'email_verified_at' => now(),
           'password' => bcrypt('password'),  // Example password
           'status' => 'active',
           'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

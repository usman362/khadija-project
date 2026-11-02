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
            /*
             * A name with no apostrophe in it.
             *
             * Faker produces "Kamron O'Connell" on some seeds and not others,
             * and a page quite correctly escapes that to &#039;. Tests that
             * compare a fixture's raw name against rendered HTML therefore
             * pass on most seeds and fail on a few, which has cost this
             * project four separate sessions chasing a failure that was never
             * about the code under test.
             *
             * Escaping is right and stays; it is the comparison that cannot
             * work. A test that wants to check escaping sets its own name.
             */
            'name' => str_replace("'", '', fake()->name()),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
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

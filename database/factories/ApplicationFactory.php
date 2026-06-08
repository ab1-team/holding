<?php

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'icon_path' => null,
            'base_url' => 'https://' . fake()->domainName(),
            'api_token_key' => Str::random(32),
            'has_financial_report' => true,
            'is_active' => true,
        ];
    }

    public function noFinancialReport(): static
    {
        return $this->state(fn () => ['has_financial_report' => false]);
    }
}

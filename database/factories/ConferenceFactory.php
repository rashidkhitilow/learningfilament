<?php

namespace Database\Factories;

use App\Enums\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Conference;
use App\Models\Venue;

class ConferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Conference::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $start_date = $this->faker->dateTimeBetween('-1 years', 'now');
        $end_date = $this->faker->dateTimeBetween($start_date, '+1 years');
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $this->faker->randomElement([
              'draft',
              'published',
              'archived',
            ]),
            'region' => $this->faker->randomElement(Region::class),
            'venue_id' => null,
        ];
    }
}

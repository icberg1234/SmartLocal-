<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Core\Models\Mall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mall>
 */
class MallFactory extends Factory
{
    protected $model = Mall::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'type' => 'mall',
            'subdomain' => $this->faker->unique()->domainWord(),
            'settings' => [],
        ];
    }
}

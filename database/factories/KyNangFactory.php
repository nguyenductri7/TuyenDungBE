<?php

namespace Database\Factories;

use App\Models\KyNang;
use Illuminate\Database\Eloquent\Factories\Factory;

class KyNangFactory extends Factory
{
    protected $model = KyNang::class;

    public function definition(): array
    {
        $ten = $this->faker->unique()->randomElement([
            'Machine Learning',
            'Blockchain',
            'Cloud Computing',
            'Data Engineering',
            'UI/UX Research',
            'Growth Hacking',
            'Scrum Master',
            'Technical Writing',
            'GraphQL',
        ]);

        return [
            'ten_ky_nang' => $ten,
            'mo_ta' => $this->faker->sentence(),
            'icon' => null,
        ];
    }
}

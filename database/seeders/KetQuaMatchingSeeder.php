<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KetQuaMatchingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('KetQuaMatchingSeeder: Bỏ qua. Kết quả AI Matching phải được sinh thật khi demo, không seed dữ liệu giả.');
    }
}

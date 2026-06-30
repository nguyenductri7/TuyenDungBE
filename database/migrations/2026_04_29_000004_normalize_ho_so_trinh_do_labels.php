<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $labels = [
        'trung_hoc' => 'Trung học',
        'trung_cap' => 'Trung cấp',
        'cao_dang' => 'Cao đẳng',
        'dai_hoc' => 'Đại học',
        'thac_si' => 'Thạc sĩ',
        'tien_si' => 'Tiến sĩ',
        'khac' => 'Khác',
    ];

    public function up(): void
    {
        foreach ($this->labels as $legacyKey => $label) {
            DB::table('ho_sos')
                ->where('trinh_do', $legacyKey)
                ->update(['trinh_do' => $label]);
        }
    }

    public function down(): void
    {
        foreach ($this->labels as $legacyKey => $label) {
            DB::table('ho_sos')
                ->where('trinh_do', $label)
                ->update(['trinh_do' => $legacyKey]);
        }
    }
};

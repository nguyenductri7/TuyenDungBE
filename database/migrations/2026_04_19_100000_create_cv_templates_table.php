<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_templates', function (Blueprint $table) {
            $table->id();
            $table->string('ma_template', 100)->unique();
            $table->string('ten_template', 150);
            $table->text('mo_ta')->nullable();
            $table->string('bo_cuc', 100);
            $table->json('badges_json')->nullable();
            $table->unsignedTinyInteger('trang_thai')->default(1);
            $table->unsignedInteger('thu_tu_hien_thi')->default(0);
            $table->timestamps();
        });

        DB::table('cv_templates')->insert([
            [
                'ma_template' => 'executive_navy',
                'ten_template' => 'Executive Navy',
                'mo_ta' => 'Bám theo mẫu header xanh đậm, tên căn giữa, sidebar trái và phần kinh nghiệm chi tiết.',
                'bo_cuc' => 'executive_navy',
                'badges_json' => json_encode(['Hợp Product / Business', 'Hợp HR / Finance'], JSON_UNESCAPED_UNICODE),
                'trang_thai' => 1,
                'thu_tu_hien_thi' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ma_template' => 'topcv_maroon',
                'ten_template' => 'Sidebar Maroon',
                'mo_ta' => 'Bám theo mẫu sidebar đỏ nâu có ảnh đại diện lớn, cột trái đậm màu và nội dung trắng bên phải.',
                'bo_cuc' => 'topcv_maroon',
                'badges_json' => json_encode(['Hợp Frontend / Mobile', 'Hợp Marketing / UI UX'], JSON_UNESCAPED_UNICODE),
                'trang_thai' => 1,
                'thu_tu_hien_thi' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ma_template' => 'ats_serif',
                'ten_template' => 'ATS Serif',
                'mo_ta' => 'Bám theo mẫu ATS trắng tối giản, chữ serif, một cột, ưu tiên đọc nhanh và in đẹp.',
                'bo_cuc' => 'ats_serif',
                'badges_json' => json_encode(['Hợp ATS / Software', 'Hợp Data / Intern'], JSON_UNESCAPED_UNICODE),
                'trang_thai' => 1,
                'thu_tu_hien_thi' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_templates');
    }
};

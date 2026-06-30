<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TinTuyenDungParsing extends Model
{
    use HasFactory;

    protected $table = 'tin_tuyen_dung_parsings';

    protected $fillable = [
        'tin_tuyen_dung_id',
        'raw_text',
        'parsed_skills_json',
        'parsed_requirements_json',
        'parsed_benefits_json',
        'parsed_salary_json',
        'parsed_location_json',
        'parse_status',
        'parser_version',
        'confidence_score',
        'error_message',
    ];

    protected $casts = [
        'parsed_skills_json' => 'array',
        'parsed_requirements_json' => 'array',
        'parsed_benefits_json' => 'array',
        'parsed_salary_json' => 'array',
        'parsed_location_json' => 'array',
        'parse_status' => 'integer',
        'confidence_score' => 'float',
    ];

    public function tinTuyenDung()
    {
        return $this->belongsTo(TinTuyenDung::class, 'tin_tuyen_dung_id');
    }
}

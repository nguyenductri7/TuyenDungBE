<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoSoParsing extends Model
{
    use HasFactory;

    protected $table = 'ho_so_parsings';

    protected $fillable = [
        'ho_so_id',
        'raw_text',
        'parsed_name',
        'parsed_email',
        'parsed_phone',
        'parsed_skills_json',
        'parsed_experience_json',
        'parsed_education_json',
        'parse_status',
        'parser_version',
        'confidence_score',
        'error_message',
    ];

    protected $casts = [
        'parsed_skills_json' => 'array',
        'parsed_experience_json' => 'array',
        'parsed_education_json' => 'array',
        'parse_status' => 'integer',
        'confidence_score' => 'float',
    ];

    public function hoSo()
    {
        return $this->belongsTo(HoSo::class, 'ho_so_id')->withTrashed();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionDefinition extends Model
{
    use HasFactory;

    public const SCOPE_ADMIN = 'admin';
    public const SCOPE_EMPLOYER = 'employer';

    protected $fillable = [
        'scope',
        'key',
        'label',
        'description',
        'mapped_permission_key',
        'is_system',
        'default_enabled',
        'created_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'default_enabled' => 'boolean',
        'created_by' => 'integer',
    ];
}

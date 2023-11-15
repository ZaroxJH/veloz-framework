<?php

namespace Veloz\Models;

use Veloz\Core\Model;

class ModuleJs extends Model
{
    protected static string $table = 'modules_js';

    protected array $fillable = [
        'module_id',
        'script',
        'public_path',
    ];
}
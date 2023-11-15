<?php

namespace Veloz\Models;

use Veloz\Core\Model;

class Module extends Model
{
    protected string $table = 'modules';

    protected array $columns = [
        'name',
        'description',
        'has_js',
    ];

    protected array $fillable = [
        'name',
        'description',
        'has_js',
    ];

    protected array $hidden = [
        'id',
    ];

    public function get_modules()
    {
        $modules = self::all();

        foreach ($modules as $module) {
            $module->js = ModuleJs::where('module_id', $module->id)->get();
        }

        return $modules;
    }
}
<?php

namespace Veloz\Core;

use Veloz\Models\Module;
use Veloz\Models\ModuleJs;

class ModuleLoader
{
    /**
     * Loads a module
     * 
     * @param string $module
     * @return bool
     */
    public static function load(string $module): bool
    {
        
    }

    public static function register_modules()
    {
        $modules = self::get_modules();

        foreach ($modules as $module) {
            if (isset($module['has_js']) && $module['has_js']) {
                $js_name = $module['name'];

                foreach ($module['js'] as $js) {
                    if (isset($module['js_name'])) {
                        $js_name = $module['js_name'];
                    }

                    self::insert_webpack($js, $js_name);
                }
            }
        }

        // Inserts the modules into the database
        foreach ($modules as $module) {
            $module_exists = Module::exists(['name' => $module['name']]);

            if ($module_exists) {
                continue;
            }

            $has_js = $module['has_js'] ?? false;

            $data = [
                'name' => $module['name'],
                'description' => $module['description'],
                'has_js' => (int)$has_js,
            ];
    
            $module_id = Module::create($data);

            if ($has_js) {
                foreach ($module['js'] as $js) {
                    $data = [
                        'module_id' => $module_id,
                        'script' => $js,
                        'public_path' => server_root() . '/public/assets/js/' . strtolower($module['name']) . '.js',
                    ];
            
                    $module_js = ModuleJs::create($data);
                }
            }
        }

        return;
    }

    public static function load_js()
    {
        $app_url = $_ENV['APP_URL'];
        $module_js = ModuleJs::get_all();
        $html_includes = '';

        foreach ($module_js as $js) {
            $html_includes .= '<script src="' .$_ENV['APP_URL'] . $js['public_path'] . '"></script>';
        }

        return $html_includes;
    }

    /**
     * Inserts the javascript file into webpack-entries.json
     */
    private static function insert_webpack(string $js, $js_name)
    {
        $entries_dir = server_root() . '/webpack-entries.json';
        $filtered_js = str_replace(server_root(), '', $js);
        
        // Make $js lowercase
        $filtered_js = strtolower($filtered_js);

        // Checks if webpack-entries.json exists
        if (!is_file($entries_dir)) {
            // Creates the file if it doesn't exist
            file_put_contents($entries_dir, '{}');
        }

        // Gets the contents of webpack-entries.json
        $entries = file_get_contents($entries_dir);

        // Decodes the contents of webpack-entries.json
        $entries = json_decode($entries, true);

        // Checks if the file is already in webpack-entries.json
        if (isset($entries[$js_name])) {
            // Checks if the file is already in the array
            if (!in_array($filtered_js, $entries[$js_name])) {
                // Adds the file to the array
                array_push($entries[$js_name], $filtered_js);
            }
        } else {
            // Adds the file to the array
            $entries[$js_name] = [$filtered_js];
        }

        // Encodes the array
        $entries = json_encode($entries, JSON_PRETTY_PRINT);

        // Writes the array to webpack-entries.json
        file_put_contents($entries_dir, $entries);

        return;
    }

    public static function get_modules()
    {
        $modules = [];

        $modules = array_merge($modules, self::get_modules_from_path($_ENV['APP_ROOT'] . 'modules'));
        $modules = array_merge($modules, self::get_modules_from_path(__DIR__ . '/../modules'));

        return $modules;
    }

    private static function get_modules_from_path(string $path): array
    {
        $final_modules = [];

        // Scandir if the path exists
        if (!is_dir($path)) {
            return $final_modules;
        }

        $modules = scandir($path);

        // Will see if there is a Core class in the module, and if so, it will load it and fetch the module data
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }
            
            // if the module is indeed a directory, we will check if it has a Core class
            if (is_dir($path . '/' . $module)) {
                if (is_file($path . '/' . $module . '/Core.php')) {
                    $class = 'Veloz\\Modules\\' . $module . '\\Core';
                    $module_data = $class::$module_data;

                    $final_modules[$module] = $module_data;
                }
            }
        }

        return $final_modules;
    }

}
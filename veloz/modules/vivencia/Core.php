<?php

namespace Veloz\Modules\Vivencia;

use Veloz\Web\Request;

class Core
{
    public static $module_data = [
        'name' => 'Vivencia',
        'description' => 'A module that allows you to bind data to a select element',
        'has_js' => true,
        'js' => [
            __DIR__ . '/js/vivencia.js',
        ],
        'js_name' => 'vivencia',
    ];

    public static $bind_data;

    public static function bind($data, $nested = []): void
    {
        $final_data = [];

        // In this case the bind:name is a value rather than database field
        if ($nested || !empty($nested)) {
            $value = $nested[0];
            $value_key = $nested[1];

            foreach ($data as $result) {
                $new_result = [];

                $new_result['bind_to'] = $result[$value];
                $new_result['value'] = $result[$value_key];

                array_push($final_data, $new_result);
            }
        } else {
            // In this case the bind:name is a database field
            foreach ($data as $result => $value) {
                $new_result = [];

                $new_result['bind_to'] = $result;
                $new_result['value'] = $value;

                array_push($final_data, $new_result);
            }
        }

        $_SESSION['bind_data'] = $final_data;

        // self::$bind_data = $final_data;
        return;
    }

    public static function get_bind_data(): array
    {
        return self::$bind_data;
    }

    /**
     * Returns the bind data as a JSON string using json_response()
     * 
     * @return string
     */
    public static function get_bind_data_json(): string
    {
        self::sanitize_data();
        $data = $_SESSION['bind_data'] ?? [];
        $_SESSION['bind_data'] = [];
        return Request::json_response($data);
        // return Request::json_response(self::$bind_data);
    }

    /**
     * Makes sure all data ia sanitized and allowed to be shown
     * 
     */
    private static function sanitize_data()
    {
        $data = self::$bind_data ?? [];

        if (empty($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $data[$key]['bind_to'] = htmlspecialchars($value['bind_to']);
            $data[$key]['value'] = htmlspecialchars($value['value']);
        }

        $_SESSION['bind_data'] = $data;

        // self::$bind_data = $data;
    }
}
<?php

namespace Veloz\Database\Seeders;

class RandomData
{
    public static function num($min, $max)
    {
        return rand($min, $max);
    }

    public static function string($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0,
                $charactersLength - 1)];
        }

        return $randomString;
    }
    
    /**
     * @param $data
     * @param int $num
     * @return array ex: [0 => $data, 1 => $data, 2 => $data]
     */
    public static function fill($data, $num = 1)
    {
        $result = [];
    
        for ($i = 0; $i < $num; $i++) {
            $dataCopy = $data; // Create a copy of the original data array
            
            foreach ($dataCopy as $key => $value) {
                if (is_array($value)) {
                    $method = $value[0];
                    $args = $value[1] ?? null;
    
                    $dataCopy[$key] = self::$method($args); // Modify the copy, not the original data
                }
            }
    
            $result[] = $dataCopy; // Add the modified copy to the result array
        }
    
        return $result;
    }
    
}
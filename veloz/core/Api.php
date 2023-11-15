<?php

namespace Veloz\Core;

class Api
{
    public array $data = [];

    /**
     * Checks if the api_key is valid
     * 
     * @param string $api_key
     * @return bool
     */
    public function checkApiKey(string $api_key): bool
    {
        if (!isset($_ENV['API_KEY'])) {
            return false;
        }

        if ($api_key !== $_ENV['API_KEY']) {
            return false;
        }

        return true;
    }

    /**
     * Returns a JSON response
     * 
     * @return bool|string
     */
    public function json_response(array $data = []): bool|string
    {
        $data = $data ?: $this->data;

        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
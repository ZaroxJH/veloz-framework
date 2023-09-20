<?php

namespace Veloz\Web;

class Request
{
    static array $error = [];
    static array $response = [];

    /**
     * Sends a request using given data
     * 
     * @return bool
     */
    public static function send(array $request_data): bool
    {
        $required_data = [
            'url',
            'method',
        ];

        $allowed_methods = [
            'GET',
            'POST',
            'PUT',
            'DELETE',
        ];

        // Checks if the required data is present
        foreach ($required_data as $data) {
            if (!isset($request_data[$data])) {
                self::set_response([
                    'message' => 'Missing required parameters',
                    'data' => $request_data,
                ]);
                return false;
            }
        }

        // Checks if the method is valid
        if (!in_array($request_data['method'], $allowed_methods)) {
            self::set_response([
                'message' => 'Invalid method',
                'data' => $request_data,
            ]);

            return false;
        }

        switch ($request_data['method']) {
            case 'GET':
                $request_data['url'] .= '?' . http_build_query($request_data['data']);
                break;
            case 'POST':
                $request_data['data'] = http_build_query($request_data['data']);
                break;
        }

        $curl = curl_init($request_data['url']);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request_data['method']);

        if (isset($request_data['data'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request_data['data']);
        }

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            self::set_response([
                'message' => curl_error($curl),
                'data' => $request_data,
            ]);
            return false;
        }

        // Sets the response
        self::set_response([
            'message' => 'Request sent',
            'data' => $response,
        ]);

        curl_close($curl);

        return true;
    }

    private static function set_response($response)
    {
        if (!isset($response['message'])) {
            $response['message'] = 'Something went wrong';
        }

        if (!isset($response['data'])) {
            $response['data'] = [];
        }

        self::$response = $response;
    }

    public static function get_response()
    {
        return self::$response;
    }

    /**
     * Sets an error
     * 
     * @return void
     */
    public static function set_error(array $response): void
    {
        if (!isset($response['message'])) {
            $response['message'] = 'Something went wrong';
        }

        if (!isset($response['data'])) {
            $response['data'] = [];
        }

        self::$error = $response;
    }

    /**
     * Gets the error
     * 
     * @return array
     */
    public static function get_error(): array
    {
        return self::$error;
    }
}
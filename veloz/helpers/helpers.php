<?php

use Veloz\Core\View;
use Veloz\Models\Log;
use Veloz\Helpers\Auth;
use Veloz\Core\Exception as HomeException;

if (! function_exists('auth')) {
    /**
     * @return Auth
     */
    function auth(): Auth
    {
        return new Auth();
    }
}

if (! function_exists('assets')) {
    /**
     * @param string $path
     * @return string
     */
    function assets(string $path): string
    {
        $folder = '';
        $skip = false;

        if (!empty($_ENV['APP_SUBFOLDER'])) {
            // Remove the first slash
            $folder = substr($_ENV['APP_SUBFOLDER'], 1);
        } else {
            $folder = $_ENV['APP_URL'];
            $skip = true;
        }

        if (str_starts_with($folder, '/')) {
            // Remove the first slash
            $folder = substr($folder, 1);
        }

        if (!file_exists(__DIR__ . '/../../LocalValetDriver.php')) {
            if ($skip) {
                return $folder . '/public/assets/' . $path;
            }
            return '/' . $folder . '/public/assets/' . $path;
        }

        // Return the path to the public folder, with the given path appended. We should be able to use this from anywhere.
        return '/' . $path;
    }
}

if (! function_exists('array_last')) {
    /**
     * Returns the last elements of an array, amount is dependent on the $amount parameter.
     *
     * @param array $array
     * @param int $amount
     * @param bool $desc
     * @return array
     */
    function array_last(array $array, int $amount, bool $desc = false)
    {
        // If $desc is set to true, we simply reverse the array and return the first $amount elements.
        if ($desc) {
            return array_slice(array_reverse($array), 0, $amount);
        }

        // Return the last $amount elements of the array.
        return array_slice($array, -$amount);
    }
}

if (! function_exists('array_first')) {
    function array_first(array $array, int $amount, bool $desc = false)
    {
        if ($desc) {
            return array_slice($array, -$amount);
        }

        return array_slice($array, 0, $amount);
    }
}

if (! function_exists('csrf_token')) {
    /**
     * @throws Exception
     */
    function csrf_token(): string
    {
        // Check if a CSRF token is already set in the session
        if (isset($_SESSION[$_ENV['APP_NAME']]['csrfToken'])) {
            return $_SESSION[$_ENV['APP_NAME']]['csrfToken'];
        }

        // Generate a new CSRF token and store it in the session
        try {
            $token = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            throw new Exception('Could not generate CSRF token');
        }
        $_SESSION[$_ENV['APP_NAME']]['csrfToken'] = $token;

        return $token;
    }
}

if (! function_exists('check_csrf')) {
    /**
     * Returns true if the CSRF token is valid, false otherwise.
     *
     * @return bool
     */
    function check_csrf(): bool
    {
        // Check if the CSRF token is valid
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION[$_ENV['APP_NAME']]['csrfToken']) {
            return false;
        }

        return true;
    }
}

if (! function_exists('check_notice')) {
    /**
     * Checks if a notice is set in the session.
     *
     * @return bool
     */
    function check_notice()
    {
        if (isset($_SESSION[$_ENV['APP_NAME']]['notice']) && isset($_SESSION[$_ENV['APP_NAME']]['notice']['message']) && isset($_SESSION[$_ENV['APP_NAME']]['notice']['type'])) {
            $notice = $_SESSION[$_ENV['APP_NAME']]['notice'];
            unset($_SESSION[$_ENV['APP_NAME']]['notice']);
            return $notice;
        }

        return false;
    }
}

if (! function_exists('exists_in_array')) {
    /**
     * Checks if a value exists in an array.
     *
     * @param string $needle
     * @param array $haystack
     * @return bool
     */
    function exists_in_array($needle, $haystack): bool
    {
        return in_array($needle, $haystack, true);
    }
}

if (! function_exists('redirect')) {
    /**
     * Redirects the user to the given URL.
     *
     */
    #[NoReturn] function redirect($url): void
    {
        session_write_close();
        header('Location: ' . $_ENV['APP_URL'] . $url);
        exit();
    }
}

if (! function_exists('set_exception')) {
    /**
     * Sets an exception in the session.
     *
     */
    function set_exception($message, $type, $fade): null | bool
    {
        $acceptedTypes = [
            'success',
            'error',
            'danger',
            'warning',
        ];

        if (!in_array($type, $acceptedTypes)) {
            return false;
        }

        return (new HomeException)->set($message, $type, $fade);
    }
}

if (! function_exists('t')) {
    /**
     * Translates the given string.
     * 
     * @param string $string
     * @param string $file
     * @param bool $admin
     * @return string
     */
    function t(string $string, string $file = '', bool $admin = false): string
    {
        $fromRoot = '';
    
        if ($admin) {
            // Sets lang from site_settings
        } else {
            // Sets lang to cookie preference
            $lang = $_COOKIE['lang'] ?? 'en';
            $fromRoot = $_ENV['APP_ROOT'] ?? '';
        }
    
        // If the file is empty, we simply return the string
        if (empty($file)) {
            return $string;
        }
    
        if (!str_ends_with($file, '.mo')) {
            $file .= '.mo';
        }
    
        $location = server_root() . $fromRoot . 'resources/lang/' . $lang . '/' . $file;
    
        // If the file is not empty, we check if the file exists
        if (!file_exists($location)) {
            // Log an error message or return the original string
            return $string;
        }

        $file = str_replace('.mo', '', $file);
    
        if (function_exists('gettext')) {
            setlocale(LC_ALL, 'nl_NL');
    
            // Specifies the location of the translation tables
            bindtextdomain($file, server_root() . $fromRoot . 'resources/lang/' . $lang . '/');
            bind_textdomain_codeset($file, 'UTF-8');
    
            // Sets the default domain to $file
            textdomain($file);
    
            // Attempt to translate the string
            $translation = gettext($string);
    
            if ($translation === $string) {
                // Translation failed, return the original string
                return $string;
            }
    
            // Return the translated string or the original string
            return $translation;
        } else {
            // gettext is not available, return the original string
            return $string;
        }
    }
}

if (! function_exists('scrape_translatables')) {
    /**
     * Scrapes and collects all translatable strings from the given directory.
     * 
     * @param string $dir
     * @return array|bool
     */
    function scrape_translatables(string $dir)
    {
        if (empty($dir)) {
            return false;
        }

        $files = scandir($dir);
        $translatables = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir($dir . '/' . $file)) {
                $translatables = array_merge($translatables, scrape_translatables($dir . '/' . $file));
            } else {
                $fileContent = file_get_contents($dir . '/' . $file);
                // Example call to t() function: t('Hello World', 'default')
                // We only want to get the first parameter, so we look got t(' and then get the string until the next ')
                $matches = [];
                preg_match_all('/t\(\'(.*?)\'/', $fileContent, $matches);
                if (!empty($matches[1])) {
                    $translatables = array_merge($translatables, $matches[1]);
                }
            }
        }

        return $translatables;
    }

}

if (! function_exists('create_po_file')) {
    /**
     * Generate the PO file based on the given information
     * 
     * @param array $translatables
     * @param string $location
     * @param string $fileName
     * @return bool
     */
    function create_po_file(array $translatables, string $location, string $fileName): bool
    {
        // Check if $translatables is not empty
        if (empty($translatables)) {
            return false;
        }

        if (!str_ends_with($fileName, '.po')) {
            $fileName .= '.po';
        }
    
        // Create the full file path
        $filePath = rtrim($location, '/') . '/' . $fileName;

        // Creates the file if it doesn't exist
        // if (!file_exists($filePath)) {
        //     fopen($filePath, 'w');
        // }
    
        // Open the PO file for writing
        $fileHandle = fopen($filePath, 'w');
    
        if (!$fileHandle) {
            return false; // Unable to open the file
        }
    
        // Write the PO header
        $header = 'msgid "" ' . PHP_EOL . 'msgstr ""' . PHP_EOL . '"Content-Type: text/plain; charset=UTF-8\n"' . PHP_EOL . '"Language: nl_NL\n"' . PHP_EOL . PHP_EOL . '';
    
        fwrite($fileHandle, $header);
    
        // Write translations for each translatable item
        foreach ($translatables as $msgid) {
            // Escape double quotes and backslashes in msgid
            $msgid = str_replace('"', '\\"', $msgid);

            // Write msgid with an empty msgstr
            fwrite($fileHandle, "msgid \"$msgid\"\n");
            fwrite($fileHandle, "msgstr \"\"\n\n");
        }
    
        // Close the file handle
        fclose($fileHandle);
    
        return true; // PO file created successfully
    }
}

if (! function_exists('validate_post')) {
    /**
     * Validates the given POST data.
     *
     * @throws Exception
     */
    function validate_post(array $rules): ?string
    {
        // Check if the request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        // Check if the request content type is application/x-www-form-urlencoded
        // if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] !== 'application/x-www-form-urlencoded') {
        //     return false;
        // }

        // Check if the request body is not empty
        if (empty($_POST)) {
            return false;
        }

        if (Auth::check()) {
            // Check if the CSRF token is valid
            if (!check_csrf()) {
                set_exception('Oops something went wrong', 'error');
                return false;
            }
        }

        // Validate the data from the request
        foreach ($rules as $key => $value) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $rules = explode('|', $value);
            foreach ($rules as $rule) {
                if (!isset($_POST[$key])) {
                    process_response('Nice try!', 'error', $uri);
                    return false;
                }

                if (str_contains($rule, ':')) {
                    // This means we have something like max:20
                    $rule = explode(':', $rule);

                    // Check if the rule is valid
                    if (!in_array($rule[0], ['min', 'max', 'length', 'in'])) {
                        throw new Exception('Invalid validation rule: ' . $rule[0]);
                    }

                    if ($rule[0] === 'min' && strlen($_POST[$key]) < $rule[1]) {
                        process_response('Oops something went wrong', 'error', $uri);
                        return false;
                    }

                    if ($rule[0] === 'max' && strlen($_POST[$key]) > $rule[1]) {
                        process_response('Oops something went wrong', 'error', $uri);
                        return false;
                    }

                    if ($rule[0] === 'in') {
                        $acceptedValues = explode(',', $rule[1]);
                        if (!exists_in_array($_POST[$key], $acceptedValues)) {
                            process_response('Oops something went wrong', 'error', $uri);
                            return false;
                        }
                    }
                }
                if ($rule === 'required' && empty($_POST[$key])) {
                    process_response('Oops something went wrong (You might want to listen to your browser)', 'error', $uri);
                    return false;
                }
                if ($rule === 'numeric' && !is_numeric($_POST[$key])) {
                    process_response('Oops something went wrong', 'error', $uri);
                    return false;
                } else {
                    // Checks for comma's and replaces them with dots
                    $_POST[$key] = str_replace(',', '.', $_POST[$key]);
                }
                if ($rule === 'email' && !filter_var($_POST[$key], FILTER_VALIDATE_EMAIL)) {
                    process_response('Oops something went wrong', 'error', $uri);
                    return false;
                }
            }
        }

        return true;
    }
}

if (! function_exists('validate_get')) {
    function validate_get(array $rules)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }

        if (empty($_GET)) {
            return false;
        }

        foreach ($rules as $key => $value) {
            $rules = explode('|', $value);
            foreach ($rules as $rule) {
                if (!isset($_GET[$key])) {
                    return false;
                }

                if (str_contains($rule, ':')) {
                    // This means we have something like max:20
                    $rule = explode(':', $rule);

                    // Check if the rule is valid
                    if (!in_array($rule[0], ['min', 'max', 'length', 'in'])) {
                        throw new Exception('Invalid validation rule: ' . $rule[0]);
                    }

                    if ($rule[0] === 'min' && strlen($_GET[$key]) < $rule[1]) {
                        return false;
                    }

                    if ($rule[0] === 'max' && strlen($_GET[$key]) > $rule[1]) {
                        return false;
                    }

                    if ($rule[0] === 'in') {
                        $acceptedValues = explode(',', $rule[1]);
                        if (!exists_in_array($_GET[$key], $acceptedValues)) {
                            return false;
                        }
                    }
                }
                if ($rule === 'required' && empty($_GET[$key])) {
                    return false;
                }
                if ($rule === 'numeric' && !is_numeric($_GET[$key])) {
                    return false;
                } else {
                    // Checks for comma's and replaces them with dots
                    $_GET[$key] = str_replace(',', '.', $_GET[$key]);
                }
                if ($rule === 'email' && !filter_var($_GET[$key], FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
            }
        }

        return true;
    }
}

if (! function_exists('validate_request')) {
    /**
     * Validates the given request data.
     *
     * @throws Exception
     */
    function validate_request(): ?string
    {
        if (empty($_SERVER)) {
            throw new Exception('Something went wrong with the request');
        }

        if (!isset($_SERVER['REQUEST_METHOD'])) {
            throw new Exception('Something went wrong with the request');
        }

//        if (!isset($_SERVER['CONTENT_TYPE'])) {
//            throw new Exception('Something went wrong with the request');
//        }

        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            throw new Exception('Something went wrong with the request');
        }

        return true;
    }
}

if (! function_exists('set_pagination_data')) {
    /**
     * Sets the pagination data.
     */
    function set_pagination_data($data): void
    {
        $_SESSION[$_ENV['APP_NAME']]['pagination'] = $data;
    }
}

if (! function_exists('pagination')) {
    /**
     * Gets the pagination data.
     */
    function pagination($type, $raw = true)
    {
        $acceptedTypes = [
            'buttons',
            'current_page',
            'total_pages',
            'page_from_total',
            'total_items',
        ];

        if (is_array($type)) {
            $params = $type['params'] ?? null;
            $type = $type['type'] ?? null;
        }

        if (!in_array($type, $acceptedTypes)) {
            return false;
        }

        if (!isset($_SESSION[$_ENV['APP_NAME']]['pagination'])) {
            return false;
        }

        $data = $_SESSION[$_ENV['APP_NAME']]['pagination'];

        switch($type) {
            case 'buttons':
                // Creates the pagination buttons
                // Example 1,2,3,4 then last button 10, depending on $params['amount']
                $buttons = '';

                // Checks if the current page is not the first page
                if ($data['current_page'] > 1) {
                    $buttons .= '<a href="?page=' . ($data['current_page'] - 1) . '" class="previous-page">Previous</a>';
                }

                // Generate numeric buttons based on $params['amount']
                for ($i = max(1, $data['current_page'] - floor($params['amount'] / 2)); $i <= min($data['page_amount'], $data['current_page'] + floor($params['amount'] / 2)); $i++) {
                    if ($i == $data['current_page']) {
                        $buttons .= '<span class="current">' . $i . '</span>';
                    } else {
                        $buttons .= '<a href="?page=' . $i . '">' . $i . '</a>';
                    }
                }

                // Checks if the current page is not the last page
                if ($data['current_page'] < $data['page_amount']) {
                    $buttons .= '<a href="?page=' . ($data['current_page'] + 1) . '" class="next-page">Next</a>';
                }

                return $buttons;
                break;
            case 'current_page':
                return $data['current_page'];
                break;
            case 'total_pages':
                return $data['page_amount'];
                break;
            case 'page_from_total':
                if ($raw) {
                    return 'Showing page ' . $data['current_page'] . ' of ' . $data['page_amount'];
                }
                // Returns data with html markup
                return '<p class="pagination-page-from-total">Showing page ' . $data['current_page'] . ' of ' . $data['page_amount'] . '</p>';
                break;
            case 'total_items':
                return $data['total'];
                break;
            default:
                return false;
                break;
        }
    }
}

if (! function_exists('log_action')) {
    /**
     * Logs an action.
     *
     * @throws Exception
     */
    function log_action($server)
    {
        // Log the user agent, requested page, request method, ip address to database
        $log = new Log();
        
        switch($server['REQUEST_METHOD']) {
            case 'GET':
                $payload = $_GET;
                break;
            case 'POST':
                $payload = file_get_contents('php://input');
                break;
            case 'PUT':
                $payload = file_get_contents('php://input');
                break;
            case 'DELETE':
                $payload = file_get_contents('php://input');
                break;
            default:
                $payload = null;
                break;
        }

        $log->userAgent = $server['HTTP_USER_AGENT'] ?? 'Unkown';
        $log->requestedPage = $server['REQUEST_URI'] ?? null;
        $log->requestMethod = $server['REQUEST_METHOD'] ?? null;
        if (is_array($payload)) {
            foreach ($payload as $key => $value) {
                if (str_contains($key, 'pass')) {
                    $payload[$key] = '***';
                }
            }
        }
        $log->payload = $payload;
        $log->ipAddress = $server['REMOTE_ADDR'] ?? null;
        $log->statusCode = http_response_code() ?? $server['REDIRECT_STATUS'] ?? null;
        $log->userId = null;

        // Checks if the Auth class exists
        if (class_exists('Auth')) {
            $log->userId = Auth::id() ?? null;
        }

        if (isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] != 200) {
            $log->saveToDatabase();
        } else {
            $log->saveToFile();
        }
    }
}

if (! function_exists('setup_server')) {
    function setup_server()
    {
        ini_set( 'session.cookie_httponly', 1 );
        ini_set( 'session.cookie_secure', 1 );
        ini_set( 'expose_php', 0 );
        set_headers();
        // session_name('Veloz');
        session_start();
    }
}

if (! function_exists('set_headers')) {
    function set_headers()
    {
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Frame-Options: DENY');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        header('Referrer-Policy: no-referrer');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com/; img-src \'self\' data: https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; font-src \'self\' https://fonts.gstatic.com/; frame-src https://www.google.com/recaptcha/; object-src \'none\';');
        header('X-Powered-By: Canvas-IT');
        // header('Server: Basement');
    }
}

if (! function_exists('veloz_error_handler')) {
    function veloz_error_handler($errno, $errstr, $errfile, $errline)
    {
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error</title>
            <style>
                body {
                    background-color: #1c2331;
                    color: #ffffff;
                    font-family: monospace;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
        
                .container {
                    max-width: 600px;
                    text-align: center;
                }
        
                .error-box {
                    background-color: #34495e;
                    color: #ffffff;
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                }
        
                .error-box h3 {
                    margin-top: 0;
                }
        
                .error-box p {
                    margin: 10px 0;
                }
        
                .version {
                    font-size: 12px;
                    margin-top: 20px !important;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-box">
                    <h3>Error</h3>
                    <p><strong>Error number:</strong> {$errno}</p>
                    <p><strong>Error message:</strong> {$errstr}</p>
                    <p><strong>Error file:</strong> {$errfile}</p>
                    <p><strong>Error line:</strong> {$errline}</p>
                    <p class="version">Veloz version: {vf_version}</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
        
        echo $html;
        exit();
    }
}

if (! function_exists('veloz_exception_handler')) {
    function veloz_exception_handler($e)
    {
        $errorMessage = $e->getMessage();
        $errorFile = $e->getFile();
        $errorLine = $e->getLine();
        $stackTrace = $e->getTraceAsString();

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Exception</title>
            <style>
                body {
                    background-color: #1c2331;
                    color: #ffffff;
                    font-family: monospace;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }

                .container {
                    max-width: 85vw;
                    text-align: center;
                }

                .exception-box {
                    background-color: #34495e;
                    color: #ffffff;
                    padding: 20px;
                    border-radius: 5px;
                    margin: 10px 0;
                }

                .exception-box h3 {
                    margin-top: 0;
                }

                .exception-box p {
                    margin: 10px 0;
                }

                .stack-trace-container {
                    background-color: #000000;
                    color: #ffffff;
                    padding: 10px;
                    border-radius: 5px;
                    margin-top: 20px;
                    max-height: 200px;
                    overflow-y: auto;
                    text-align: left;
                }

                .stack-trace {
                    white-space: pre;
                }

                .version {
                    font-size: 12px;
                    margin-top: 20px !important;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="exception-box">
                    <h3>Exception</h3>
                    <p><strong>Exception message:</strong> $errorMessage</p>
                    <p><strong>Exception file:</strong> $errorFile</p>
                    <p><strong>Exception line:</strong> $errorLine</p>
                    <p><strong>Stack trace:</strong></p>
                    <div class="stack-trace-container">
                        <pre class="stack-trace">$stackTrace</pre>
                    </div>
                    <p class="version">Veloz version: {vf_version}</p>
                </div>
            </div>
        </body>
        </html>
        HTML;

        echo $html;
        exit();
    }
}

if (! function_exists('process_response')) {
    /**
     * Sets an exception.
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    function process_response(string $message, string $type, $page, $fade = false): bool|string
    {
        set_exception($message, $type, $fade);
        redirect($page);
    }
}


if (! function_exists('server_root')) {
    /**
     * Returns the server root.
     *
     * @return string
     */
    function server_root()
    {
        return $_SERVER['DOCUMENT_ROOT'];
    }
}

if (! function_exists('format_datetime')) {
    /**
     * Formats a datetime.
     *
     * @param string | array $param
     * @param $format
     */
    function format_datetime(string|array $param, $format, $keys = null)
    {
        if (is_array($param)) {
            if (!$keys) {
                return false;
            }

            if (!is_array($keys)) {
                $keys = [$keys];
            }

            // Formats all given keys in the array to the given format
            foreach ($param as $key => &$value) {
                foreach ($keys as $val) {
                    $value[$val] = date($format, strtotime($value[$val]));
                }
            }
            
            return $param;
        }

        // Formats the given datetime to the given format
        return date($format, strtotime($param));
    }
}

if (! function_exists('time_in_seconds')) {
    /**
     * Calculates the time in seconds from a given start and end time.
     *
     * @param $time
     * @return false|int
     */
    function time_in_seconds($start, $end)
    {
        return round((strtotime($end) - strtotime($start)), 2);
    }
}

if (! function_exists('get_total')) {
    function get_total($param, $keys = null)
    {
        if (!$keys) {
            return false;
        }
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $total = 0;

        // Use array_reduce to sum all the values of the given keys
        array_reduce($param, function ($carry, $item) use ($keys, &$total) {
            foreach ($keys as $key) {
                $total += $item[$key];
            }
        });

        return $total;
    }
}

if (! function_exists('average_month')) {
    /**
     * Calculates the average of anything per month.
     *
     * @return float|int
     */
    function average_month($param, $value, $date)
    {
        $total = 0;
        $count = 0;
        foreach ($param as $key => $val) {
            if (date('m-Y', strtotime($val[$date])) === date('m-Y')) {
                $total += $val[$value];
                $count++;
            }
        }

        return round($total / $count,2);
    }
}

if (! function_exists('amount_month')) {
    function amount_month($param, $value, $date)
    {
        $filtered_param = array_filter($param, function ($element) use ($date) {
            return date('m-Y', strtotime($element[$date])) === date('m-Y');
        });

        $count = count($filtered_param);

        $total = array_reduce($filtered_param, function($carry, $item) use ($value) {
            return $carry + $item[$value];
        }, 0);

        return round($total / $count, 2);
    }
}

if (! function_exists('average_year')) {
    /**
     * Calculates the average of anything per year.
     *
     * @return void
     */
    function average_year($param)
    {

    }
}

if (! function_exists('get_month')) {
    /**
     * Gets the month from a given date.
     *
     * @param $date
     * @return false|string
     */
    function get_month($date)
    {
        return date('F', strtotime($date));
    }
}

if (! function_exists('get_year')) {
    /**
     * Gets the year from a given date.
     *
     * @param $date
     * @return false|string
     */
    function get_year($date)
    {
        return date('Y', strtotime($date));
    }
}

if (! function_exists('nullable')) {
    /**
     * Allows us to prevent errors when attempting to access an array key on a null array
     *
     */
    function nullable($value)
    {
        return $value;
    }
}

if (! function_exists('f')) {
    /**
     * Filters data by using htmlspecialchars()
     *
     */
    function f($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('filter_by_month')) {
    function filter_by_month($param, $date, $key = false)
    {
        $result = [];

        if (!$key) {
            foreach ($param as $p) {
                if (str_starts_with($p['created_at'], $date)) {
                    $result[] = $p;
                }
            }
        }

        return $result;
    }
}

if (! function_exists('try_unset')) {
    function try_unset($value)
    {
        if (isset($value)) {
            unset($value);
        }
    }
}

if (! function_exists('veloz')) {
    function veloz_session($key)
    {
        // Key can be passed like 'user' but also 'user.id' with infinite depth options
        $keys = explode('.', $key);
        $session = $_SESSION[$_ENV['APP_NAME']] ?? 'example';

        foreach ($keys as $key) {
            if (isset($session[$key])) {
                $session = $session[$key];
            } else {
                return null;
            }
        }

        return $session;
    }
}

if (! function_exists('veloz_session_set')) {
    function veloz_session_set($key, $value)
    {
        $_SESSION[$_ENV['APP_NAME']][$key] = $value;
    }
}

if (! function_exists('unset_user')) {
    function unset_user()
    {
        unset($_SESSION[$_ENV['APP_NAME']]['user']);
    }
}

if (! function_exists('unset_cookies')) {
    function unset_cookies()
    {
        setcookie("remember_token", "", time()-(60*60*24*7),"/");
        unset($_COOKIE['remember_token']);
    }
}

if (! function_exists('user')) {
    function user($key)
    {
        if (isset($_SESSION[$_ENV['APP_NAME']]['user'][$key])) {
            return ucwords($_SESSION[$_ENV['APP_NAME']]['user'][$key]);
        }

        return '';
    }
}

/**
 * Saves the session id to the database sessions table
 *
 * @param $key
 * @return mixed
 */
if (! function_exists('store_session_id')) {
    function store_session_id($id, $sessionId, $expirationDate = null)
    {
        $session = new \Veloz\Models\Session();

        $session::destroy($id, $expirationDate);

        $session::store($id, $sessionId, $expirationDate);
    }
}


if (! function_exists('get_session_id')) {
    function get_session_id()
    {
        return veloz_session('user')['sessionId'] ?? null;
    }
}

// if (! function_exists('check_session_id')) {
//     function check_session_id()
//     {
//         $session = new \Veloz\Models\Session();
//         return $session::check(Auth::id(), get_session_id());
//     }
// }

// if (! function_exists('clear_session_id')) {
//     function clear_session_id()
//     {
//         $session = new \Veloz\Models\Session();
//         $session::destroy(Auth::id());
//     }
// }

if (! function_exists('verify_session')) {
    function verify_session()
    {
        $sessionKeys = [
            'user' => [
                'id',
                'name',
                'sessionId',
            ],
        ];

        foreach ($sessionKeys as $key => $value) {
            if (!isset($_SESSION[$_ENV['APP_NAME']][$key])) {
                return false;
            }
            foreach ($value as $item) {
                if (!isset($_SESSION[$_ENV['APP_NAME']][$key][$item])) {
                    return false;
                }
            }
        }

        return true;

    }
}

if (! function_exists('load404')) {
    function load404() 
    {
        $path = dirname(__DIR__, 2) . $_ENV['APP_ROOT'] . 'views/404.php';
    
        // Look for a 404 page in a views folder anywhere inside app
        return [file_exists($path), $path];
    }
}

if (! function_exists('set404')) {
    function set404()
    {
        // Return a 404 response
        http_response_code(404);

        $load404 = load404();
        $loaded = $load404[0];
        $path = $load404[1];

        if ($loaded) {
            include $path;
            return;
        }

        return '404 Not Found';
    }
}

if (! function_exists('echoOutput')) {
    function echoOutput($output, $breaks = 0, $sleep = 0)
    {
        echo PHP_EOL . $output;
        for ($i = 0; $i < $breaks; $i++) {
            echo PHP_EOL;
        }
        sleep($sleep);
    }
}
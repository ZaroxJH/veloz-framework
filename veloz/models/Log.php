<?php

namespace Veloz\Models;

use Veloz\Core\Model;

class Log extends Model
{
    protected string $table = 'logs';

    private string $logPath;
    private string $logFile;

    public string $userAgent;
    public string $requestedPage;
    public string $requestMethod;
    public string $ipAddress;
    public int|null $statusCode;
    public int|null $userId;
    public string $payload;

    private string $date;
    private array $params;

    public function __construct()
    {
        $logPath = $_ENV['LOG_PATH'];

        if (!str_starts_with($logPath, '/')) {
            $logPath = '/' . $logPath;
        }

        if (!str_ends_with($logPath, '/')) {
            $logPath .= '/';
        }

        $this->logPath = $logPath;

        // Checks if logs folder exists
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $this->logPath)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . $this->logPath);
        }
    }

    public function saveToFile()
    {
        $this->setParams();

        $log = json_encode($this->params);
        $log .= PHP_EOL;

        $this->insertToFile($log);
    }

    public function saveToDatabase(): void
    {
        $this->setParams();
        self::insert($this->params);
    }

    private function insertToFile($log)
    {
        $this->date = date('Y-m');
        $this->logFile = server_root() . $this->logPath . $this->date . '.log';

        if (!file_exists($this->logFile)) {
            $this->createLogFile();
        }

        $file = fopen($this->logFile, 'a');

        fwrite($file, $log);
        fclose($file);
    }

    /**
     * Extracts the data of a given log file
     */
    private function extractFromLogFile($logFile): array
    {
        $logFile = fopen($logFile, 'r');
        $logs = [];

        while (!feof($logFile)) {
            $log = fgets($logFile);
            $log = json_decode($log, true);

            if (is_null($log)) {
                continue;
            }

            $logs[] = $log;
        }

        fclose($logFile);

        return $logs;
    }

    private function setParams()
    {
        $this->params = [
            'user_id' => $this->userId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'method' => $this->requestMethod,
            'payload' => $this->payload,
            'url' => $this->requestedPage,
            'status_code' => $this->statusCode,
        ];
    }

    private function createLogFile()
    {
        $file = fopen($this->logFile, 'w');
        fclose($file);
    }
}
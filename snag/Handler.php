<?php namespace yxorP\snag;

use Throwable;

class Handler
{
    private static bool $enableShutdownHandler = true;
    private Client $client;
    private $previousErrorHandler;
    private $previousExceptionHandler;
    private $reservedMemory;
    private string $oomRegex = '/^Allowed memory size of (\d+) bytes exhausted \(tried to allocate \d+ bytes\)/';

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function registerWithPrevious($client = null): static
    {
        return self::register($client);
    }

    public static function register($client = null): static
    {
        if (!$client instanceof Client) {
            $client = Client::make($client);
        }
        $handler = new static($client);
        $handler->registerSnagHandlers(true);
        return $handler;
    }

    protected function registerSnagHandlers($callPrevious)
    {
        $this->registerErrorHandler($callPrevious);
        $this->registerExceptionHandler($callPrevious);
        $this->registerShutdownHandler();
    }

    public function registerErrorHandler($callPrevious)
    {
        $previous = set_error_handler([$this, 'errorHandler']);
        if ($callPrevious) {
            $this->previousErrorHandler = $previous;
        }
    }

    public function registerExceptionHandler($callPrevious)
    {
        $previous = set_exception_handler([$this, 'exceptionHandler']);
        if (!$callPrevious) {
            return;
        }
        if (!is_callable($previous)) {
            $previous = static function ($throwable) {
                throw $throwable;
            };
        }
        $this->previousExceptionHandler = $previous;
    }

    public function registerShutdownHandler()
    {
        $this->reservedMemory = str_repeat(' ', 1024 * 32);
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    public function exceptionHandler($throwable)
    {
        $this->notifyThrowable($throwable);
        if (!$this->previousExceptionHandler) {
            return;
        }
        try {
            call_user_func($this->previousExceptionHandler, $throwable);
            return;
        } catch (Throwable $exceptionFromPreviousHandler) {
        }
        if ($throwable === $exceptionFromPreviousHandler) {
            self::$enableShutdownHandler = false;
            throw $throwable;
        }
        $this->notifyThrowable($exceptionFromPreviousHandler);
    }

    private function notifyThrowable($throwable)
    {
        $report = Report::fromPHPThrowable($this->client->getConfig(), $throwable);
        $report->setSeverity('error');
        $report->setUnhandled(true);
        $report->setSeverityReason(['type' => 'unhandledException']);
        $this->client->notify($report);
    }

    public function errorHandler($errno, $errstr, $errfile = '', $errline = 0)
    {
        if (!$this->client->getConfig()->shouldIgnoreErrorCode($errno)) {
            $report = Report::fromPHPError($this->client->getConfig(), $errno, $errstr, $errfile, $errline);
            $report->setUnhandled(true);
            $report->setSeverityReason(['type' => 'unhandledError', 'attributes' => ['errorType' => ErrorTypes::getName($errno),],]);
            $this->client->notify($report);
        }
        if ($this->previousErrorHandler) {
            return call_user_func($this->previousErrorHandler, $errno, $errstr, $errfile, $errline);
        }
        return false;
    }

    public function shutdownHandler()
    {
        $this->reservedMemory = null;
        if (!self::$enableShutdownHandler) {
            return;
        }
        $lastError = error_get_last();
        if ($lastError !== null && $this->client->getMemoryLimitIncrease() !== null && preg_match($this->oomRegex, $lastError['message'], $matches) === 1) {
            $currentMemoryLimit = (int)$matches[1];
            $newMemoryLimit = $currentMemoryLimit + $this->client->getMemoryLimitIncrease();
            ini_set('memory_limit', (string)$newMemoryLimit);
        }
        if (!is_null($lastError) && ErrorTypes::isFatal($lastError['type']) && !$this->client->getConfig()->shouldIgnoreErrorCode($lastError['type'])) {
            $report = Report::fromPHPError($this->client->getConfig(), $lastError['type'], $lastError['message'], $lastError['file'], $lastError['line'], true);
            $report->setSeverity('error');
            $report->setUnhandled(true);
            $report->setSeverityReason(['type' => 'unhandledException',]);
            $this->client->notify($report);
        }
        $this->client->flush();
    }
}
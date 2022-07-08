<?php namespace Bugsnag;

use BackedEnum;
use Bugsnag\Breadcrumbs\Breadcrumb;
use Bugsnag\DateTime\Date;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Throwable;
use UnitEnum;

class Report
{
    const PAYLOAD_VERSION = HttpClient::NOTIFY_PAYLOAD_VERSION;
    private Configuration $config;
    private array|null|Throwable $originalError;
    private Stacktrace $stacktrace;
    private $previous;
    private string $name;
    private ?string $message;
    private string $severity;
    private ?string $context;
    private ?string $groupingHash;
    private array $metaData = [];
    private array $user = [];
    private array $breadcrumbs = [];
    private string $time;
    private bool $unhandled = false;
    private array $severityReason = [];
    private $session;

    protected function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->time = Date::now();
    }

    public static function fromPHPError(Configuration $config, $code, $message, $file, $line, $fatal = false): static
    {
        $report = new static($config);
        $report->setPHPError($code, $message, $file, $line, $fatal)->setUnhandled(false)->setSeverityReason(['type' => 'handledError']);
        return $report;
    }

    public static function fromPHPThrowable(Configuration $config, $throwable): static
    {
        $report = new static($config);
        $report->setPHPThrowable($throwable)->setUnhandled(false)->setSeverityReason(['type' => 'handledException']);
        return $report;
    }

    public static function fromNamedError(Configuration $config, $name, $message = null): static
    {
        $report = new static($config);
        $report->setName($name)->setMessage($message)->setStacktrace(Stacktrace::generate($config))->setUnhandled(false)->setSeverityReason(['type' => 'handledError']);
        return $report;
    }

    public function setPHPError($code, $message, $file, $line, $fatal = false): static
    {
        $this->originalError = ['code' => $code, 'message' => $message, 'file' => $file, 'line' => $line, 'fatal' => $fatal,];
        if ($fatal) {
            $stacktrace = Stacktrace::fromFrame($this->config, $file, $line);
        } else {
            $stacktrace = Stacktrace::generate($this->config);
        }
        $this->setName(ErrorTypes::getName($code))->setMessage($message)->setSeverity(ErrorTypes::getSeverity($code))->setStacktrace($stacktrace);
        return $this;
    }

    public function setPHPThrowable($throwable): static
    {
        if (!$throwable instanceof Throwable) {
            throw new InvalidArgumentException('The throwable must implement Throwable or extend Exception.');
        }
        $this->originalError = $throwable;
        $this->setName(get_class($throwable))->setMessage($throwable->getMessage())->setStacktrace(Stacktrace::fromBacktrace($this->config, $throwable->getTrace(), $throwable->getFile(), $throwable->getLine()));
        if (method_exists($throwable, 'getPrevious')) {
            $this->setPrevious($throwable->getPrevious());
        }
        return $this;
    }

    public function getOriginalError(): Throwable|array|null
    {
        return $this->originalError;
    }

    public function getStacktrace(): Stacktrace
    {
        return $this->stacktrace;
    }

    protected function setStacktrace(Stacktrace $stacktrace): static
    {
        $this->stacktrace = $stacktrace;
        return $this;
    }

    public function addMetaData(array $metadata): static
    {
        $this->metaData = array_replace_recursive($this->metaData, $metadata);
        $this->metaData = $this->removeNullElements($this->metaData);
        return $this;
    }

    public function addBreadcrumb(Breadcrumb $breadcrumb)
    {
        $data = $breadcrumb->toArray();
        if ($metaData = $this->cleanupObj($breadcrumb->getMetaData(), true)) {
            $data['metaData'] = $metaData;
            if (strlen(json_encode($data)) > Breadcrumb::MAX_SIZE) {
                unset($data['metaData']);
            }
        }
        $this->breadcrumbs[] = $data;
    }

    public function getSummary(): array
    {
        $summary = [];
        $name = $this->getName();
        $message = $this->getMessage();
        if ($name !== $message) {
            $summary['name'] = $name;
        }
        $summary['message'] = $message;
        $summary['severity'] = $this->getSeverity();
        return array_filter($summary);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name): static
    {
        if (is_scalar($name) || (is_object($name) && method_exists($name, '__toString'))) {
            $this->name = (string)$name;
        } else {
            throw new InvalidArgumentException('The name must be a string.');
        }
        if ($this->name === '') {
            $this->name = 'Error';
        }
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage($message): static
    {
        if ($message === null) {
            $this->message = null;
        } elseif (is_scalar($message) || (is_object($message) && method_exists($message, '__toString'))) {
            $this->message = (string)$message;
        } else {
            throw new InvalidArgumentException('The message must be a string.');
        }
        return $this;
    }

    public function getSeverity(): string
    {
        return $this->severity ?: 'warning';
    }

    public function setSeverity($severity): static
    {
        if (in_array($severity, ['error', 'warning', 'info', null], true)) {
            $this->severity = $severity;
        } else {
            throw new InvalidArgumentException('The severity must be either "error", "warning", or "info".');
        }
        return $this;
    }

    public function setSessionData(array $session)
    {
        $this->session = $session;
    }

    #[Pure] public function getErrors(): array
    {
        $errors = [$this->toError()];
        $previous = $this->previous;
        while ($previous) {
            $errors[] = $previous->toError();
            $previous = $previous->previous;
        }
        return $errors;
    }

    public function toArray(): array
    {
        $event = ['app' => $this->config->getAppData(), 'device' => array_merge(['time' => $this->time], $this->config->getDeviceData()), 'user' => $this->getUser(), 'context' => $this->getContext(), 'payloadVersion' => HttpClient::NOTIFY_PAYLOAD_VERSION, 'severity' => $this->getSeverity(), 'exceptions' => $this->exceptionArray(), 'breadcrumbs' => $this->breadcrumbs, 'metaData' => $this->cleanupObj($this->getMetaData(), true), 'unhandled' => $this->getUnhandled(), 'severityReason' => $this->getSeverityReason(),];
        if ($hash = $this->getGroupingHash()) {
            $event['groupingHash'] = $hash;
        }
        if (isset($this->session)) {
            $event['session'] = $this->session;
        }
        return $event;
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function setUser(array $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext($context): static
    {
        $this->context = $context;
        return $this;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function setMetaData(array $metaData, $merge = true): static
    {
        $this->metaData = $merge ? array_merge_recursive($this->metaData, $metaData) : $metaData;
        return $this;
    }

    public function getUnhandled(): bool
    {
        return $this->unhandled;
    }

    public function setUnhandled($unhandled): static
    {
        $this->unhandled = $unhandled;
        return $this;
    }

    public function getSeverityReason(): array
    {
        if (!array_key_exists('type', $this->severityReason)) {
            syslog(LOG_WARNING, 'Severity reason should always have a "type" set');
            $this->severityReason['type'] = 'userSpecifiedSeverity';
        }
        return $this->severityReason;
    }

    public function setSeverityReason(array $severityReason): static
    {
        $this->severityReason = $severityReason;
        return $this;
    }

    public function getGroupingHash(): ?string
    {
        return $this->groupingHash;
    }

    public function setGroupingHash($groupingHash): static
    {
        $this->groupingHash = $groupingHash;
        return $this;
    }

    protected function setPrevious($throwable): static
    {
        if ($throwable) {
            $this->previous = static::fromPHPThrowable($this->config, $throwable);
        }
        return $this;
    }

    protected function removeNullElements($array)
    {
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $array[$key] = $this->removeNullElements($val);
            } elseif (is_null($val)) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    protected function cleanupObj($obj, $isMetaData)
    {
        if (is_null($obj)) {
            return null;
        }
        if (is_array($obj)) {
            $clean = [];
            foreach ($obj as $key => $value) {
                $clean[$key] = $this->shouldFilter($key, $isMetaData) ? '[FILTERED]' : $this->cleanupObj($value, $isMetaData);
            }
            return $clean;
        }
        if (is_string($obj)) {
            return (function_exists('mb_detect_encoding') && !mb_detect_encoding($obj, 'UTF-8', true)) ? utf8_encode($obj) : $obj;
        }
        if (is_object($obj)) {
            if ($obj instanceof UnitEnum) {
                return $this->enumToString($obj);
            }
            return $this->cleanupObj(json_decode(json_encode($obj), true), $isMetaData);
        }
        return $obj;
    }

    protected function shouldFilter($key, $isMetaData): bool
    {
        if (!$isMetaData) {
            return false;
        }
        foreach ($this->config->getFilters() as $filter) {
            if (stripos($key, $filter) !== false) {
                return true;
            }
        }
        foreach ($this->config->getRedactedKeys() as $redactedKey) {
            if (@preg_match($redactedKey, $key) === 1) {
                return true;
            } elseif (Utils::stringCaseEquals($redactedKey, $key)) {
                return true;
            }
        }
        return false;
    }

    protected function exceptionArray()
    {
        $exceptionArray = [$this->exceptionObject()];
        $previous = $this->previous;
        while ($previous) {
            $exceptionArray[] = $previous->exceptionObject();
            $previous = $previous->previous;
        }
        return $this->cleanupObj($exceptionArray, false);
    }

    #[Pure] #[ArrayShape(['errorClass' => "string", 'message' => "null|string", 'stacktrace' => "array|array[]"])] protected function exceptionObject(): array
    {
        return ['errorClass' => $this->name, 'message' => $this->message, 'stacktrace' => $this->stacktrace->toArray(),];
    }

    private function enumToString(UnitEnum $enum): string
    {
        $string = sprintf('%s::%s', get_class($enum), $enum->name);
        if ($enum instanceof BackedEnum) {
            $string .= sprintf(' (%s)', $enum->value);
        }
        return $string;
    }

    #[ArrayShape(['errorClass' => "string", 'errorMessage' => "null|string", 'type' => "string"])] private function toError(): array
    {
        return ['errorClass' => $this->name, 'errorMessage' => $this->message, 'type' => 'php',];
    }
}
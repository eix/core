<?php
/**
 * Eix logging library.
 */

namespace Eix\Services\Log;

use Eix\Core\Application;
use Eix\Core\Exception as CoreException;
use Eix\Core\Settings\Exception as SettingsException;

class Logger
{
    const DEFAULT_ID = 'EixLogger';

    private static $levelNames = array(
        LOG_ERR => 'error',
        LOG_WARNING => 'warning',
        LOG_INFO => 'info',
        LOG_NOTICE => 'notice',
        LOG_DEBUG => 'debug',
    );
    private $id;
    private $isEnabled = true;
    private static $instance;

    /** Cache the error_log location to avoid retrieving it on each log call. */
    private $errorLogFile;

    private $level = LOG_DEBUG;

    /**
     * Creates a logger.
     *
     * @param string $id and ID that helps identifying the logs from this
     * instance if multiple logs are found in the same stream.
     */
    public function __construct($id)
    {
        // Disable errors during testing.
        // TODO: Fix this, there shouldn't be a dependency on EIX_ENV here.
        if ($_ENV["EIX_ENV"] == "test") {
            $this->disable();
        }

        $this->id = $id;
    }

    public function enable() {
        $this->isEnabled = true;
    }

    public function disable() {
        $this->isEnabled = false;
    }

    /**
     * Gets the logger's default instance.
     */
    public static function get()
    {
        if (empty(static::$instance)) {
            static::$instance = new static(self::DEFAULT_ID);
        }

        return static::$instance;
    }

    /**
     * Sets the logger's default instance.
     */
    public static function set(Logger $logger)
    {
        static::$instance = $logger;
    }

    /**
     * Commits a message to application's log.
     */
    private function log($message, $level)
    {
        // Only log if the logger is enabled.
        if (!$this->isEnabled) {
            return;
        }

        if ($level <= $this->level) {
            $requestId = null;
            try {
                $requestID = Application::getCurrent()->getRequestId();
            } catch (CoreException $exception) {
                $requestID = '---';
            }

            $logMessage = sprintf(
                '%s: %s [%s] %s' . PHP_EOL,
                $this->id,
                $requestId ?: '———',
                @self::$levelNames[$level],
                $message
            );

            // Commit the log message.
            if (empty($this->errorLogFile)) {
                // If no log file is specified, use the default PHP location.
                error_log($logMessage);
            } else {
                // Silence the eventual error if the message cannot be written
                // to the log, so that it doesn't interrupt the program.
                @file_put_contents(
                    $this->errorLogFile,
                    $logMessage,
                    FILE_APPEND
                );
            }
        }
    }

    /*
     * Convenience shorthands.
     */
    public function exception(\Throwable $throwable)
    {
        try {
            if (@Application::getSettings()->logging->exceptions) {
                $this->error('[EXCEPTION] ' . $throwable . PHP_EOL . $throwable->getTraceAsString());
            }
        } catch (SettingsException $throwable) {
            // No setting present, throwable will not be logged.
        }
    }

    public function error($message)
    {
        // If the parameter count is greater than 1, then a sprintf-style
        // message is being passed. Format it.
        if (func_num_args() > 1) {
            $parameters = array_slice(func_get_args(), 1);
            $message = vsprintf($message, $parameters);
        }

        $this->log($message, LOG_ERR);
    }

    public function warning($message)
    {
        // If the parameter count is greater than 1, then a sprintf-style
        // message is being passed. Format it.
        if (func_num_args() > 1) {
            $parameters = array_slice(func_get_args(), 1);
            $message = vsprintf($message, $parameters);
        }

        $this->log($message, LOG_WARNING);
    }

    public function info($message)
    {
        // If the parameter count is greater than 1, then a sprintf-style
        // message is being passed. Format it.
        if (func_num_args() > 1) {
            $parameters = array_slice(func_get_args(), 1);
            $message = vsprintf($message, $parameters);
        }

        $this->log($message, LOG_INFO);
    }

    public function debug($message)
    {
        // If the parameter count is greater than 1, then a sprintf-style
        // message is being passed. Format it.
        if (func_num_args() > 1) {
            $parameters = array_slice(func_get_args(), 1);
            $message = vsprintf($message, $parameters);
        }

        $this->log($message, LOG_DEBUG);
    }

    public function dump($variable)
    {
        $this->log(var_export($variable, true), LOG_DEBUG);
    }
}

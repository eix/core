<?php
/**
 * Eix logging library.
 */

namespace Nohex\Eix\Services\Log;

use Nohex\Eix\Core\Application;
use Nohex\Eix\Core\Exception as CoreException;
use Nohex\Eix\Core\Settings\Exception as SettingsException;

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
        $this->id = $id;
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
     * Gets the logger's default instance.
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
        if ($level <= $this->level) {
            // The default log file is named after the application, and lives
            // in the directory specified in the error_log setting.
            if (empty($this->errorLogFile)) {
                $this->errorLogFile = sprintf('%s/%s.log',
                    dirname(ini_get('error_log')),
                    $this->id
                );
            }

            $requestId = null;
            try {
                $requestID = Application::getCurrent()->getRequestId();
            } catch (CoreException $exception) {
                $requestID = '---';
            }

            $logMessage = sprintf(
                '%s %s [%s] %s' . PHP_EOL,
                date('Ymd H:i:s:u', microtime(true)),
                $requestId ?: '———',
                @self::$levelNames[$level],
                $message
            );

            // Silence the eventual error if the message cannot be written
            // to the log, so that it doesn't interrupt the program.
            @file_put_contents(
                $this->errorLogFile,
                $logMessage,
                FILE_APPEND
            );
        }
    }

    /*
     * Convenience shorthands.
     */
    public function exception(\Exception $exception)
    {
        try {
            if (@Application::getSettings()->logging->exceptions) {
                $this->error('[EXCEPTION] ' . $exception . PHP_EOL . $exception->getTraceAsString());
            }
        } catch (SettingsException $exception) {
            // No setting present, exception will not be logged.
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

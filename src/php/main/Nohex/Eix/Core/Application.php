<?php
/**
 * Defines an Eix application.
 */

namespace Nohex\Eix\Core;

use Nohex\Eix\Services\Net\Http\NotAuthenticatedException;
use Nohex\Eix\Core\Responders\Factory as ResponderFactory;
use Nohex\Eix\Core\Settings\Exception as SettingsException;
use Nohex\Eix\Core\Responders\Http\Identity as IdentityResponder;
use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Net\Http\Exception as HttpException;

abstract class Application
{
    const LOCATOR_COOKIE_NAME = 'eix-locator';

    private static $instance;
    private $settings;
    private $logger;
    private $locale;
    private $availableLocales;
    private $requestId;
    private $startTime;

    /**
     * @static
     * @return Application      an instance of the currently running application.
     * @throws RuntimeException
     */
    public static function getCurrent()
    {
        if (self::$instance instanceof Application) {
            return self::$instance;
        } else {
            throw new Exception('No application is running.');
        }
    }

    public function __construct(Settings $settings = null)
    {
        // Keep a reference to the running application.
        self::$instance = $this;

        // Start a session, if possible.
        @session_start();

        // Logging with a generic logger as the application is not set up yet.
        Logger::set(new Logger('eix'));
        Logger::get()->debug('Starting Eix application...');

        if (!defined('DEBUG')) {
            define('DEBUG', @$_SERVER['SERVER_ENV'] == 'DEV');
        }

        if (!defined('TEST')) {
            define('TEST', @$_SERVER['SERVER_ENV'] == 'TEST');
        }

        // Divert PHP errors to custom handlers.
        if (!DEBUG && !TEST) {
            set_error_handler(array($this, 'handleError'));
        }
        set_exception_handler(array($this, 'handleException'));

        try {
            // Load the application's settings.
            if ($settings) {
                $this->settings = $settings;
            } else {
                // No settings specified, try to load from default location.
                $this->settings = new Settings;
            }
            // Ok, ready to go. Start application.
            Logger::get()->info("Application {$this->getId()} started.");
            // Replace the logger with the application's own.
            Logger::set(new Logger($this->getId()));
        } catch (\Exception $exception) {
            $this->fail($exception);
        }
    }

    public function __destruct()
    {
        // If this object is the current application, destroy its reference.
        if ($this == self::$instance) {
            self::$instance = null;
        }
    }

    /**
     * Starts the application's cycle.
     */
    public function run()
    {
        $this->requestId = uniqid();
        $this->startTime = microtime();

        try {
            // Generate a Request object from the data available in the
            // current running environment.
            try {
            $requestFactory = new Requests\Factories\Http;
            $request = $requestFactory->get($this);
                // Set the application locale from the request.
                // TODO: Move into the Request constructor?
                $this->setLocale($request->getLocale());
                // Run the process of responding to the request.
                $this->issueResponse($request);
            } catch (\Exception $exception) {
                // There was an error somewhere during the request
                // creation, or somewhere during the response building
                // process.
                $errorRequest = new Requests\Http\Error;
                $errorRequest->setException($exception);
                $this->issueResponse($errorRequest, $exception);
            }
        } catch (\Exception $exception) {
            // There was an error while trying to fail gracefully.
            // Little else can be done now.
            $this->fail($exception);
        }

        Logger::get()->info($this->getStats());

        // Since the application is being run in an HTTP context, and
        // PHP is the scripting language it is, the application cycle
        // just finishes after the response is done with its tasks.
    }

    /**
     * Obtains a Response to a Request and actions it.
     *
     * @param Request $request the request that a response is needed for.
     */
    private function issueResponse(Request $request, \Exception $exception = null)
    {
        $responder = null;
        if ($exception === null) {
            // No exception, just return a normal responder.
            $responder = ResponderFactory::getResponder($request);
        } else {
            // Any type of exception, just find an error responder.
            $responder = ResponderFactory::getErrorResponder($request, $exception);
        }
        // The responder is asked to produce a Response object, which is then
        // asked to perform whatever actions it takes to issue the response.

        try {
            $response = $responder->getResponse();
        } catch (NotAuthenticatedException $exception) {
            // A resource that needs authentication was hit.

            // Store the current locator, to be able to return to it after
            // signing in.
            $this->keepCurrentLocator($request);
            // Request identification from the user.
            $responder = new IdentityResponder($request);
            $response = $responder->getResponse();
        }
        if ($response instanceof Response) {
            // Provide a hook for last-minute response work.
            $this->alterResponse($response);
            // Issue the response.
            $response->issue();
        } else {
            throw new HttpException('No response available.');
        }
    }

    /**
     * This function should be overridden in descendant classes to provide
     * application-wide response alterations.
     */
    protected function alterResponse(Response &$response)
    {
        // Do nothing.
    }

    /**
     * Stops the application and outputs an error.
     * @param Exception $exception the error that caused the application to
     * stop.
     */
    public function fail(\Exception $exception)
    {
        Logger::get()->exception($exception);
        header('Server error', true, 500);
        echo '<h1>Eix</h1>';
        echo 'The application cannot continue.';
        echo '<blockquote>' . $exception->getMessage() . '</blockquote>';

        if (defined('DEBUG') && DEBUG) {
            echo '<pre>' . $exception->getTraceAsString() . '</pre>';
        }

        die(-1);
    }

    /**
     * Provides access to the current application's settings.
     *
     * @return \Nohex\Eix\Core\Settings the current application's settings;
     */
    public static function getSettings()
    {
        try {
            return self::getCurrent()->settings;
        } catch (Exception $exception) {
            // No application running.
            throw new SettingsException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Eix's way of handling a PHP error is to wrap it in a runtime exception
     * and throw it, so that the standard exception handler can catch it.
     */
    public function handleError($number, $message, $file, $line, array $context)
    {
        // This avoid silenced (@'ed) errors from being reported.
        if (error_reporting()) {
            $message = "PHP error $number: $message\n\tat $file:$line";
            Logger::get()->error($message);
            $this->handleException(new \RuntimeException($message));
        }
    }

    /**
     * Eix custom exceptions.
     * Handle exceptions out of Eix's range.
     * There is HTML here in the event the templating system has failed as well
     * (which is usually why the error is not properly shown).
     */
    public function handleException($exception)
    {
        // Captured application-level exception.
        try {
            // Try to handle gracefully.
            Logger::get()->exception($exception);
            $responder = new Responders\Http\Error;
            $responder->getResponse()->issue();
        } catch (Exception $innerException) {
            $this->fail($innerException);
        }
    }

    /*
     * Generates an ID from the item's file name.
     */
    public static function normalizeFileName($fileName, $withExtension = true)
    {
        // Remove filename's extension.
        $fileName = basename($fileName);
        $extension = substr($fileName, strrpos($fileName, ".") + 1, strlen($fileName) - strrpos($fileName, "."));
        $fileName = substr($fileName, 0, strrpos($fileName, "."));

        // Convert to lowercase.
        $fileName = strtolower($fileName);

        // Convert characters to the range a-z_-
        $fileName = strtr($fileName, "√°√©√≠√≥√∫√†√®√¨√≤√π√§√´√Ø√∂√º√¢√™√Æ√¥√ª√±√ß", "aeiouaeiouaeiouaeiounc");
        $fileName = preg_replace("/([\W]+)/", "_", $fileName);

        if ($withExtension) {
            $fileName .= '.' . $extension;
        }

        return $fileName;
    }

    /**
     * Generates a globally unique identifier.
     * @return string the identifier.
     */
    public static function getNewGuid()
    {
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }

    public function getId()
    {
        return $this->getSettings()->application->id;
    }

    public function getName()
    {
        return $this->getSettings()->application->name;
    }

    /**
     * Sets the locale of the application, if this locale is available.
     * @param string $locale the new locale.
     */
    public function setLocale($locale)
    {
        $availableLocales = $this->getAvailableLocales();

        if (class_exists('\Locale')) {
            $this->locale = \Locale::lookup(
                $availableLocales,
                $locale,
                false,
                $this->getSettings()->locale->default
            );
        }

        // if (empty($this->locale)) {
            $this->locale = $this->getSettings()->locale->default;
        // }

        setlocale(LC_ALL, $this->locale);

        Logger::get()->info("Locale is now {$this->locale}.");
    }

    /**
     * Keeps the currently requested URL, to be able to retrieve it at a later
     * point.
     */
    private function keepCurrentLocator(Request $request)
    {
        // Ensure a location is not already set, to avoid overwriting it.
        if (!@$_COOKIE[self::LOCATOR_COOKIE_NAME]) {
            $currentUrl = $request->getCurrentUrl();
            // Do not keep OpenID callbacks.
            if (!preg_match('#/identity/openid.+#', $currentUrl)) {
                setcookie(
                    self::LOCATOR_COOKIE_NAME,
                    $currentUrl,
                    time() + 300 // Keep it for 5 minutes.
                );
                Logger::get()->debug('Current locator stored: ' . $currentUrl);
            }
        }
    }

    /**
     * Returns the latest stored URL, and clears the record.
     */
    public function popLastLocator()
    {
        $lastLocator = @$_COOKIE[self::LOCATOR_COOKIE_NAME];
        // Check whether a locator is set.
        if ($lastLocator) {
            // There is a locator. Clear its cookie.
            setcookie(
                self::LOCATOR_COOKIE_NAME,
                null,
                time() - 1 // Expire the cookie.
            );
        }

        return $lastLocator;
    }

    /**
     * Gathers all the locales the application can serve.
     */
    private function getAvailableLocales()
    {
        if (empty($this->availableLocales)) {
            // Open the pages directory.
            $pagesDirectory = opendir('../data/pages/');
            while ($directory = readdir($pagesDirectory)) {
                if (($directory != '.') && ($directory != '..')) {
                    $this->availableLocales[] = $directory;
                }
            }
            closedir($pagesDirectory);
        }

        return $this->availableLocales;
    }

    /**
     * Gather some runtime statistics.
     */
    protected function getStats()
    {
        $endTime = microtime() - $this->startTime;
        $stats = sprintf(
            'Request-response cycle finished: %1.3fs'
            . ' - Memory usage: %1.2fMB (peak: %1.2fMB)'
            ,
            $endTime,
            memory_get_usage(true) / 1048576,
            memory_get_peak_usage(true) / 1048576
        );

        return $stats;
    }

    /**
     * This should probably go in Http\Request.
     */
    public function getLocale()
    {
        if (empty($this->locale)) {
            $this->locale = $this->getSettings()->locale->default;
        }

        return $this->locale;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getSalt()
    {
        if (empty($_SESSION['salt'])) {
            $_SESSION['salt'] = sha1(uniqid('eix', true));
        }

        return $_SESSION['salt'];
    }
}

<?php
/**
 * Defines an Eix application.
 */

namespace Eix\Core;

use Eix\Services\Net\Http\NotAuthenticatedException;
use Eix\Core\Responders\Factory as ResponderFactory;
use Eix\Core\Settings\Exception as SettingsException;
use Eix\Core\Responders\Http\Identity as IdentityResponder;
use Eix\Services\Log\Logger;
use Eix\Services\Net\Http\Exception as HttpException;
use Eix\Core\Requests\Http as HttpRequest;

abstract class Application
{
    const LOCATOR_COOKIE_NAME = 'eix-locator';
    const TEXT_DOMAIN_NAME = 'messages';
    const TEXT_DOMAIN_LOCATION = '../data/resources/locale/';

    private static $instance;
    private $settings;
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

    /**
     * Sets the specified application as the currently running one.
     *
     * @param \Eix\Core\Application $application the application to set as
     * the current one.
     */
    public static function setCurrent(Application $application)
    {
        self::$instance = $application;
    }

    public function __construct(Settings $settings = null)
    {
        // If no other application is running, set the newly created as the
        // current application.
        if (empty(self::$instance)) {
            self::setCurrent($this);
        }
        
        // Start a session, if possible.
        @session_start();

        // Logging with a generic logger as the application is not set up yet.
        Logger::set(new Logger('eix'));
        Logger::get()->debug('Starting Eix application...');

        // Divert PHP errors to custom handlers.
        if (@$settings->application->rawErrors !== 'on') {
            set_error_handler(array($this, 'handleError'));
            set_exception_handler(array($this, 'handleException'));
        }

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
            } catch (\Throwable $throwable) {
                // There was an error somewhere during the request
                // creation, or somewhere during the response building
                // process.
                $errorRequest = new Requests\Http\Error;
                $errorRequest->setThrowable($throwable);
                $this->issueResponse($errorRequest, $throwable);
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
            // Set the method to GET for the identity request. POST is reserved
            // for the actual posting of the identity data by the user.
            $request->setMethod(HttpRequest::HTTP_METHOD_GET);
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
    public function alterResponse(Response &$response)
    {
        // Do nothing.
    }

    /**
     * Stops the application and outputs an error.
     * @param Throwable $throwable the error that caused the application to
     * stop.
     */
    public function fail(\Throwable $throwable)
    {
        Logger::get()->exception($throwable);
        header('Server error', true, 500);
        echo '<h1>Eix</h1>';
        echo 'The application cannot continue.';
        echo '<blockquote>' . $throwable->getMessage() . '</blockquote>';

        if (defined('DEBUG') && DEBUG) {
            echo '<pre>' . $throwable->getTraceAsString() . '</pre>';
        }

        die(-1);
    }

    /**
     * Provides access to the current application's settings.
     *
     * @return \Eix\Core\Settings the current application's settings;
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
        // This avoids silenced (@'ed) errors from being reported.
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
    public function handleException(\Throwable $throwable)
    {
        // Captured an application-level exception.
        try {
            // Log the exception.
            Logger::get()->exception($throwable);
            // Try to display a user-friendly message.
            $responder = new Responders\Http\Error;
            $responder->setThrowable($throwable);
            $responder->getResponse()->issue();
            // Stop the application, as it did not handle the exception
            // properly.
            die(-1);
        } catch (\Exception $innerException) {
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
    public function setLocale($locale = null)
    {
        if (class_exists('\Locale')) {
            $this->locale = \Locale::lookup(
                $this->getAvailableLocales(),
                $locale,
                true,
                $this->getSettings()->locale->default
            );
        }

        if (empty($this->locale)) {
            $this->locale = $this->getSettings()->locale->default;
        }

        // Set up locale environment for gettext.
        bindtextdomain(self::TEXT_DOMAIN_NAME, self::TEXT_DOMAIN_LOCATION);
        bind_textdomain_codeset(self::TEXT_DOMAIN_NAME, 'UTF-8');
        textdomain(self::TEXT_DOMAIN_NAME);
        putenv('LANG=' . $this->locale);
        putenv('LC_MESSAGES=' . $this->locale);
        $locale = setlocale(LC_MESSAGES, $this->locale);

        Logger::get()->info(sprintf('Locale is now %s [%s] (domain "%s" at %s)',
            $locale,
            $this->locale,
            self::TEXT_DOMAIN_NAME,
            realpath(self::TEXT_DOMAIN_LOCATION)
        ));
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
            // Do not keep identity URLs.
            if (!preg_match('#/identity.+#', $currentUrl)) {
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
}

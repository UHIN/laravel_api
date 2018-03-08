<?php

namespace uhin\laravel_api;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Class PagerDuty
 *
 * Example usage:
 *   $success = (new PagerDuty)
 *     ->setMessage('This is the message to be sent')
 *     ->setRoutingKey('my routing key')
 *     ->send();
 *
 * @package uhin\laravel_api
 */
class PagerDuty
{

    /** @var null|string */
    private $apiUrl = null;

    /** @var null|string */
    private $apiKey = null;

    /** @var null|string */
    private $integrationKey = null;

    /** @var null|string */
    private $message = null;

    /** @var null|string */
    private $component = null;

    /** @var null|string */
    private $client = null;

    /** @var null|string */
    private $severity = null;

    /** @var null|string */
    private $action = null;


    public function __construct() {
        $this->apiUrl = config('uhin.pager_duty.url');
        $this->apiKey = config('uhin.pager_duty.api_key');
        $this->integrationKey = config('uhin.pager_duty.integration_key');
        $this->client = config('uhin.pager_duty.client');
        $this->severity = config('uhin.pager_duty.severity');
        $this->action = config('uhin.pager_duty.action');
    }

    /**
     * Override the default Pager Duty endpoint URL.
     *
     * @param null|string $apiUrl
     * @return $this
     */
    public function setApiUrl(?string $apiUrl) {
        $this->apiUrl = $apiUrl;
        return $this;
    }

    /**
     * Override the default Pager Duty API Key.
     *
     * @param null|string $apiKey
     * @return $this
     */
    public function setApiKey(?string $apiKey) {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Override the default integration key.
     *
     * @param null|string $integrationKey
     * @return $this
     */
    public function setIntegrationKey(?string $integrationKey) {
        $this->integrationKey = $integrationKey;
        return $this;
    }

    /**
     * Sets the message - This is required in order to send a PagerDuty.
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message) {
        $this->message = $message;
        return $this;
    }

    /**
     * Set the component.
     *
     * @param null|string $component
     * @return $this
     */
    public function setComponent(?string $component) {
        $this->component = $component;
        return $this;
    }

    /**
     * Override the default client.
     *
     * @param null|string $client
     * @return $this
     */
    public function setClient(?string $client) {
        $this->client = $client;
        return $this;
    }

    /**
     * Override the default severity.
     *
     * @param null|string $severity
     * @return $this
     */
    public function setSeverity(?string $severity) {
        $this->severity = $severity;
        return $this;
    }

    /**
     * Override the default severity.
     *
     * @param null|string $action
     * @return $this
     */
    public function setAction(?string $action) {
        $this->action = $action;
        return $this;
    }

    /**
     * Builds the URI string for the current service/application, eg: https://api.dev.uhin.org/
     *
     * @return string
     */
    private function buildClientUrl() {
        // protocol
        $s = &$_SERVER;
        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
        $sp = strtolower($s['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');

        // port
        $port = $s['SERVER_PORT'];
        $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;

        // host
        $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
        $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;

        // build the uri
        return $protocol . '://' . $host . $s['REQUEST_URI'];
    }

    /**
     * Makes the actual call to PagerDuty. This method will return true upon success, and false upon
     * failure.
     *
     * @return bool
     */
    public function send() {

        // message
        $message = $this->message;
        if ($message === null) {
            throw new InvalidArgumentException("PagerDuty message is null - you must call ->setMessage(...) before sending your message");
        }

        // apiUrl
        $apiUrl = $this->apiUrl;
        if ($apiUrl === null) {
            throw new InvalidArgumentException('PagerDuty could not find the api url. Either set the PAGER_DUTY_URL in the .env file or use the ->setApiUrl(...) method');
        }

        // apiKey
        $apiKey = $this->apiKey;
        if ($apiKey === null || strlen($apiKey) <= 0) {
            throw new InvalidArgumentException('PagerDuty error: You must specify your PAGER_DUTY_API_KEY in the .env file');
        }

        // integration key
        $integrationKey = $this->integrationKey;
        if ($integrationKey === null || strlen($integrationKey) <= 0) {
            throw new InvalidArgumentException('PagerDuty could not find an integration key to use. Either set the PAGER_DUTY_INTEGRATION_KEY in the .env file or use the ->setIntegrationKey(...) method');
        }

        // component
        $component = $this->component;
        if ($component === null) {
            $component = null; // component can be null
        }

        // client
        $client = $this->client;
        if ($client === null) {
            throw new InvalidArgumentException('PagerDuty could not find the client. Either set the PAGER_DUTY_CLIENT in the .env file or use the ->setClient(...) method');
        }

        // severity
        $severity = $this->severity;
        if ($severity === null) {
            $severity = 'info';
        }

        // action
        $action = $this->action;
        if ($action === null) {
            $action = 'trigger';
        }

        // Gather all of the server variables
        $details = [];
        foreach ($_SERVER as $key => $value) {
            $details[$key] = $value;
        }

        // Build some other information
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $details['backtrace'] = $backtrace;
        $client_url = $this->buildClientUrl();

        // Create the payload
        $args = [
            'payload' => [
                'summary'           => $message,
                'timestamp'         => (new DateTime)->format('Y-m-d\TH:i:s.uO'),
                'source'            => $client . '.' . gethostname(),
                'severity'          => $severity,
                'component'         => $component,
                'group'             => $client,
                'classs'            => $backtrace[1]['function'],
                'custom_details'    => $details,
            ],
            'routing_key'   => $integrationKey,
            'event_action'  => $action,
            'client'        => $client,
            'client_url'    => $client_url,
        ];
        $result = null;

        try {
            // Build the headers
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token token=' . $apiKey,
            ];

            // Build the curl request
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $apiUrl);
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($handle, CURLOPT_HEADER, true);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($args));
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

            // Send the request
            $result = curl_exec($handle);
            $info  = curl_getinfo($handle);

            if ($info['http_code'] !== 202 && $info['http_code'] !== 201) {
                throw new Exception("Status code: {$info['http_code']} returned from PagerDuty");
            }

            /** @noinspection PhpUndefinedMethodInspection */
            Log::debug("Event triggered in PagerDuty. " . $result);
            return true;
        } catch (Exception $e) {
            $message = "Error in " . __FILE__ . " line " . __LINE__ .
                " - Failed to create incident. " .
                $e->getMessage() .
                json_encode($args, JSON_PRETTY_PRINT) .
                json_encode(json_decode($result), JSON_PRETTY_PRINT);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::error($message);
            return false;
        }
    }

}

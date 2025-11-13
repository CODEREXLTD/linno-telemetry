<?php
/**
 * OpenPanel Driver Implementation
 *
 * @package CodeRex\Telemetry
 * @since 1.0.0
 */

namespace CodeRex\Telemetry\Drivers;

/**
 * Class OpenPanelDriver
 *
 * Implements DriverInterface for OpenPanel analytics platform.
 * Handles HTTPS communication, authentication, and error handling.
 *
 * @since 1.0.0
 */
class OpenPanelDriver implements DriverInterface {
	/**
	 * OpenPanel API endpoint URL
	 *
	 * @since 1.0.0
	 */
	private const API_ENDPOINT = 'https://analytics.linno.io/api/track';

	/**
	 * API key for authentication
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private string $apiKey;


    /**
     * API Secret for authentication
     *
     * @var string
     * @since 1.0.0
     */
    private string $apiSecret;

	/**
	 * Last error message
	 *
	 * @var string|null
	 * @since 1.0.0
	 */
	private ?string $lastError = null;

	/**
	 * Send event data to OpenPanel
	 *
	 * @param string $event The event name.
	 * @param array  $properties The event properties.
	 *
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	public function send( string $event, array $properties ): bool {
		$this->lastError = null;

		$payload = array(
			'event'      => $event,
			'properties' => $properties,
		);

		return $this->makeRequest( $payload );
	}

	/**
	 * Set the API key for authentication
	 *
	 * @param string $apiKey The API key.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function setApiKey( string $apiKey ): void {
		$this->apiKey = $apiKey;
	}


    public function setApiSecret( string $apiSecret ): void {
        $this->apiSecret = $apiSecret;
    }

	/**
	 * Get the last error message
	 *
	 * @return string|null The last error message or null if no error.
	 * @since 1.0.0
	 */
	public function getLastError(): ?string {
		return $this->lastError;
	}

	/**
	 * Build HTTP headers for the request
	 *
	 * @return array The headers array.
	 * @since 1.0.0
	 */
	private function buildHeaders(): array {
		return array(
			'openpanel-client-id'     => $this->apiKey,
			'openpanel-client-secret' => $this->apiSecret,
			'Content-Type'            => 'application/json',
			'user-agent'              => $this->getClientUserAgent(),
			'x-client-ip'             => $this->getClientIp(),
		);
	}

	/**
	 * Get the client's User-Agent string from their browser
	 *
	 * @return string The client's User-Agent string.
	 * @since 1.0.0
	 */
	private function getClientUserAgent(): string {
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		return sprintf(
			'coderex-telemetry/%s (WordPress/%s; PHP/%s; %s)',
			'1.0.0',
			get_bloginfo( 'version' ),
			PHP_VERSION,
			parse_url( home_url(), PHP_URL_HOST )
		);
	}

	/**
	 * Get the client's IP address
	 *
	 * @return string The client's IP address.
	 * @since 1.0.0
	 */
	private function getClientIp(): string {
		// Check for IP from various sources (in order of reliability)
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP',        // Nginx proxy
			'HTTP_X_FORWARDED_FOR',  // Standard proxy header
			'HTTP_CLIENT_IP',        // Shared internet/ISP IP
			'REMOTE_ADDR',           // Direct connection
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

				// Handle comma-separated IPs (X-Forwarded-For can have multiple)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip_list = explode( ',', $ip );
					$ip      = trim( $ip_list[0] ); // Get the first (original client) IP
				}

				// Validate IP address
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}

				// If validation fails but it's still an IP format, return it anyway
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		// Fallback to localhost if no IP found
		return '127.0.0.1';
	}

	/**
	 * Convert array to stdClass recursively
	 *
	 * OpenPanel requires properties to be an object, not an array.
	 * This method converts associative arrays to stdClass objects recursively.
	 *
	 * @param mixed $data The data to convert.
	 *
	 * @return mixed Converted data with arrays as objects.
	 * @since 1.0.0
	 */
	private function arrayToObject( $data ) {
		if ( is_array( $data ) ) {
			// Check if it's an associative array or indexed array
			if ( empty( $data ) || array_keys( $data ) === range( 0, count( $data ) - 1 ) ) {
				// Indexed array - keep as array but convert nested values
				return array_map( array( $this, 'arrayToObject' ), $data );
			} else {
				// Associative array - convert to object
				return (object) array_map( array( $this, 'arrayToObject' ), $data );
			}
		}
		return $data;
	}

	/**
	 * Make HTTPS request to OpenPanel API using cURL
	 *
	 * @param array $payload The payload to send.
	 *
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	private function makeRequest( array $payload ): bool {
		
		$ch = curl_init( self::API_ENDPOINT );
		$body = wp_json_encode( array(
			'type'    => 'track',
			'payload' => array(
				'name'       => $payload['event'],
				'properties' => $this->arrayToObject( $payload['properties'] ),
			),
		) );

		// Build headers for cURL
		$headers = array();
		foreach ( $this->buildHeaders() as $key => $value ) {
			$headers[] = "$key: $value";
		}

		// Set cURL options
		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_TIMEOUT        => 5,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_FOLLOWLOCATION => false,
		) );

		// Execute the request
		$response = curl_exec( $ch );
		$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$error    = curl_error( $ch );
		$errno    = curl_errno( $ch );

		curl_close( $ch );
		return $this->handleResponse( $response, $httpCode, $error, $errno );
	}

	/**
	 * Handle the API response from cURL
	 *
	 * @param mixed  $response The response body from cURL.
	 * @param int    $httpCode The HTTP status code.
	 * @param string $error The cURL error message.
	 * @param int    $errno The cURL error number.
	 *
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	private function handleResponse( $response, int $httpCode, string $error, int $errno ): bool {
		// Check for cURL errors
		if ( $errno !== 0 ) {
			$this->lastError = sprintf( 'cURL error (%d): %s', $errno, $error );
			return false;
		}

		// Check HTTP status code
		if ( $httpCode < 200 || $httpCode >= 300 ) {
			$this->lastError = sprintf(
				'HTTP %d: %s',
				$httpCode,
				$response ? $response : 'Unknown error'
			);
			return false;
		}

		return true;
	}
}

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
		);
	}

	/**
	 * Get the client's User-Agent string
	 *
	 * @return string The client's User-Agent string.
	 * @since 1.0.0
	 */
	private function getClientUserAgent(): string {
		return sprintf(
			'coderex-telemetry/%s; WordPress/%s; PHP/%s',
			'1.0.0',
			get_bloginfo( 'version' ),
			PHP_VERSION
		);
	}

	/**
	 * Make HTTPS request to OpenPanel API using wp_remote_post
	 *
	 * @param array $payload The payload to send.
	 *
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	private function makeRequest( array $payload ): bool {
		$body = array(
			'type'    => 'track',
			'payload' => array(
				'name'       => $payload['event'],
				'properties' => (object) $payload['properties'],
			),
		);

		$args = array(
			'body'        => wp_json_encode( $body ),
			'headers'     => $this->buildHeaders(),
			'timeout'     => 5,
			'redirection' => 5,
			'blocking'    => true,
			'sslverify'   => true,
		);

		$response = wp_remote_post( self::API_ENDPOINT, $args );

		if ( is_wp_error( $response ) ) {
			$this->lastError = $response->get_error_message();
			return false;
		}

		$httpCode = wp_remote_retrieve_response_code( $response );

		if ( $httpCode < 200 || $httpCode >= 300 ) {
			$this->lastError = sprintf(
				'HTTP %d: %s',
				$httpCode,
				wp_remote_retrieve_response_message( $response )
			);
			return false;
		}

		return true;
	}
}

<?php

namespace Flagrow\Flarum\Api;

use Flagrow\Flarum\Api\Response\Factory;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class Flarum
{
	/**
	 * @var Cache
	 */
	protected static $cache;

	/**
	 * @var bool
	 */
	protected $authorized = false;

	/**
	 * @var Fluent
	 */
	protected $fluent;

	/**
	 * @var Guzzle
	 */
	protected $rest;

	/**
	 * Whether to enforce specific markup/variables setting.
	 * @var bool
	 */
	protected $strict = true;

	/**
	 * @return Cache
	 */
	public static function getCache(): Cache
	{
		return self::$cache;
	}

	/**
	 * Flarum constructor.
	 * @param       $host          Full FQDN hostname to your Flarum forum, eg http://example.com/forum
	 * @param array $authorization Holding either "token" or "username" and "password" as keys.
	 */
	public function __construct( $host, array $authorization = [] )
	{
		$this->rest = new Guzzle( [
				'base_uri' => "$host/api/",
				'headers' => $this->requestHeaders( $authorization )
			]
		);
		$this->fluent();
		static::$cache = new Cache( new ArrayStore );
	}

	/**
	 * {@inheritdoc}
	 */
	public function __call( $name, $arguments )
	{
		return call_user_func_array( [$this->fluent, $name], $arguments );
	}

	/**
	 * @return Fluent
	 */
	public function getFluent(): Fluent
	{
		return $this->fluent;
	}

	/**
	 * @return bool
	 */
	public function isAuthorized(): bool
	{
		return $this->authorized;
	}

	/**
	 * @return bool
	 */
	public function isStrict(): bool
	{
		return $this->strict;
	}

	/**
	 * @return null
	 */
	public function request()
	{
		$method = $this->fluent->getMethod();
		/** @var ResponseInterface $response */
		$response = $this->rest->{$method}( (string)$this->fluent, $this->getVariablesForMethod() );
		if( $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ) {
			// Reset the fluent builder for a new request.
			$this->fluent();
			return Factory::build( $response );
		}
	}

	/**
	 * @param bool $strict
	 * @return Flarum
	 */
	public function setStrict( bool $strict ): Flarum
	{
		$this->strict = $strict;
		return $this;
	}

	/**
	 * @return Flarum
	 */
	protected function fluent(): Flarum
	{
		$this->fluent = new Fluent( $this );
		return $this;
	}

	/**
	 * @return array
	 */
	protected function getVariablesForMethod(): array
	{
		$variables = $this->fluent->getVariables();
		if( empty( $variables )) {
			return [];
		}
		switch( $this->fluent->getMethod() ) {
			case 'get':
				return $variables;
				break;
			default:
				return [
					'json' => ['data' => $variables]
				];
		}
	}

	/**
	 * @param array $authorization
	 * @return array
	 */
	protected function requestHeaders( array $authorization = [] )
	{
		$headers = [
			'Accept' => 'application/vnd.api+json, application/json',
			'User-Agent' => 'Flagrow Api Client'
		];
		$token = Arr::get( $authorization, 'token' );
		if( $token ) {
			$this->authorized = true;
			Arr::set( $headers, 'Authorization', "Token $token" );
		}
		return $headers;
	}
}

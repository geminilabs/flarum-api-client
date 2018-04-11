<?php

namespace Flagrow\Flarum\Api;

use Flagrow\Flarum\Api\Cache;
use Flagrow\Flarum\Api\Fluent;
use Flagrow\Flarum\Api\Resource\Collection;
use Flagrow\Flarum\Api\Resource\Item;
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
	protected $guzzle;

	/**
	 * Whether to enforce specific markup/variables setting.
	 * @var bool
	 */
	protected $strict = true;

	/**
	 * @param $host Full FQDN hostname to your Flarum forum, eg http://example.com/forum
	 * @param array $authorization Holding either "token" or "identification" and "password" as keys.
	 */
	public function __construct( string $host, array $authorization = [] )
	{
		$this->guzzle = new Guzzle([
			'base_uri' => $host.'/api/',
			'headers' => $this->requestHeaders( $authorization )
		]);
		$this->fluent();
		static::$cache = new Cache( new ArrayStore );
	}

	/**
	 * {@inheritdoc}
	 */
	public function __call( string $name, array $arguments = [] )
	{
		return call_user_func_array( [$this->fluent, $name], $arguments );
	}

	/**
	 * @return Cache
	 */
	public static function getCache(): Cache
	{
		return self::$cache;
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
	 * @return void|Item|Collection
	 */
	public function request()
	{
		$method = $this->fluent->getMethod();
		$response = call_user_func( [$this->guzzle, $method],
			(string)$this->fluent,
			$this->getVariablesForMethod()
		);
		if( $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ) {
			$this->resetFluent();
			return Factory::build( $response );
		}
	}

	/**
	 * @return Flarum
	 */
	public function setStrict( bool $strict ): Flarum
	{
		$this->strict = $strict;
		return $this;
	}

	/**
	 * @return array
	 */
	protected function getVariablesForMethod(): array
	{
		$method = $this->fluent->getMethod();
		$variables = $this->fluent->getVariables();
		if( $method == 'get' || empty( $variables )) {
			return $variables;
		}
		return ['json' => ['data' => $variables]];
	}

	/**
	 * @return array
	 */
	protected function requestHeaders( array $authorization = [] ): array
	{
		$headers = [
			'Accept' => 'application/vnd.api+json, application/json',
			'User-Agent' => 'Flagrow Api Client'
		];
		if( $token = Arr::get( $authorization, 'token' )) {
			$this->authorized = true;
			Arr::set( $headers, 'Authorization', "Token $token" );
		}
		return $headers;
	}

	/**
	 * @return Flarum
	 */
	protected function resetFluent(): Flarum
	{
		$this->fluent = new Fluent( $this );
		return $this;
	}
}

<?php

namespace Flagrow\Flarum\Api;

use Flagrow\Flarum\Api\Cache;
use Flagrow\Flarum\Api\Fluent;
use Flagrow\Flarum\Api\Resource\Collection;
use Flagrow\Flarum\Api\Resource\Item;
use Flagrow\Flarum\Api\Response\Factory;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Arr;

class Flarum
{
	/**
	 * @var Cache
	 */
	protected static $cache;

	/**
	 * @var GuzzleClient
	 */
	protected $client;

	/**
	 * @var Fluent
	 */
	protected $fluent;

	/**
	 * @var bool
	 */
	protected $isAuthorized = false;

	/**
	 * @var bool
	 */
	protected $isStrict = true;

	/**
	 * @return Cache
	 */
	public static function getCache(): Cache
	{
		return static::$cache;
	}

	/**
	 * @param string $host Full FQDN hostname to your Flarum forum, eg http://example.com/forum
	 * @param array $authorization Holding "token" and "userid" as keys.
	 */
	public function __construct( string $host, array $authorization = [] )
	{
		$this->client = new GuzzleClient([
			'base_uri' => rtrim( $host, '/' ).'/api/',
			'headers' => $this->getRequestHeaders( $authorization ),
		]);
		$this->setFluent();
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
		return $this->isAuthorized;
	}

	/**
	 * Whether to enforce specific markup/variables setting.
	 * @return bool
	 */
	public function isStrict(): bool
	{
		return $this->isStrict;
	}

	/**
	 * @return void|Item|Collection
	 */
	public function request()
	{
		$method = $this->fluent->getMethod();
		$response = call_user_func( [$this->client, $method],
			(string)$this->fluent,
			$this->getVariablesForMethod()
		);
		if( $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ) {
			$this->setFluent();
			return Factory::build( $response );
		}
	}

	/**
	 * @return Flarum
	 */
	public function setStrict( bool $isStrict ): Flarum
	{
		$this->isStrict = $isStrict;
		return $this;
	}

	/**
	 * @return array
	 */
	protected function getRequestHeaders( array $authorization = [] ): array
	{
		$authorization += ['token' => '', 'userid' => 1];
		$headers = [
			'Accept' => 'application/vnd.api+json, application/json',
			'User-Agent' => 'Flagrow Api Client',
		];
		if( $token = $authorization['token'] ) {
			$headers['Authorization'] = 'Token '.$token.';userId='.$authorization['userid'];
			$this->isAuthorized = true;
		}
		return $headers;
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
	 * @return Flarum
	 */
	protected function setFluent(): Flarum
	{
		$this->fluent = new Fluent( $this );
		return $this;
	}
}

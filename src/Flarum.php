<?php

namespace Flagrow\Flarum\Api;

use Flagrow\Flarum\Api\Cache;
use Flagrow\Flarum\Api\Fluent;
use Flagrow\Flarum\Api\Resource\Resource;
use Flagrow\Flarum\Api\Response\Factory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Arr;

/**
 * @method bool isAuthorized()
 * @method bool isStrict()
 */
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
	 * @return mixed|void
	 */
	public function __get( string $property )
	{
		if( property_exists( $this, $property )) {
			return $this->{$property};
		}
	}

	/**
	 * @return true|object|Resource
	 * @throws RequestException
	 */
	public function request()
	{
		try {
			$response = call_user_func( [$this->client, $this->fluent->getMethod()],
				(string)$this->fluent,
				$this->getVariablesForMethod()
			);
		}
		catch( RequestException $e ) {
			$response = $e->getResponse();
		}
		if( floor( $response->getStatusCode() / 100 ) == 2 ) {
			$this->resetFluent();
			return Factory::build( $response );
		}
		return (object)json_decode( $response->getBody() );
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
		if( (string)$this->fluent !== 'token' ) {
			$variables = ['data' => $variables];
		}
		return ['json' => $variables];
	}

	/**
	 * @return Flarum
	 */
	protected function resetFluent(): Flarum
	{
		return $this->setFluent();
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

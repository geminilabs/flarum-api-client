<?php

namespace Flagrow\Flarum\Api;

use Flagrow\Flarum\Api\Exceptions\UnauthorizedRequestMethodException;
use Flagrow\Flarum\Api\Flarum;
use Psr\Http\Message\ResponseInterface;

/**
 * @method ResponseInterface delete( string $uri, array $options = [] )
 * @method ResponseInterface get( string $uri, array $options = [] )
 * @method ResponseInterface head( string $uri, array $options = [] )
 * @method ResponseInterface patch( string $uri, array $options = [] )
 * @method ResponseInterface post( string $uri, array $options = [] )
 * @method ResponseInterface put( string $uri, array $options = [] )
 * @method true|object|Resource request()
 */
class Fluent
{
	const METHODS = [
		'delete', 'get', 'head', 'patch', 'post', 'put',
	];

	const METHODS_REQUIRING_AUTHENTICATION = [
		'delete', 'patch', 'post', 'put',
	];

	const PAGINATION = [
		'filter', 'page',
	];

	const TYPES = [
		'discussions', 'users',
	];

	/**
	 * @var Flarum
	 */
	protected $flarum;

	/**
	 * @var array
	 */
	protected $includes = [];

	/**
	 * @var string
	 */
	protected $method = 'get';

	/**
	 * @var array
	 */
	protected $query = [];

	/**
	 * @var array
	 */
	protected $segments = [];

	/**
	 * @var array
	 */
	protected $variables = [];

	public function __construct( Flarum $flarum )
	{
		$this->flarum = $flarum;
	}

	/**
	 * @return mixed
	 */
	public function __call( string $name, array $arguments = [] )
	{
		if( $this->shouldHandleType( $name, $arguments )) {
			return $this->handleType( $name );
		}
		if( $this->shouldSetMethod( $name )) {
			$this->setVariables( $arguments );
			return $this->setMethod( $name );
		}
		if( $this->shouldHandlePagination( $name, $arguments )) {
			return call_user_func_array( [$this, 'handlePagination'],
				array_prepend( $arguments, $name )
			);
		}
		if( method_exists( $this->flarum, $name )) {
			return call_user_func_array( [$this->flarum, $name], $arguments );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function __toString()
	{
		$path = implode( '/', $this->segments );
		if( !empty( array_filter( array_merge( $this->includes, $this->query )))) {
			$path .= '?';
		}
		if( !empty( $this->includes )) {
			$path .= sprintf( 'include=%s&', implode( ',', $this->includes ));
		}
		if( !empty( $this->query )) {
			$path .= http_build_query( $this->query );
		}
		return $path;
	}

	/**
	 * @return object
	 */
	public function authenticate( array $credentials = [] )
	{
		$this->method = 'post';
		$this->segments = ['token'];
		return $this->setVariables( $credentials )->flarum->request();
	}

	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * @return array
	 */
	public function getVariables(): array
	{
		return $this->variables;
	}

	/**
	 * @param string|array $value
	 * @return Fluent
	 */
	protected function handlePagination( string $type, $value ): Fluent
	{
		$this->query[$type] = $value;
		return $this;
	}

	/**
	 * @return Fluent
	 */
	protected function handleType( string $type ): Fluent
	{
		$this->segments[] = $type;
		return $this;
	}

	/**
	 * @return Fluent
	 */
	public function id( int $id ): Fluent
	{
		$this->segments[] = $id;
		return $this;
	}

	/**
	 * @return Fluent
	 */
	public function include( string $include ): Fluent
	{
		$this->includes[] = $include;
		return $this;
	}

	/**
	 * @return Fluent
	 */
	public function offset( int $number ): Fluent
	{
		return $this->handlePagination( 'page[offset]', $number );
	}

	/**
	 * @return Fluent
	 * @throws UnauthorizedRequestMethodException
	 */
	public function setMethod( string $method ): Fluent
	{
		$this->method = strtolower( $method );
		if( !$this->flarum->isAuthorized()
			&& $this->flarum->isStrict()
			&& in_array( $this->method, static::METHODS_REQUIRING_AUTHENTICATION )) {
			throw new UnauthorizedRequestMethodException( $this->method );
		}
		return $this;
	}

	/**
	 * @return Fluent
	 */
	public function setVariables( array $variables = [] ): Fluent
	{
		if( count( $variables ) === 1 && is_array( $variables[0] )) {
			$this->variables = $variables[0];
		}
		else if( !empty( $variables )) {
			$this->variables = $variables;
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	protected function shouldHandlePagination( string $name, array $arguments ): bool
	{
		return in_array( $name, static::PAGINATION ) && count( $arguments ) === 1;
	}

	/**
	 * @return bool
	 */
	protected function shouldHandleType( string $name, array $arguments ): bool
	{
		return in_array( $name, static::TYPES ) && count( $arguments ) === 0;
	}

	/**
	 * @return bool
	 */
	protected function shouldSetMethod( string $name ): bool
	{
		return in_array( $name, static::METHODS );
	}
}

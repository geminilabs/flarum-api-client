<?php

namespace Flagrow\Flarum\Api\Models;

use Flagrow\Flarum\Api\Exceptions\InvalidArgumentException;
use Flagrow\Flarum\Api\Flarum;
use Flagrow\Flarum\Api\Fluent;
use Flagrow\Flarum\Api\Resource\Item;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class Model
{
	/**
	 * @var Flarum
	 */
	protected static $dispatcher;

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @var int|null
	 */
	protected $id;

	/**
	 * @return Item
	 */
	public static function fromResource( Item $item )
	{
		$class = sprintf( '%s\\%s', __NAMESPACE__, Str::camel( Str::singular( $item->type )));
		if( class_exists( $class )) {
			$response = new $class( $item->attributes );
			if( $item->id ) {
				$response->id = $item->id;
			}
			return $response;
		}
		throw new InvalidArgumentException( 'Resource type '.$item->type.' could not be migrated to Model' );
	}

	/**
	 * @return Flarum
	 */
	public static function getDispatcher(): Flarum
	{
		return static::$dispatcher;
	}

	/**
	 * @return void
	 */
	public static function setDispatcher( Flarum $flarum )
	{
		static::$dispatcher = $flarum;
	}

	public function __construct( array $attributes = [] )
	{
		if( Arr::has( $attributes, 'id' )) {
			$this->id = Arr::pluck( $attributes, 'id' );
		}
		$this->attributes = $attributes;
	}

	/**
	 * {@inheritdoc}
	 */
	public function __get( $name )
	{
		return Arr::get( $this->attributes, $name );
	}

	/**
	 * {@inheritdoc}
	 */
	public function __set( $name, $value )
	{
		if( $name === 'id' ) {
			$this->id = $value;
		}
		else {
			$this->attributes[$name] = $value;
		}
	}

	/**
	 * @return array
	 */
	public function attributes(): array
	{
		return $this->attributes;
	}

	/**
	 * @return Fluent
	 */
	public function baseRequest(): Fluent
	{
		$dispatch = call_user_func_array( [static::$dispatcher, $this->type()], [] );
		if( $this->id ) {
			$dispatch->id( $this->id );
		}
		return $dispatch;
	}

	/**
	 * @return mixed
	 */
	public function delete()
	{
		if( $this->id ) {
			return $this->baseRequest()->delete()->request();
		}
		throw new InvalidArgumentException( 'Resource doesn\'t exist.' );
	}

	/**
	 * @return Item
	 */
	public function item(): Item
	{
		return new Item([
			'attributes' => $this->attributes,
			'type' => $this->type(),
		]);
	}

	/**
	 * @return mixed
	 */
	public function save()
	{
		return $this->baseRequest()
			->post( $this->item()->toArray() )
			->request();
	}

	/**
	 * @return string
	 */
	public function type(): string
	{
		return Str::plural( Str::lower(
			Str::replaceFirst( __NAMESPACE__.'\\', '', static::class )
		));
	}
}

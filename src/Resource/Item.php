<?php

namespace Flagrow\Flarum\Api\Resource;

use Flagrow\Flarum\Api\Flarum;
use Flagrow\Flarum\Api\Resource\Resource;
use Flagrow\Flarum\Api\Traits\HasRelationships;
use Illuminate\Support\Arr;

class Item implements Resource
{
	use HasRelationships;

	/**
	 * @var array
	 */
	public $attributes = [];

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $type;

	public function __construct( array $item = [] )
	{
		$this->id = (int)Arr::get( $item, 'id' );
		$this->type = Arr::get( $item, 'type' );
		$this->attributes = Arr::get( $item, 'attributes', [] );
		$this->relations( Arr::get( $item, 'relationships', [] ));
	}

	/**
	 * {@inheritdoc}
	 */
	public function __get( $name )
	{
		if( Arr::has( $this->attributes, $name )) {
			return Arr::get( $this->attributes, $name );
		}
		if( Arr::has( $this->relationships, $name )) {
			return Arr::get( $this->relationships, $name );
		}
	}

	/**
	 * @return Item
	 */
	public function cache(): Item
	{
		Flarum::getCache()->set( $this->id, $this, $this->type );
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'attributes' => $this->attributes,
			'id' => $this->id,
			'relationships' => $this->relationships,
			'type' => $this->type,
		];
	}
}

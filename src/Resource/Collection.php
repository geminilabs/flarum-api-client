<?php

namespace Flagrow\Flarum\Api\Resource;

use Illuminate\Support\Collection as IlluminateCollection;

class Collection extends Resource
{
	/**
	 * @var array
	 */
	protected $items = [];

	public function __construct( array $data )
	{
		foreach( $data as $item ) {
			$item = new Item( $item );
			$this->items[$item->id] = $item;
		}
	}

	/**
	 * @return Collection
	 */
	public function cache(): Collection
	{
		foreach( $this->items as $id => $item ) {
			$item->cache();
		}
		return $this;
	}

	/**
	 * @return IlluminateCollection
	 */
	public function collect(): IlluminateCollection
	{
		return collect( $this->items )->keyBy( 'id' );
	}

	/**
	 * @param int|null $amount
	 * @return IlluminateCollection
	 */
	public function latest( string $by = 'created_at', $amount = null ): IlluminateCollection
	{
		$collection = $this->collect()->sortBy( $by );
		if( $amount ) {
			$collection = $collection->splice( 0, $amount );
		}
		return $collection;
	}
}

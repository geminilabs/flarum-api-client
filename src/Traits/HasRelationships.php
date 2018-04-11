<?php

namespace Flagrow\Flarum\Api\Traits;

use Flagrow\Flarum\Api\Flarum;
use Flagrow\Flarum\Api\Resource\Item;
use Illuminate\Support\Arr;

trait HasRelationships
{
	/**
	 * @var array
	 */
	public $relationships = [];

	/**
	 * @return Item|null
	 */
	protected function parseRelationshipItem( string $type, int $id )
	{
		return Flarum::getCache()->get( $id, null, $type );
	}

	/**
	 * @return void
	 */
	protected function relations( array $relations = [] ): void
	{
		foreach( $relations as $attribute => $relation ) {
			$data = Arr::get( $relation, 'data' );
			if( Arr::get( $data, 'type' )) {
				$this->setRelationshipItem( $attribute, $data );
			}
			else {
				$this->setRelationshipItems( $attribute, $data );
			}
		}
	}

	/**
	 * @return void
	 */
	protected function setRelationshipItem( string $attribute, array $item ): void
	{
		$this->relationships[$attribute] = $this->parseRelationshipItem(
			Arr::get( $item, 'type' ),
			Arr::get( $item, 'id' )
		);
	}

	/**
	 * @return void
	 */
	protected function setRelationshipItems( string $attribute, array $items ): void
	{
		if( !array_key_exists( $attribute, $this->relationships )) {
			$this->relationships[$attribute] = [];
		}
		foreach( $items as $item ) {
			$id = (int)Arr::get( $item, 'id' );
			$this->relationships[$attribute][$id] = $this->parseRelationshipItem(
				Arr::get( $item, 'type' ),
				$id
			);
		}
	}
}

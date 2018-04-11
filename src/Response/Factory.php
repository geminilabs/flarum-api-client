<?php

namespace Flagrow\Flarum\Api\Response;

use Flagrow\Flarum\Api\Resource\Collection;
use Flagrow\Flarum\Api\Resource\Item;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class Factory
{
	/**
	 * @return true|null|Collection|Item
	 */
	public static function build( ResponseInterface $response )
	{
		if( $response->getStatusCode() === 204 ) {
			return true;
		}
		if( empty( $body = $response->getBody() )) {
			return null;
		}
		$json = json_decode( $body, true );
		$data = Arr::get( $json, 'data' );
		static::storeIncluded( $json );
		return $data && !array_key_exists( 'type', $data )
			? (new Collection( $data ))->cache()
			: (new Item( $data ))->cache();
	}

	/**
	 * @return void
	 */
	public static function storeIncluded( array $data ): void
	{
		$included = Arr::get( $data, 'included', [] );
		if( !empty( $included )) {
			(new Collection( $included ))->cache();
		}
	}
}

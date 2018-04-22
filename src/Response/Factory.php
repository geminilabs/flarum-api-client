<?php

namespace Flagrow\Flarum\Api\Response;

use Flagrow\Flarum\Api\Resource\Collection;
use Flagrow\Flarum\Api\Resource\Item;
use Flagrow\Flarum\Api\Resource\Resource;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class Factory
{
	const STATUS_NO_CONTENT = 204;

	/**
	 * @return true|void|Resource|object
	 */
	public static function build( ResponseInterface $response )
	{
		if( $response->getStatusCode() === static::STATUS_NO_CONTENT ) {
			return true;
		}
		if( empty( $body = $response->getBody() ))return;
		$json = json_decode( $body, true );
		static::storeIncluded( $json );
		if( $data = Arr::get( $json, 'data' )) {
			return static::cacheData( $data );
		}
		return (object)$json;
	}

	/**
	 * @return Resource
	 */
	public static function cacheData( $data )
	{
		return array_key_exists( 'type', $data )
			? (new Item( $data ))->cache()
			: (new Collection( $data ))->cache();
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

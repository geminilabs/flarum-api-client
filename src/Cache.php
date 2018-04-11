<?php

namespace Flagrow\Flarum\Api;

use Flagrow\Flarum\Api\Resource\Item;
use Illuminate\Contracts\Cache\Store;

class Cache
{
	/**
	 * @var string
	 */
	protected $activeStore;

	/**
	 * @var int
	 */
	protected $minutesToCache = 60;

	/**
	 * @var Store
	 */
	protected $store;

	/**
	 * @var array|Store[]
	 */
	protected $stores = [];

	public function __construct( Store $store )
	{
		$this->store = $store;
	}

	/**
	 * @param string|null $type
	 * @return mixed
	 */
	public function all( $type = null )
	{
		return $this->getStore( $type )->all();
	}

	/**
	 * @param mixed $default
	 * @param string|null $type
	 * @return mixed
	 */
	public function get( int $id, $default = null, $type = null )
	{
		if( $value = $this->getStore( $type )->get( $id )) {
			return $value;
		}
		return $default;
	}

	/**
	 * @return Store
	 */
	public function getActive(): Store
	{
		return $this->activeStore;
	}

	/**
	 * @param string|null $store
	 * @return Store
	 */
	public function getStore( $store = null ): Store
	{
		if( is_null( $store )) {
			$store = $this->activeStore;
		}
		if( !array_key_exists( $store, $this->stores )) {
			$this->stores[$store] = clone $this->store;
		}
		return $this->stores[$store];
	}

	/**
	 * @param string|null $type
	 * @return Cache
	 */
	public function set( int $id, Item $item, $type = null ): Cache
	{
		$this->getStore( $type )->put( $id, $item, $this->minutesToCache );
		return $this;
	}

	/**
	 * @return Cache
	 */
	public function setActive( string $type ): Cache
	{
		$this->activeStore = $type;
		return $this;
	}
}

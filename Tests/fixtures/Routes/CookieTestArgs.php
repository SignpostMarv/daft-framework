<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Routes;

use SignpostMarv\DaftRouter\TypedArgs;

/**
* @psalm-type T = array{name:string, value:string, secure:bool, http:bool, same-site:'lax'|'strict'}
*
* @tempalte-extends TypedArgs<T>
*
* @property-read string $name
* @property-read string $value
* @property-read bool $secure
* @property-read bool $http
*/
class CookieTestArgs extends TypedArgs
{
	/**
	* @var T
	*/
	protected $typed;

	/**
	* @template K as 'name'|'value'|'secure'|'http'|'same-site'
	*
	* @param array{name:string, value:string, secure:'0'|'1', http:'0'|'1', same-site:'lax'|'strict'} $args
	*/
	public function __construct(array $args)
	{
		$args['secure'] = (bool) $args['secure'];
		$args['http'] = (bool) $args['http'];

		/**
		* @var T
		*/
		$args = $args;

		$this->typed = $args;
	}

	/**
	* @return 'lax'|'strict'
	*/
	public function SameSite() : string
	{
		/**
		* @var 'lax'|'strict'
		*/
		$out = $this->__get('same-site');

		return $out;
	}
}

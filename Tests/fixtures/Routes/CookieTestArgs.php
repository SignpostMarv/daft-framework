<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Routes;

use SignpostMarv\DaftRouter\TypedArgs;

/**
* @template T as array{name:string, value:string, secure:bool, http:bool, same-site:'lax'|'strict'}
* @template S as array{name:string, value:string, secure:'0'|'1', http:'0'|'1', same-site:'lax'|'strict'}
*
* @tempalte-extends TypedArgs<T, S>
*/
class CookieTestArgs extends TypedArgs
{
	const TYPED_PROPERTIES = [
		'name',
		'value',
		'secure',
		'http',
		'sameSite',
	];

	/**
	* @readonly
	*
	* @var string
	*/
	public $name;

	/**
	* @readonly
	*
	* @var string
	*/
	public $value;

	/**
	* @readonly
	*
	* @var bool
	*/
	public $secure;

	/**
	* @readonly
	*
	* @var bool
	*/
	public $http;

	/**
	* @readonly
	*
	* @var 'lax'|'strict'
	*/
	public $sameSite;

	/**
	* @param T $args
	*/
	public function __construct(array $args)
	{
		$this->name = $args['name'];
		$this->value = $args['value'];
		$this->secure = $args['secure'];
		$this->http = $args['http'];
		$this->sameSite = $args['same-site'];
	}

	/**
	* @template K as key-of<T>
	*
	* @param K $property
	* @param S[K] $value
	*
	* @return T[K]
	*/
	public static function PropertyScalarOrNullToValue(
		string $property,
		$value
	) {
		/**
		* @var string
		*/
		$property = $property;

		if (
			'secure' === $property ||
			'http' === $property
		) {
			/**
			* @var T[K]
			*/
			return (bool) $value;
		}

		/**
		* @var T[K]
		*/
		return parent::PropertyScalarOrNullToValue($property, $value);
	}
}

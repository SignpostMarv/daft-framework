<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework;

use BadMethodCallException;
use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use SplObjectStorage;
use Symfony\Component\HttpFoundation\Request;

/**
* @template CONFIG as array<string, scalar|array|object|null>
*/
class Framework
{
	const DEFAULT_BOOL_REQUIRE_FILE_EXISTS = true;

	const BOOL_IN_ARRAY_STRICT = true;

	/**
	* @var string
	*/
	private $baseUrl;

	/**
	* @var string
	*/
	private $basePath;

	/**
	* @var EasyDB|null
	*/
	private $db;

	/**
	* @var array
	*/
	private $config;

	/**
	* @var SplObjectStorage<Request, Framework>|null
	*/
	private static $requestpair;

	/**
	* @param CONFIG $config
	*/
	public function __construct(
		string $baseUrl,
		string $basePath,
		array $config
	) {
		if ( ! is_dir($basePath)) {
			throw new InvalidArgumentException(
				'Base path must be a directory!'
			);
		} elseif (realpath($basePath) !== $basePath) {
			throw new InvalidArgumentException(
				'Path should be explicitly set to via realpath!'
			);
		}

		$this->baseUrl = static::NormaliseUrl($baseUrl);
		$this->basePath = $basePath;

		$this->config = $this->ValidateConfig($config);
	}

	public static function NormaliseUrl(string $baseUrl) : string
	{
		/**
		* @var array{scheme?:string, host?:string, path?:string, port?:int}
		*/
		$parsed = parse_url($baseUrl);

		if ( ! isset($parsed['scheme'], $parsed['host'], $parsed['path'])) {
			throw new InvalidArgumentException(
				'Base URL must have at least a scheme, host & path in order to be normalised!'
			);
		}

		$scheme = $parsed['scheme'];
		$host = $parsed['host'];

		$baseUrl = $scheme . '://' . $host;

		if (isset($parsed['port'])) {
			$baseUrl .= ':' . $parsed['port'];
		}

		$path = $parsed['path'];

		return $baseUrl . str_replace('//', '/', $path);
	}

	public function ObtainDatabaseConnection() : EasyDB
	{
		if ( ! ($this->db instanceof EasyDB)) {
			throw new BadMethodCallException(
				'Database Connection not available!'
			);
		}

		return $this->db;
	}

	public function ConfigureDatabaseConnection(
		string $dsn,
		string $username = null,
		string $password = null,
		array $options = []
	) : void {
		if ($this->db instanceof EasyDB) {
			throw new BadMethodCallException(
				'Database Connection already made!'
			);
		}

		$this->db = Factory::create($dsn, $username, $password, $options);
	}

	public function ObtainBaseUrl() : string
	{
		return $this->baseUrl;
	}

	public function ObtainBasePath() : string
	{
		return $this->basePath;
	}

	public function ObtainConfig() : array
	{
		return $this->config;
	}

	public function FileIsUnderBasePath(
		string $filename,
		bool $requireFileExists = self::DEFAULT_BOOL_REQUIRE_FILE_EXISTS
	) : bool {
		$realpath = realpath($filename);

		return
			( ! $requireFileExists && ! file_exists($filename)) ||
			(
				is_file($filename) &&
				is_string($realpath) &&
				0 === mb_strpos($realpath, $this->basePath)
			);
	}

	public static function PairWithRequest(
		self $framework,
		Request $request
	) : void {
		self::initRequestPair()[$request] = $framework;
	}

	public static function ObtainFrameworkForRequest(Request $request) : self
	{
		$framework = self::initRequestPair()[$request] ?? null;

		if ( ! ($framework instanceof self)) {
			throw new InvalidArgumentException(
				'No framework instance has been paired with the provided request!'
			);
		}

		return $framework;
	}

	public static function DisposeOfFrameworkReferences(
		self ...$frameworks
	) : void {
		$requestpair = self::initRequestPair();

		foreach ($requestpair as $request) {
			if (
				in_array(
					$requestpair[$request],
					$frameworks,
					self::BOOL_IN_ARRAY_STRICT
				)
			) {
				unset($requestpair[$request]);
			}
		}
	}

	public static function DisposeOfRequestReferences(
		Request ...$requests
	) : void {
		$requestpair = self::initRequestPair();

		foreach ($requests as $request) {
			if (isset($requestpair[$request])) {
				unset($requestpair[$request]);
			}
		}
	}

	/**
	* @throws InvalidArgumentException if $config contains something not valid
	*/
	protected function ValidateConfig(array $config) : array
	{
		return $config;
	}

	/**
	* @return SplObjectStorage<Request, Framework>
	*/
	private static function initRequestPair() : SplObjectStorage
	{
		if (is_null(self::$requestpair)) {
			/**
			* @var SplObjectStorage<Request, Framework>
			*/
			self::$requestpair = new SplObjectStorage();
		}

		return self::$requestpair;
	}
}

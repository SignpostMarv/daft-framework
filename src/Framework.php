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
use Symfony\Component\HttpFoundation\Request;

class Framework
{
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
    * @var array<string, self>
    */
    private static $requestpair = [];

    public function __construct(string $baseUrl, string $basePath, array $config = [])
    {
        if ( ! is_dir($basePath)) {
            throw new InvalidArgumentException('Base path must be a directory!');
        } elseif (realpath($basePath) !== $basePath) {
            throw new InvalidArgumentException('Path should be explicitly set to via realpath!');
        }

        $parsed = parse_url($baseUrl);

        $baseUrl = $parsed['scheme'] . '://' . $parsed['host'];

        if (isset($parsed['port'])) {
            $baseUrl .= ':' . $parsed['port'];
        }

        $baseUrl .= str_replace('//', '/', $parsed['path']);

        $this->baseUrl = $baseUrl;
        $this->basePath = $basePath;

        $this->ValidateConfig($config);

        $this->config = $config;
    }

    /**
    * @throws InvalidArgumentException if $config contains something not valid
    */
    protected function ValidateConfig(array $config) : void
    {

    }

    public function ObtainDatabaseConnection() : EasyDB
    {
        if ( ! ($this->db instanceof EasyDB)) {
            throw new BadMethodCallException('Database Connection not available!');
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
            throw new BadMethodCallException('Database Connection already made!');
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

    public function FileIsUnderBasePath(string $filename) : bool
    {
        return
            is_file($filename) &&
            0 === mb_strpos($this->basePath, realpath($filename));
    }

    public static function PairWithRequest(self $framework, Request $request) : void
    {
        self::$requestpair[spl_object_hash($request)] = $framework;
    }

    public static function ObtainFrameworkForRequest(Request $request) : self
    {
        $framework = self::$requestpair[spl_object_hash($request)] ?? null;

        if ( ! ($framework instanceof self)) {
            throw new InvalidArgumentException(
                'No framework instance has been paired with the provided request!'
            );
        }

        return $framework;
    }

    public static function DisposeOfFrameworkReferences(self ...$frameworks) : void
    {
        foreach (array_keys(self::$requestpair) as $hash) {
            if (in_array(self::$requestpair[$hash], $frameworks, true)) {
                unset(self::$requestpair[$hash]);
            }
        }
    }

    public static function DisposeOfRequestReferences(Request ...$requests) : void
    {
        foreach ($requests as $request) {
            $hash = spl_object_hash($request);

            if (isset(self::$requestpair[$hash])) {
                unset(self::$requestpair[$hash]);
            }
        }
    }
}

<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use Generator;
use InvalidArgumentException;
use RuntimeException;
use SignpostMarv\DaftFramework\HttpHandler;
use SignpostMarv\DaftRouter\DaftRouteAcceptsEmptyArgs;
use SignpostMarv\DaftRouter\DaftRouteAcceptsTypedArgs;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Tests\ImplementationTest as Base;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;

class HttpHandlerTest extends Base
{
	/**
	 * @return Generator<int, array{0:class-string<DaftSource>}, mixed, void>
	 */
	public function DataProviderGoodSources() : Generator
	{
		yield from [
			[
				fixtures\DaftSourceConfig::class,
			],
		];
	}

	/**
	 * @return Generator<int, array{0:class-string<HttpHandler>, 1:array, 2:string, 3:string, 4:array}, mixed, void>
	 */
	public function DataProviderHttpHandlerInstances() : Generator
	{
		yield from [
			[
				HttpHandler::class,
				['ConfigureDatabaseConnection' => ['sqlite::memory:', null, null, []]],
				'https://example.com/',
				realpath(__DIR__ . '/fixtures'),
				[
					DaftSource::class => [
						'cacheFile' => (__DIR__ . '/fixtures/http-kernel.fast-route.cache'),
					],
				],
			],
		];
	}

	/**
	 * @return Generator<int, array{0:HttpHandler, 1:Request, 2:int, 3:string}, mixed, void>
	 */
	public function DataProviderHttpHandlerHandle() : Generator
	{
		foreach ($this->DataProviderHttpHandlerInstances() as $args) {
			/**
			 * @var string
			 */
			$implementation = $args[0];

			/**
			 * @var array<string, mixed[]>
			 */
			$postConstructionCalls = $args[1];

			$basePath = $args[3];

			/**
			 * @var array<string, mixed[]>
			 */
			$config = $args[4];

			foreach ($this->DataProviderVerifyHandlerGood() as $testArgs) {
				[$baseUrl, $config, $testArgs] = $this->prepDataProviderVerifyHandlerGoodArgs(
					$config,
					$testArgs
				);

				if (
					! isset($testArgs[0], $testArgs[1], $testArgs[2], $testArgs[3], $testArgs[4])
				) {
					throw new RuntimeException(sprintf(
						'Unsupported args derived from %s::prepDataProviderVerifyHandlerGoodArgs',
						get_class($this)
					));
				}

				$baseUrl = (string) $baseUrl;
				$config = (array) $config;

				/**
				 * @var array
				 */
				$testArgs = $testArgs;

				/**
				 * @var int
				 */
				$expectedStatus = $testArgs[2];

				/**
				 * @var string
				 */
				$expectedContent = $testArgs[3];

				/**
				 * @var array
				 */
				$requestArgs = $testArgs[4];

				$instance = Utilities::ObtainHttpHandlerInstanceMixedArgs(
					$this,
					$implementation,
					$baseUrl,
					$basePath,
					$config
				);
				Utilities::ConfigureFrameworkInstance($this, $instance, $postConstructionCalls);

				$uri = (string) $requestArgs[0];
				$method = (string) ($requestArgs[1] ?? 'GET');
				$parameters = (array) ($requestArgs[2] ?? []);
				$cookies = (array) ($requestArgs[3] ?? []);
				$files = (array) ($requestArgs[4] ?? []);
				$server = (array) ($requestArgs[5] ?? []);

				/**
				 * @var string|resource|null
				 */
				$content = ($requestArgs[6] ?? null);

				$request = Request::create(
					$uri,
					$method,
					$parameters,
					$cookies,
					$files,
					$server,
					$content
				);

				yield [$instance, $request, $expectedStatus, $expectedContent];
			}
		}
	}

	/**
	 * @return Generator<int, array{0:class-string<HttpHandler>, 1:string, 2:string, 3:array, 4:array<string, mixed[]>}, mixed, void>
	 */
	public function DataProviderTestDroppedConfigProperty() : Generator
	{
		foreach ($this->DataProviderHttpHandlerInstances() as $args) {
			[$implementation, , , $basePath, $config] = $args;

			foreach ($this->DataProviderVerifyHandlerGood() as $testArgs) {
				[$baseUrl, $config] = $this->prepDataProviderVerifyHandlerGoodArgs(
					(array) $config,
					$testArgs
				);

				foreach (['cacheFile', 'sources'] as $omitSubProperty) {
					/**
					 * @var array<string, mixed>
					 */
					$modifiedConfig = (array) $config;

					/**
					 * @var array<string, mixed>
					 */
					$modifiedDaftSourceConfig = $modifiedConfig[DaftSource::class];

					unset($modifiedDaftSourceConfig[$omitSubProperty]);

					$modifiedConfig[DaftSource::class] = $modifiedDaftSourceConfig;

					/**
					 * @var array<string, mixed[]>
					 */
					$args1 = $args[1];

					yield [$implementation, $baseUrl, $basePath, $modifiedConfig, $args1];
				}
			}
		}
	}

	/**
	 * @param array<string, mixed[]> $args1
	 *
	 * @dataProvider DataProviderTestDroppedConfigProperty
	 */
	public function test_dropped_config_property(
		string $implementation,
		string $baseUrl,
		string $basePath,
		array $config,
		array $args1
	) : void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('%s config not found!', DaftSource::class));

		$instance = Utilities::ObtainHttpHandlerInstanceMixedArgs(
			$this,
			$implementation,
			$baseUrl,
			$basePath,
			$config
		);
		Utilities::ConfigureFrameworkInstance($this, $instance, $args1);
	}

	/**
	 * @dataProvider DataProviderHttpHandlerHandle
	 */
	public function test_handler_good_with_http_kernel(
		HttpHandler $instance,
		Request $request,
		int $expectedStatus,
		string $expectedContent
	) : void {
		$dispatcher = new EventDispatcher();
		$instance->AttachToEventDispatcher($dispatcher);
		HttpHandler::PairWithRequest($instance, $request);

		$kernel = new HttpKernel(
			$dispatcher,
			new ControllerResolver(),
			new RequestStack(),
			new ArgumentResolver()
		);

		$response = $kernel->handle($request);

		$kernel->terminate($request, $response);

		static::assertSame($expectedStatus, $response->getStatusCode());
		static::assertSame($expectedContent, $response->getContent());
	}

	/**
	 * @depends test_compiler_verify_add_route_adds_routes
	 * @depends test_compiler_verify_add_middleware_adds_middlewares
	 * @depends test_compiler_excludes_middleware
	 *
	 * @dataProvider DataProviderVerifyHandlerGood
	 *
	 * @param array<int, class-string<DaftRouteAcceptsEmptyArgs>|class-string<DaftRouteAcceptsTypedArgs>> $sources
	 * @param array<string, scalar|array|object|null> $expectedHeaders
	 */
	public function test_handler_good(
		array $sources,
		string $prefix,
		int $expectedStatus,
		string $expectedContent,
		array $requestArgs,
		array $expectedHeaders = []
	) : void {
		static::markTestSkipped(
			'see ' .
			static::class .
			'::testHandlerGoodWithHttpKernel()'
		);
	}

	/**
	 * @return array{0:string, 1:array, 2:array}
	 */
	protected function prepDataProviderVerifyHandlerGoodArgs(
		array $config,
		array $testArgs
	) : array {
		[$sources, $prefix, , , $requestArgs] = $testArgs;

		[$uri] = (array) $requestArgs;

		/**
		 * @var array
		 */
		$parsed = parse_url((string) $uri);

		$baseUrl = (string) ($parsed['scheme'] ?? '') . '://' . (string) ($parsed['host'] ?? '');

		if (isset($parsed['port']) && is_int($parsed['port'])) {
			$baseUrl .= ':' . (string) $parsed['port'];
		}

		$baseUrl .= '/' . (string) $prefix;

		/**
		 * @var array<string, string|array<int, string>>
		 * @var array<int, string> $daftSourceConfig['sources']
		 * @var string $daftSourceConfig['cacheFile']
		 */
		$daftSourceConfig = (array) $config[DaftSource::class];

		$daftSourceConfig['sources'] = array_filter((array) $sources, 'is_string');
		$daftSourceConfig['cacheFile'] = __DIR__ . '/fixtures/http-kernel.fast-route.cache';

		if (is_file($daftSourceConfig['cacheFile'])) {
			unlink((string) ($daftSourceConfig['cacheFile']));
		}

		$config[DaftSource::class] = $daftSourceConfig;

		return [$baseUrl, $config, $testArgs];
	}

	protected function DataProviderGoodHandler() : Generator
	{
		yield from [
			[
				[
					fixtures\DaftSourceConfig::class,
				],
				'/',
				302,
				(
					'<!DOCTYPE html>' . "\n" .
					'<html>' . "\n" .
					'    <head>' . "\n" .
					'        <meta charset="UTF-8" />' . "\n" .
					'        <meta http-equiv="refresh" content="0;url=\'/login\'" />' . "\n" .
					'' . "\n" .
					'        <title>Redirecting to /login</title>' . "\n" .
					'    </head>' . "\n" .
					'    <body>' . "\n" .
					'        Redirecting to <a href="/login">/login</a>.' . "\n" .
					'    </body>' . "\n" .
					'</html>'
				),
				[],
				'https://example.com/',
			],
		];
	}
}

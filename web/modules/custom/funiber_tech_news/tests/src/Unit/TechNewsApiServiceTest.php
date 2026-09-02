<?php

namespace Drupal\Tests\funiber_tech_news\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\funiber_tech_news\Service\TechNewsApiService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the TechNewsApiService.
 *
 * @group funiber_tech_news
 */
class TechNewsApiServiceTest extends TestCase {

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * Mock Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * Mock Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * Mock Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\funiber_tech_news\Service\TechNewsApiService
   */
  protected TechNewsApiService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->cacheBackend = $this->createMock(CacheBackendInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    $this->loggerFactory->method('get')
      ->with('funiber_tech_news')
      ->willReturn($this->logger);

    $this->service = new TechNewsApiService(
      $this->httpClient,
      $this->cacheBackend,
      $this->loggerFactory
    );
  }

  /**
   * Test successful API fetch, normalization, and cache storage.
   */
  public function testSuccessfulApiFetchAndNormalization(): void {
    $mockApiResponse = json_encode([
      [
        'id' => 991,
        'title' => 'Drupal 11 Headless with Next.js',
        'description' => 'Building modern decoupled architectures.',
        'cover_image' => 'https://example.com/cover.jpg',
        'url' => 'https://dev.to/article/drupal11',
        'published_at' => '2026-08-15T12:00:00Z',
        'reading_time_minutes' => 5,
        'tag_list' => ['drupal', 'php', 'javascript'],
        'user' => [
          'name' => 'Drupal Contributor',
          'profile_image_90' => 'https://example.com/user.jpg',
        ],
      ],
    ]);

    // Expect cache miss
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('funiber_tech_news:articles:technology:1')
      ->willReturn(FALSE);

    // Expect HTTP call
    $response = new Response(200, [], $mockApiResponse);
    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    // Expect cache set
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with(
        'funiber_tech_news:articles:technology:1',
        $this->isType('array'),
        $this->greaterThan(time()),
        ['funiber_tech_news:articles']
      );

    $results = $this->service->getArticles(1, 'technology');

    $this->assertCount(1, $results);
    $this->assertEquals(991, $results[0]['id']);
    $this->assertEquals('Drupal 11 Headless with Next.js', $results[0]['title']);
    $this->assertEquals('Building modern decoupled architectures.', $results[0]['description']);
    $this->assertEquals('https://example.com/cover.jpg', $results[0]['image_url']);
    $this->assertEquals('5 min read', $results[0]['reading_time']);
    $this->assertEquals('Drupal Contributor', $results[0]['author']);
  }

  /**
   * Test that cached articles are returned immediately without HTTP requests.
   */
  public function testCachedResponseReturned(): void {
    $cachedArticles = [
      [
        'id' => 55,
        'title' => 'Cached AI Article',
        'description' => 'From cache directly.',
        'image_url' => 'https://example.com/cache.jpg',
        'url' => 'https://example.com/item',
        'published_date' => 'Aug 1, 2026',
        'reading_time' => '3 min read',
        'tags' => ['ai'],
        'author' => 'Cache Author',
      ],
    ];

    $cacheObject = (object) ['data' => $cachedArticles];

    // Cache hit
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('funiber_tech_news:articles:ai:1')
      ->willReturn($cacheObject);

    // HTTP client should NOT be called
    $this->httpClient->expects($this->never())
      ->method('request');

    $results = $this->service->getArticles(1, 'ai');

    $this->assertEquals($cachedArticles, $results);
  }

  /**
   * Test that fallback articles are returned when API request throws an exception.
   */
  public function testFallbackOnGuzzleException(): void {
    // Cache miss
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    // Guzzle throws TransferException
    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(new TransferException('Connection timed out'));

    // Logger should record error
    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error fetching articles'),
        $this->isType('array')
      );

    $results = $this->service->getArticles(2, 'technology');

    // Should return 2 fallback items gracefully
    $this->assertCount(2, $results);
    $this->assertNotEmpty($results[0]['title']);
    $this->assertNotEmpty($results[0]['description']);
    $this->assertNotEmpty($results[0]['image_url']);
  }

}

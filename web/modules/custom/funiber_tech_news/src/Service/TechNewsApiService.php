<?php

namespace Drupal\funiber_tech_news\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service to consume, cache, and normalize tech news from external REST APIs.
 */
class TechNewsApiService {

  /**
   * The HTTP client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Cache expiration time in seconds (1 hour default).
   */
  protected const CACHE_TTL = 3600;

  /**
   * Cache tag prefix.
   */
  protected const CACHE_TAG = 'funiber_tech_news:articles';

  /**
   * Primary REST API endpoint (Dev.to API).
   */
  protected const API_ENDPOINT = 'https://dev.to/api/articles';

  /**
   * Constructs a new TechNewsApiService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    ClientInterface $http_client,
    CacheBackendInterface $cache_backend,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->cacheBackend = $cache_backend;
    $this->logger = $logger_factory->get('funiber_tech_news');
  }

  /**
   * Fetches latest tech news articles with caching support.
   *
   * @param int $limit
   *   Number of articles to fetch.
   * @param string $tag
   *   Topic/tag to filter (e.g., 'technology', 'devops', 'ai').
   *
   * @return array
   *   Array of normalized article items.
   */
  public function getArticles(int $limit = 6, string $tag = 'technology'): array {
    $cid = sprintf('funiber_tech_news:articles:%s:%d', $tag, $limit);

    // 1. Attempt to load from Drupal Cache API
    if ($cached = $this->cacheBackend->get($cid)) {
      return $cached->data;
    }

    // 2. Fetch fresh data from API
    try {
      $response = $this->httpClient->request('GET', self::API_ENDPOINT, [
        'query' => [
          'tag' => $tag,
          'per_page' => $limit,
          'state' => 'rising',
        ],
        'timeout' => 8.0,
        'headers' => [
          'Accept' => 'application/json',
          'User-Agent' => 'FUNIBER-Drupal11-Portal/1.0',
        ],
      ]);

      if ($response->getStatusCode() === 200) {
        $raw_data = json_decode((string) $response->getBody(), true);
        if (is_array($raw_data)) {
          $articles = $this->normalizeArticles($raw_data);

          // Store in Cache
          $this->cacheBackend->set(
            $cid,
            $articles,
            time() + self::CACHE_TTL,
            [self::CACHE_TAG]
          );

          return $articles;
        }
      }
    } catch (GuzzleException $e) {
      $this->logger->error('Error fetching articles from external API: @error', [
        '@error' => $e->getMessage(),
      ]);
    } catch (\Exception $e) {
      $this->logger->error('Unexpected error processing articles: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // 3. Fallback mock data if external API fails or is unreachable
    return $this->getFallbackArticles($limit, $tag);
  }

  /**
   * Normalizes raw API response into standardized structure for Twig rendering.
   *
   * @param array $raw_articles
   *   Raw articles array from API.
   *
   * @return array
   *   Normalized articles.
   */
  protected function normalizeArticles(array $raw_articles): array {
    $normalized = [];

    foreach ($raw_articles as $item) {
      // Extract fallback image if none provided
      $image = !empty($item['cover_image'])
        ? $item['cover_image']
        : (!empty($item['social_image'])
          ? $item['social_image']
          : 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=600&q=80');

      // Date formatting
      $published_at = !empty($item['published_at'])
        ? date('M j, Y', strtotime($item['published_at']))
        : date('M j, Y');

      $normalized[] = [
        'id' => $item['id'] ?? uniqid('article_', true),
        'title' => $item['title'] ?? 'Noticia Tecnológica',
        'description' => !empty($item['description'])
          ? $item['description']
          : 'Avances e innovaciones tecnológicas destacadas en el ecosistema digital global.',
        'image_url' => $image,
        'url' => $item['url'] ?? '#',
        'published_date' => $published_at,
        'reading_time' => ($item['reading_time_minutes'] ?? 3) . ' min read',
        'tags' => !empty($item['tag_list']) ? $item['tag_list'] : ['technology'],
        'author' => $item['user']['name'] ?? 'FUNIBER Tech Lab',
        'author_image' => $item['user']['profile_image_90'] ?? null,
      ];
    }

    return $normalized;
  }

  /**
   * Fallback curated articles in case of network unavailability.
   *
   * @param int $limit
   *   Number of items.
   * @param string $tag
   *   Topic tag.
   *
   * @return array
   *   Fallback articles.
   */
  public function getFallbackArticles(int $limit = 6, string $tag = 'technology'): array {
    $fallback_data = [
      [
        'id' => 101,
        'title' => 'Arquitecturas Cloud Native y Kubernetes en Entornos Educativos 2026',
        'description' => 'Estrategias modernas para desplegar plataformas Drupal y microservicios escalables con alta disponibilidad y resiliencia.',
        'image_url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=600&q=80',
        'url' => 'https://www.funiber.org/innovacion',
        'published_date' => date('M j, Y'),
        'reading_time' => '4 min read',
        'tags' => ['devops', 'kubernetes', 'drupal'],
        'author' => 'Dr. Carlos Mendoza',
        'author_image' => null,
      ],
      [
        'id' => 102,
        'title' => 'Avances en Modelos Generativos de Inteligencia Artificial para Investigación',
        'description' => 'Cómo los nuevos modelos LLM transforman el análisis de grandes volúmenes de datos científicos y académicos.',
        'image_url' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=600&q=80',
        'url' => 'https://www.funiber.org/investigacion',
        'published_date' => date('M j, Y', strtotime('-1 day')),
        'reading_time' => '5 min read',
        'tags' => ['ai', 'machinelearning', 'datascience'],
        'author' => 'FUNIBER Tech Lab',
        'author_image' => null,
      ],
      [
        'id' => 103,
        'title' => 'Ciberseguridad Zero Trust: Protegiendo Plataformas Web Gubernamentales',
        'description' => 'Implementación de autenticación multifactor, cifrado de extremo a extremo y políticas de mínimo privilegio.',
        'image_url' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=600&q=80',
        'url' => 'https://www.funiber.org/seguridad',
        'published_date' => date('M j, Y', strtotime('-2 days')),
        'reading_time' => '6 min read',
        'tags' => ['cybersecurity', 'zerotrust', 'security'],
        'author' => 'Ing. Laura Ramos',
        'author_image' => null,
      ],
      [
        'id' => 104,
        'title' => 'Web Performance: Técnicas de Optimización y Core Web Vitals en 2026',
        'description' => 'Guía paso a paso para lograr puntuaciones 100/100 en Lighthouse utilizando HTTP/3, compresión Brotli y SSR.',
        'image_url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=600&q=80',
        'url' => 'https://www.funiber.org/desarrollo-web',
        'published_date' => date('M j, Y', strtotime('-3 days')),
        'reading_time' => '3 min read',
        'tags' => ['performance', 'webdev', 'frontend'],
        'author' => 'Equipo Frontend',
        'author_image' => null,
      ],
    ];

    return array_slice($fallback_data, 0, $limit);
  }

}

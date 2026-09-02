<?php

namespace Drupal\funiber_tech_news\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\funiber_tech_news\Service\TechNewsApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for FUNIBER Tech News routes.
 */
class TechNewsController extends ControllerBase {

  /**
   * The tech news API service.
   *
   * @var \Drupal\funiber_tech_news\Service\TechNewsApiService
   */
  protected TechNewsApiService $apiService;

  /**
   * The controller constructor.
   *
   * @param \Drupal\funiber_tech_news\Service\TechNewsApiService $api_service
   *   The API service.
   */
  public function __construct(TechNewsApiService $api_service) {
    $this->apiService = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('funiber_tech_news.api_service')
    );
  }

  /**
   * Builds the response for /noticias-tech page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array representing the page.
   */
  public function content(Request $request): array {
    $tag = $request->query->get('tag', 'technology');
    $limit = (int) $request->query->get('limit', 12);
    $limit = max(4, min(30, $limit));

    $articles = $this->apiService->getArticles($limit, $tag);

    return [
      '#theme' => 'funiber_tech_news_page',
      '#articles' => $articles,
      '#category' => $tag,
      '#total_count' => count($articles),
      '#attached' => [
        'library' => [
          'funiber_tech_news/tech-news-styles',
        ],
      ],
      '#cache' => [
        'keys' => ['funiber_tech_news_page', $tag, (string) $limit],
        'tags' => ['funiber_tech_news:articles'],
        'contexts' => ['url.query_args:tag', 'url.query_args:limit'],
        'max-age' => 3600,
      ],
    ];
  }

}

<?php

namespace Drupal\funiber_tech_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\funiber_tech_news\Service\TechNewsApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'FUNIBER Tech News' Block.
 *
 * @Block(
 *   id = "funiber_tech_news_block",
 *   admin_label = @Translation("Bloque de Noticias Tecnológicas FUNIBER"),
 *   category = @Translation("FUNIBER Portal")
 * )
 */
class TechNewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The tech news API service.
   *
   * @var \Drupal\funiber_tech_news\Service\TechNewsApiService
   */
  protected TechNewsApiService $apiService;

  /**
   * Constructs a new TechNewsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\funiber_tech_news\Service\TechNewsApiService $api_service
   *   The tech news API service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TechNewsApiService $api_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->apiService = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('funiber_tech_news.api_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'items_count' => 4,
      'topic_tag' => 'technology',
      'block_title' => 'Innovación & Tech News API',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título visible del bloque'),
      '#default_value' => $this->configuration['block_title'],
      '#required' => TRUE,
    ];

    $form['items_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Número de noticias a mostrar'),
      '#default_value' => $this->configuration['items_count'],
      '#min' => 1,
      '#max' => 12,
      '#required' => TRUE,
    ];

    $form['topic_tag'] = [
      '#type' => 'select',
      '#title' => $this->t('Categoría / Tag de la API'),
      '#options' => [
        'technology' => $this->t('Tecnología General'),
        'ai' => $this->t('Inteligencia Artificial'),
        'devops' => $this->t('DevOps y Cloud'),
        'webdev' => $this->t('Desarrollo Web'),
        'security' => $this->t('Ciberseguridad'),
      ],
      '#default_value' => $this->configuration['topic_tag'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['block_title'] = $form_state->getValue('block_title');
    $this->configuration['items_count'] = (int) $form_state->getValue('items_count');
    $this->configuration['topic_tag'] = $form_state->getValue('topic_tag');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $limit = $this->configuration['items_count'] ?? 4;
    $tag = $this->configuration['topic_tag'] ?? 'technology';
    $title = $this->configuration['block_title'] ?? 'Innovación & Tech News API';

    $articles = $this->apiService->getArticles($limit, $tag);

    return [
      '#theme' => 'funiber_tech_news_block',
      '#articles' => $articles,
      '#title' => $title,
      '#tag' => $tag,
      '#view_more_url' => '/noticias-tech',
      '#attached' => [
        'library' => [
          'funiber_tech_news/tech-news-styles',
        ],
      ],
      '#cache' => [
        'keys' => ['funiber_tech_news_block', $tag, (string) $limit],
        'tags' => ['funiber_tech_news:articles'],
        'max-age' => 3600,
      ],
    ];
  }

}

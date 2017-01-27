<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Local Tasks" escort to display the local tasks.
 *
 * @Escort(
 *   id = "local_tasks",
 *   admin_label = @Translation("Local Tasks"),
 *   category = @Translation("Menu"),
 * )
 */
class LocalTasks extends EscortPluginMultipleBase implements ContainerFactoryPluginInterface {
  use EscortPluginLinkTrait;

  /**
   * {@inheritdoc}
   */
  protected $provideMultiple = TRUE;


  /**
   * {@inheritdoc}
   */
  protected $usesIcon = FALSE;

  /**
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a LocalTasksEscort instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The local task manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LocalTaskManagerInterface $local_task_manager, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->localTaskManager = $local_task_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.menu.local_task'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'primary' => TRUE,
      'secondary' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildItems() {
    $items = [];
    $config = $this->configuration;
    $cacheability = new CacheableMetadata();
    $secondary_tabs = [];

    // Add only selected levels for the printed output.
    if ($config['secondary']) {
      $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 1);
      $cacheability = $cacheability->merge($links['cacheability']);

      if (count(Element::getVisibleChildren($links['tabs'])) > 1) {
        $secondary_tabs = $links['tabs'];
      }
    }

    // Add only selected levels for the printed output.
    if ($config['primary']) {
      $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 0);
      $cacheability = $cacheability->merge($links['cacheability']);

      if (count(Element::getVisibleChildren($links['tabs'])) > 1) {
        $tabs = $links['tabs'];
        foreach ($tabs as $tab) {
          $tab_build = $this->buildTab($tab);
          $tab_build['#attributes']['class'][] = 'primary-tab';
          $items[] = $tab_build;
          // Set active class.
          if (!empty($tab['#active']) && !empty($secondary_tabs)) {
            foreach ($secondary_tabs as $secondary_tab) {
              $secondary_tab_build = $this->buildTab($secondary_tab);
              // Set weight to same as parent.
              $secondary_tab_build['#weight'] = $tab_build['#weight'];
              $secondary_tab_build['#attributes']['class'][] = 'secondary-tab';
              $items[] = $secondary_tab_build;
            }
          }
        }
      }
    }
    $cacheability->applyTo($items);
    return $items;
  }

  /**
   * Prepare tab for rendering.
   *
   * @param array $tab
   *   A tab.
   *
   * @return array
   *   A render array.
   */
  protected function buildTab($tab) {
    $url = $tab['#link']['url'];
    $title = $tab['#link']['title'];

    $build = $this->buildLink($title, $url);

    // Set active class.
    if (!empty($tab['#active'])) {
      $build['#attributes']['class'][] = 'is-active';
    }

    $build['#access'] = $tab['#access'];
    $build['#weight'] = $tab['#weight'];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $defaults = $this->defaultConfiguration();

    $form['levels'] = [
      '#type' => 'details',
      '#title' => $this->t('Shown tabs'),
      '#description' => $this->t('Select tabs being shown in the escort'),
      // Open if not set to defaults.
      '#open' => $defaults['primary'] !== $config['primary'] || $defaults['secondary'] !== $config['secondary'],
    ];
    $form['levels']['primary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show primary tabs'),
      '#default_value' => $config['primary'],
    ];
    $form['levels']['secondary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show secondary tabs'),
      '#default_value' => $config['secondary'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    $levels = $form_state->getValue('levels');
    $this->configuration['primary'] = $levels['primary'];
    $this->configuration['secondary'] = $levels['secondary'];
  }

}

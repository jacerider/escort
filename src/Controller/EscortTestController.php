<?php

namespace Drupal\escort\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\escort\EscortManagerInterface;
use Drupal\escort\EscortRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to list escorts.
 */
class EscortTestController extends ControllerBase {

  /**
   * The escort manager.
   *
   * @var \Drupal\escort\EscortManagerInterface
   */
  protected $escortItemManager;

  /**
   * The escort repository.
   *
   * @var \Drupal\escort\EscortRepositoryInterface
   */
  protected $escortRepository;

  /**
   * Constructs a EscortTestController object.
   *
   * @param \Drupal\escort\EscortManagerInterface $escort_manager
   *   The escort manager.
   */
  public function __construct(EscortManagerInterface $escort_manager, EscortRepositoryInterface $escort_repository) {
    $this->escortItemManager = $escort_manager;
    $this->escortRepository = $escort_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.escort'),
      $container->get('escort.repository')
    );
  }

  /**
   * Shows the escort administration page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing(Request $request = NULL) {
    $build = [];

    $headers = [
      ['data' => $this->t('Plugin Type')],
      ['data' => $this->t('Operations')],
    ];

    $definitions = $this->escortItemManager->getSortedDefinitions($this->escortItemManager->getDefinitions());
    foreach ($definitions as $plugin_id => $plugin) {
      $row = [];
      $row['title']['data'] = $plugin['admin_label'];
      $links['add'] = [
        'title' => $this->t('Test'),
        'url' => Url::fromRoute('escort.escort_test_plugin', ['plugin_id' => $plugin_id]),
      ];
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }

    $build['escorts'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No escorts available.'),
      '#attributes' => [
        'class' => ['escort-add-table'],
      ],
    ];
    return $build;
  }

  /**
   * Test inidividual plugin.
   *
   * @param string $plugin_id
   *   The plugin ID for the escort.
   *
   * @return array
   *   The escort edit form.
   */
  public function test($plugin_id) {
    $this->escortRepository->enforceIsTest($plugin_id);
    $plugin = $this->escortItemManager->getDefinition($plugin_id);
    $build = [];
    $build['#title'] = $this->t('Test: %name', ['%name' => $plugin['admin_label']]);

    return $build;
    // Create an escort entity.
    $entity = $this->entityTypeManager()->getStorage('escort')->create(['plugin' => $plugin_id]);
    return $this->entityFormBuilder()->getForm($entity);
  }

}

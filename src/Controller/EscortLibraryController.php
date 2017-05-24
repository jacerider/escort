<?php

namespace Drupal\escort\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\escort\EscortManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\escort\EscortAjaxTrait;

/**
 * Provides a list of escort plugins to be added to the layout.
 */
class EscortLibraryController extends ControllerBase {
  use EscortAjaxTrait;

  /**
   * The escort manager.
   *
   * @var \Drupal\escort\EscortManagerInterface
   */
  protected $escortItemManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\LazyContextRepository
   */
  protected $contextRepository;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The local action manager.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionManager;

  /**
   * Constructs a EscortLibraryController object.
   *
   * @param \Drupal\escort\EscortManagerInterface $escort_manager
   *   The escort manager.
   * @param \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository
   *   The context repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EscortManagerInterface $escort_manager, LazyContextRepository $context_repository, RouteMatchInterface $route_match) {
    $this->escortItemManager = $escort_manager;
    $this->routeMatch = $route_match;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.escort'),
      $container->get('context.repository'),
      $container->get('current_route_match')
    );
  }

  /**
   * Shows a list of escorts that can be added to a theme's layout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listEscorts(Request $request) {
    $build = [];
    $build['new'] = $this->listNewEscorts($request);
    $build['existing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Existing'),
    ];
    $build['existing']['form'] = $this->formBuilder()->getForm('Drupal\escort\Form\EscortEnableForm');
    return $build;
  }

  /**
   * Generate list of options for new escorts.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  protected function listNewEscorts(Request $request) {

    $build = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add New'),
    ];

    $headers = [
      ['data' => $this->t('Escort')],
      ['data' => $this->t('Category')],
      ['data' => $this->t('Operations')],
    ];

    // Order by category, and then by admin label.
    $definitions = $this->escortItemManager->getSortedDefinitions($this->escortItemManager->getDefinitions());

    $region = $request->query->get('region');
    $weight = $request->query->get('weight');
    $rows = [];
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $row = [];
      $row['title']['data'] = $plugin_definition['admin_label'];
      $row['category']['data'] = $plugin_definition['category'];
      $links['add'] = [
        'title' => $this->t('Create escort'),
        'url' => Url::fromRoute('escort.escort_add', ['plugin_id' => $plugin_id]),
      ];
      $this->ajaxLinkAttributes($links['add'], NULL, 'attributes', FALSE);
      if ($region) {
        $links['add']['query']['region'] = $region;
      }
      if (isset($weight)) {
        $links['add']['query']['weight'] = $weight;
      }
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
   * Builds the local actions for this listing.
   *
   * @return array
   *   An array of local actions for this listing.
   */
  protected function buildLocalActions() {
    $build = $this->localActionManager->getActionsForRoute($this->routeMatch->getRouteName());
    // Without this workaround, the action links will be rendered as <li> with
    // no wrapping <ul> element.
    if (!empty($build)) {
      $build['#prefix'] = '<ul class="action-links">';
      $build['#suffix'] = '</ul>';
    }
    return $build;
  }

}

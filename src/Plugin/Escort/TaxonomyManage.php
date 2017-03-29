<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\micon\MiconIconize;

/**
 * Defines a plugin for managing taxonomy terms.
 *
 * @Escort(
 *   id = "taxonomy_manage",
 *   admin_label = @Translation("Taxonomy Manage"),
 *   category = @Translation("Taxonomy"),
 * )
 */
class TaxonomyManage extends Aside implements ContainerFactoryPluginInterface {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = 'taxonomy_term';

  /**
   * The entity type bundle.
   *
   * @var string
   */
  protected $entityTypeBundle = 'taxonomy_vocabulary';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a LocalTasksEscort instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'text' => $this->t('Manage Terms'),
      'icon' => 'fa-tags',
      'bundles' => [],
      'type' => 'include',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function escortAccess(AccountInterface $account) {
    $entity_types = $this->entityTypeManager->getStorage($this->entityTypeBundle)->loadMultiple();

    // No entity types currently exist.
    if (empty($entity_types)) {
      return AccessResult::neutral();
    }

    // If checking whether a entity of a particular type may be created.
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // If checking whether a entity of any type may be created.
    if (\Drupal::moduleHandler()->moduleExists('taxonomy_access_fix')) {
      foreach ($entity_types as $entity_type) {
        if (taxonomy_access_fix_access('list terms', $entity_type)) {
          return AccessResult::allowed()->cachePerPermissions();
        }
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->entityTypeManager->getStorage($this->entityTypeBundle)->loadMultiple() as $entity) {
      $options[$entity->id()] = $entity->label();
    }
    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Bundles'),
      '#options' => $options,
      '#default_value' => $this->configuration['bundles'],
    ];
    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#options' => ['include' => $this->t('Include'), 'exclude' => $this->t('Exclude')],
      '#default_value' => $this->configuration['type'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    $this->configuration['bundles'] = array_filter($form_state->getValue('bundles'));
    $this->configuration['type'] = $form_state->getValue('type');
  }

  /**
   * {@inheritdoc}
   */
  protected function escortBuildAsideContent() {
    $build = [
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => ['class' => ['escort-grid']],
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition($this->entityTypeBundle)->getListCacheTags(),
        'contexts' => ['user.permissions'],
      ],
    ];

    $entities = $this->entityTypeManager->getStorage($this->entityTypeBundle)->loadMultiple();
    if ($bundles = $this->configuration['bundles']) {
      switch ($this->configuration['type']) {
        case 'include':
          $entities = array_intersect_key($entities, $bundles);
          break;

        case 'exclude':
          $entities = array_diff_key($entities, $bundles);
          break;
      }
    }

    $access = \Drupal::currentUser()->hasPermission('administer taxonomy');
    $has_taxonomy_access_fix = \Drupal::moduleHandler()->moduleExists('taxonomy_access_fix');

    foreach ($entities as $type) {
      $type_access = $access;
      if (!$type_access && $has_taxonomy_access_fix) {
        $type_access = taxonomy_access_fix_access('list terms', $type);
      }
      if ($type_access) {
        $title = $type->label();
        $title = MiconIconize::iconize($title)->addMatchPrefix('vocabulary');
        $build['#links'][$type->id()] = [
          'title' => $title,
          'url' => $type->toUrl('overview-form'),
        ];
      }
    }
    return $build;
  }

}

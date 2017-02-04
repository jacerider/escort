<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Escort(
 *   id = "node_create",
 *   admin_label = @Translation("Node Create"),
 *   category = @Translation("Node"),
 * )
 */
class NodeCreate extends Dropdown implements ContainerFactoryPluginInterface {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = 'node';

  /**
   * The entity type bundle.
   *
   * @var string
   */
  protected $entityTypeBundle = 'node_type';

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
      'bundles' => [],
      'type' => 'include',
    );
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
  protected function buildDropdown() {
    $build = [
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => ['class' => ['escort-grid']],
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('node_type')->getListCacheTags(),
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

    foreach ($entities as $type) {
      $access = $this->entityTypeManager->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $title = $type->label();
        if ($this->hasIconSupport()) {
          $title = micon($title)->setMatchString('content_type:' . $title);
        }
        $build['#links'][$type->id()] = [
          'title' => $title,
          'url' => new Url('node.add', array('node_type' => $type->id())),
        ];
      }
      $this->renderer->addCacheableDependency($build, $access);
    }
    return $build;
  }

}

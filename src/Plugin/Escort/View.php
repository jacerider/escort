<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Views;

/**
 * Defines a plugin for pulling in a view as an aside.
 *
 * @Escort(
 *   id = "view",
 *   admin_label = @Translation("View"),
 *   category = @Translation("View"),
 * )
 */
class View extends Aside implements ContainerFactoryPluginInterface {
  use EscortPluginLinkTrait;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * Adds a LocalTasksEscort instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\EntityStorageInterface $view_storage
   *   The entity storage class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $view_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->viewStorage = $view_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('view')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'view_id' => '',
      'view_display_id' => '',
      'view_argument' => '',
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $form = parent::escortForm($form, $form_state);

    $element_id = 'escort-view-display-select';
    $view = $form_state->getCompleteFormState()->getValue(['settings', 'view', 'view_id'], $this->configuration['view_id']);

    $form['view'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('View Configuration'),
      '#id' => $element_id,
    ];

    $form['view']['view_id'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#options' => $this->getViews(),
      '#default_value' => $view,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select -'),
      '#multiple' => FALSE,
      '#ajax' => [
        'callback' => [get_class($this), 'escortViewFormAjax'],
        'event' => 'change',
        'wrapper' => $element_id,
        'progress' => [
          'type' => 'throbber',
          'message' => t('Getting display Ids...'),
        ],
      ],
    ];

    $form['view']['view_display_id'] = [
      '#type' => 'select',
      '#title' => $this->t('View Display'),
      '#options' => [],
      '#default_value' => $this->configuration['view_display_id'],
      '#empty_option' => $this->t('- Select -'),
      '#multiple' => FALSE,
      '#wrapper_attributes' => [
        'id' => $element_id,
      ],
      '#states' => array(
        'visible' => array(
          ':input[name="settings[view][view_id]"]' => ['filled' => TRUE],
        ),
      ),
    ];

    if (!empty($view)) {
      $form['view']['view_display_id'] = [
        '#options' => $this->getViewDisplayIds($view),
        '#required' => TRUE,
      ] + $form['view']['view_display_id'];
    }

    $form['view']['view_argument'] = array(
      '#title' => 'Argument',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['view_argument'],
      '#states' => array(
        'visible' => array(
          ':input[name="settings[view][view_id]"]' => ['filled' => TRUE],
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    parent::escortSubmit($form, $form_state);
    $this->configuration['view_id'] = $form_state->getValue(['view', 'view_id']);
    $this->configuration['view_display_id'] = $form_state->getValue(['view', 'view_display_id']);
    $this->configuration['view_argument'] = $form_state->getValue(['view', 'view_argument']);
  }

  /**
   * AJAX function to get display IDs for a particular View.
   */
  public static function escortViewFormAjax(array &$form, FormStateInterface $form_state) {
    return $form['settings']['view'];
  }

  /**
   * Return aside content render array.
   */
  protected function escortBuildAsideContent() {
    $view_name = $this->configuration['view_id'];
    $display_id = $this->configuration['view_display_id'];
    $argument = $this->configuration['view_argument'];
    $view = Views::getView($view_name);
    // Someone may have deleted the View.
    if (!is_object($view)) {
      return parent::escortBuildAsideContent();
    }
    $arguments = [$argument];
    if (preg_match('/\//', $argument)) {
      $arguments = explode('/', $argument);
    }
    $view->setDisplay($display_id);
    $view->setArguments($arguments);
    $view->build($display_id);
    $view->preExecute();
    $view->execute($display_id);
    return $view->buildRenderable($display_id);
  }

  /**
   * Helper function to get all display ids.
   */
  protected function getViews() {
    $views = Views::getEnabledViews();
    $options = array();
    foreach ($views as $view) {
      if ($view->status()) {
        $options[$view->get('id')] = $view->get('label');
      }
    }
    return $options;
  }

  /**
   * Helper to get display ids for a particular View.
   */
  protected function getViewDisplayIds($entity_id) {
    $views = Views::getEnabledViews();
    $options = array();
    foreach ($views as $view) {
      if ($view->get('id') == $entity_id) {
        foreach ($view->get('display') as $display) {
          $options[$display['id']] = $display['display_title'];
        }
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($view = $this->viewStorage->load($this->configuration['view_id'])) {
      $dependencies['config'][] = $view->getConfigDependencyName();
    }
    return $dependencies;
  }

}

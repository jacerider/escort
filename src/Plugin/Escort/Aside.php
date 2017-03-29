<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Component\Serialization\Json;

/**
 * Defines a text plugin.
 *
 * @Escort(
 *   id = "aside",
 *   admin_label = @Translation("Aside"),
 *   category = @Translation("Basic"),
 *   no_ui = TRUE
 * )
 */
class Aside extends Text {

  /**
   * {@inheritdoc}
   */
  protected function baseConfigurationDefaults() {
    return [
      'display' => 'dropdown',
      'display_size' => 700,
      'ajax' => TRUE,
    ] + parent::baseConfigurationDefaults();
  }

  /**
   * {@inheritdoc}
   */
  public function mockConfiguration() {
    return [
      'ajax' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display type'),
      '#options' => [
        'dropdown' => $this->t('Dropdown'),
        'shelf' => $this->t('Shelf'),
        'modal' => $this->t('Modal'),
      ],
      '#default_value' => $this->configuration['display'],
    ];
    $form['display_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display size'),
      '#field_suffix' => 'px',
      '#default_value' => $this->configuration['display_size'],
      '#states' => [
        'visible' => [
          'select[name="settings[display]"]' => ['value' => 'modal'],
        ],
      ],
    ];
    $form['ajax'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use AJAX to display aside content'),
      '#default_value' => $this->configuration['ajax'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Process the escort's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      $this->configuration['display'] = $form_state->getValue('display');
      $this->configuration['ajax'] = $form_state->getValue('ajax');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $build['#attributes']['class'][] = 'escort-aside';
    if ($this->configuration['display'] == 'dropdown') {
      $build['aside'] = $this->escortBuildAside();
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function escortBuild() {
    $build = $this->escortBuildAsideTrigger();
    $build['#attributes']['class'][] = 'escort-aside-trigger';
    $build['#attributes']['data-escort-aside'] = $this->getEscort()->uuid();
    $build['#attributes']['data-escort-aside-display'] = $this->configuration['display'];
    $build['#attached']['library'][] = 'escort/escort.aside';

    // Modal specific additions.
    if ($this->configuration['display'] == 'modal') {
      $build['#attached']['library'][] = escort_dialog_library();
      $build['#attributes']['data-dialog-type'] = escort_dialog_type();
      $build['#attributes']['data-dialog-options'] = Json::encode([
        'width' => $this->configuration['display_size'],
      ]);
    }

    if ($this->configuration['ajax']) {
      $build['#attributes']['data-escort-ajax'] = '';
      $build['#attributes']['href'] = Url::fromRoute('escort.escort_render', ['escort' => $this->getEscort()->id()])->toString();
      $build['#attached']['library'][] = 'core/drupal.ajax';
    }
    return $build;
  }

  /**
   * Return aside trigger render array.
   */
  protected function escortBuildAsideTrigger() {
    if (empty($this->configuration['text'])) {
      return [];
    }
    return [
      '#tag' => 'a',
      '#icon' => $this->configuration['icon'],
      '#markup' => $this->configuration['text'],
    ];
  }

  /**
   * Return aside content render array.
   */
  protected function escortBuildAsideContent() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function escortBuildRegionSuffix() {
    if ($this->configuration['display'] == 'shelf') {
      return $this->escortBuildAside();;
    }
    return NULL;
  }

  /**
   * Return aside render array.
   */
  protected function escortBuildAside() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'escort-ajax-' . $this->getEscort()->uuid(),
        'class' => [
          'escort-aside-content',
          'escort-aside-display-' . $this->configuration['display'],
        ],
      ],
    ];
    if (!$this->configuration['ajax']) {
      $build['content'] = $this->escortBuildAsideContent();
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function escortBuildContent() {
    $build = $this->escortBuildAsideContent();
    if ($this->configuration['display'] == 'modal') {
      return $build;
    }
    $id = '#escort-ajax-' . $this->getEscort()->uuid();
    $response = new AjaxResponse();
    if (!empty($build)) {
      $response->addCommand(new HtmlCommand($id, $build));
    }
    return $response;
  }

}

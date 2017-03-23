<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a text plugin.
 *
 * @Escort(
 *   id = "aside",
 *   admin_label = @Translation("Aside"),
 *   category = @Translation("Basic"),
 * )
 */
class Aside extends Text {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content' => '',
      'display' => 'dropdown',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $form = parent::escortForm($form, $form_state);
    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#default_value' => $this->configuration['content'],
      '#required' => TRUE,
    ];
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display type'),
      '#options' => [
        'dropdown' => $this->t('Dropdown'),
        'shelf' => $this->t('Shelf'),
        // 'dialog' => $this->t('Dialog'),
      ],
      '#default_value' => $this->configuration['display'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    parent::escortSubmit($form, $form_state);
    $this->configuration['content'] = $form_state->getValue('content');
    $this->configuration['display'] = $form_state->getValue('display');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    if ($this->configuration['display'] == 'dropdown') {
      $build['aside'] = $this->escortBuildAside();;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function escortBuild() {
    $build = [
      '#tag' => 'a',
      '#icon' => $this->configuration['icon'],
      '#markup' => $this->configuration['text'],
      '#attributes' => ['class' => ['escort-aside-trigger']],
    ];
    return $build;
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
    $build['content']['#markup'] = $this->configuration['content'];
    return $build;
  }

}

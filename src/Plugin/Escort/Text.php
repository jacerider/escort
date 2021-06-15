<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a text plugin.
 *
 * @Escort(
 *   id = "text",
 *   admin_label = @Translation("Text"),
 *   category = @Translation("Basic"),
 * )
 */
class Text extends EscortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'text' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function mockConfiguration() {
    return [
      'text' => 'Escort Text',
      'icon' => 'fa-empire',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#default_value' => $this->configuration['text'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    $this->configuration['text'] = $form_state->getValue('text');
  }

  /**
   * {@inheritdoc}
   */
  protected function escortBuild() {
    return ['#markup' => $this->configuration['text']];
  }

}

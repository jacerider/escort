<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Escort(
 *   id = "broken",
 *   admin_label = @Translation("Broken/Missing"),
 *   category = @Translation("Escort"),
 * )
 */
class Broken extends EscortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->brokenMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function escortItemForm($form, FormStateInterface $form_state) {
    return $this->brokenMessage();
  }

  /**
   * Generate message with debugging information as to why the block is broken.
   *
   * @return array
   *   Render array containing debug information.
   */
  protected function brokenMessage() {
    $build['message'] = [
      '#markup' => $this->t('This escort is broken or missing. You may be missing content or you might need to enable the original module.'),
    ];

    return $build;
  }

}

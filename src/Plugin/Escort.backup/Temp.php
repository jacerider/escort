<?php

namespace Drupal\escort\Plugin\Escort;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Escort(
 *   id = "temp",
 *   admin_label = @Translation("Temp"),
 *   category = @Translation("Basic"),
 * )
 */
class Temp extends EscortPluginAsideBase {

  /**
   * {@inheritdoc}
   */
  public function buildItems() {
    $items = [];
    $items[] = ['#markup' => 'hi'];
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildLink() {
    return [
      '#tag' => 'a',
      '#markup' => 'hi',
      '#icon' => $this->configuration['icon'],
    ];
  }

}

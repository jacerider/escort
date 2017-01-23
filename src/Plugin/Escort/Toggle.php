<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Component\Utility\Html;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Escort(
 *   id = "toggle",
 *   admin_label = @Translation("Toggle"),
 *   category = @Translation("Basic"),
 *   no_ui = TRUE,
 * )
 */
class Toggle extends EscortPluginBase implements EscortPluginImmediateInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'region' => NULL,
      'event' => 'hover',
      'icon' => 'fa-bars',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#tag'] = 'a';
    $build['#attributes']['class'][] = 'escort-toggle';
    $build['#attributes']['data-region'] = $this->configuration['region'];
    $build['#attributes']['data-event'] = $this->configuration['event'];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getBodyAttributes() {
    return ['class' => [Html::cleanCssIdentifier('hide-escort-' . $this->configuration['region'])]];
  }

}

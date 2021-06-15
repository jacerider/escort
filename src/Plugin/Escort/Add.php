<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Url;
use Drupal\escort\EscortAjaxTrait;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Escort(
 *   id = "add",
 *   admin_label = @Translation("Add"),
 *   no_ui = TRUE,
 * )
 */
class Add extends EscortPluginBase implements EscortPluginImmediateInterface {
  use EscortAjaxTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'region' => NULL,
      'icon' => 'fa-plus',
      'weight' => 200,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function escortBuild() {
    $build = [];
    $build['#tag'] = 'a';
    $build['#attributes']['href'] = Url::fromRoute('escort.escort_library', [], [
      'query' => [
        'region' => $this->configuration['region'],
        'weight' => $this->configuration['weight'],
      ],
    ])->toString();
    $build['#attributes']['class'][] = 'escort-add';

    // Dialog ajaxify.
    $this->ajaxLinkAttributes($build);

    return $build;
  }

}

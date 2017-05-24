<?php

namespace Drupal\escort\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\escort\Entity\EscortInterface;

/**
 * Controller for building the escort add form.
 */
class EscortController extends ControllerBase {

  /**
   * Build the escort ajax response.
   *
   * @param \Drupal\escort\Entity\EscortInterface $escort
   *   The plugin ID for the escort.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function render(EscortInterface $escort = NULL) {
    $plugin = $escort->getPlugin();
    return $plugin->buildContent();
  }

}

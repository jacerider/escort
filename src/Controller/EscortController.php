<?php

namespace Drupal\escort\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\escort\Entity\EscortInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;

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
  public function ajax(EscortInterface $escort = NULL) {
    $plugin = $escort->getPlugin();
    $build = $plugin->buildAjax();
    $id = '#escort-ajax-' . $escort->uuid();
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand($id, $build));
    return $response;
  }

}

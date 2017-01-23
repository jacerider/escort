<?php

namespace Drupal\escort\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller to list escorts.
 */
class EscortListController extends ControllerBase {

  /**
   * Shows the escort administration page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing(Request $request = NULL) {
    return $this->entityTypeManager()->getListBuilder('escort')->render($request);
  }

}

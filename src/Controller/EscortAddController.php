<?php

namespace Drupal\escort\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for building the escort add form.
 */
class EscortAddController extends ControllerBase {

  /**
   * Build the escort add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the escort.
   *
   * @return array
   *   The escort edit form.
   */
  public function escorItemAddForm($plugin_id) {
    // Create an escort entity.
    $entity = $this->entityTypeManager()->getStorage('escort')->create(array('plugin' => $plugin_id));
    return $this->entityFormBuilder()->getForm($entity);
  }

}

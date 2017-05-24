<?php

namespace Drupal\escort\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to close content in a dialog in a off-canvas tray.
 *
 * @ingroup ajax
 */
class EscortAsideDestinationCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'escortAsideDestination',
    ];
  }

}

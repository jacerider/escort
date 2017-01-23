<?php

namespace Drupal\escort\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Escort entities.
 */
interface EscortInterface extends ConfigEntityInterface {

  /**
   * Denotes that a block is not enabled in any region and should not be shown.
   */
  const ESCORT_REGION_NONE = -1;

}

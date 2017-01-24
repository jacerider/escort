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

  /**
   * A list of operation links that are added to each escort item in admin mode.
   *
   * @return array
   *   An array of links to be themed. Each link should be itself an array, with
   *   the following elements:
   *   - title: The link text.
   *   - url: (optional) The \Drupal\Core\Url object to link to. If omitted, no
   *     anchor tag is printed out.
   *   - attributes: (optional) Attributes for the anchor, or for the <span>
   *     tag used in its place if no 'href' is supplied. If element 'class' is
   *     included, it must be an array of one or more class names.
   */
  public function buildOps();

}

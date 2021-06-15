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
   * Determines whether the entity is new.
   *
   * An escort is temporary if it has been generated programatically and does
   * not have stored data.
   *
   * @return bool
   *   TRUE if the entity is temporary, or FALSE if the entity is not.
   *
   * @see \Drupal\escort\Entity\EscortInterface::enforceIsTemporary()
   */
  public function isTemporary();

  /**
   * Escorts can be dynamically generated without being saved.
   *
   * Settings an escort as temporary will assign it a temporary ID useful for
   * caching purposes.
   *
   * @param bool $value
   *   (optional) Whether the entity should be forced to be temporary. Defaults
   *   to TRUE.
   *
   * @return $this
   */
  public function enforceIsTemporary();

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

  /**
   * {@inheritDoc}
   *
   * @return \Drupal\escort\Plugin\Escort\EscortPluginInterface
   *   The plugin.
   */
  public function getPlugin();

}

<?php

namespace Drupal\escort;

/**
 * Defines a common interface for country managers.
 */
interface EscortRegionManagerInterface {

  /**
   * Separator used between region and section within array key.
   */
  const ESCORT_REGION_SECTION_SEPARATOR = '_';

  /**
   * Returns a list of regions.
   *
   * @return array
   *   An array of region => section => section name.
   */
  public function getRegions();

}

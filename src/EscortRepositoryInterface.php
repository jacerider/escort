<?php

namespace Drupal\escort;

interface EscortRepositoryInterface {

  /**
   * Returns an array of regions and their escort entities.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata[] $cacheable_metadata
   *   (optional) List of CacheableMetadata objects, keyed by region. This is
   *   by reference and is used to pass this information back to the caller.
   *
   * @return array
   *   The array is first keyed by region machine name, with the values
   *   containing an array keyed by escort ID, with escort entities as the values.
   */
  public function getEscortsPerRegion(array &$cacheable_metadata = []);

}

<?php

namespace Drupal\escort;

/**
 * Provides an interface for URL path matchers.
 */
interface EscortPathMatcherInterface {

  /**
   * Checks if the current page is an escort admin page.
   *
   * @return bool
   *   TRUE if the current page is an escort admin page.
   */
  public function isAdmin();

}

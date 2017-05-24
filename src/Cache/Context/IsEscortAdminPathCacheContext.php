<?php

namespace Drupal\escort\Cache\Context;

use Drupal\escort\EscortPathMatcherInterface;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines a cache context for whether the URL is within the escort admin area.
 *
 * Cache context ID: 'url.path.is_escort_admin'.
 */
class IsEscortAdminPathCacheContext implements CacheContextInterface {

  /**
   * @var \Drupal\escort\EscortPathMatcherInterface
   */
  protected $escortPathMatcher;

  /**
   * Constructs an IsEscortAdminPathCacheContext object.
   *
   * @param \Drupal\escort\EscortPathMatcherInterface $escort_path_matcher
   */
  public function __construct(EscortPathMatcherInterface $escort_path_matcher) {
    $this->escortPathMatcher = $escort_path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Is escort admin page');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return 'is_escort_admin.' . (int) $this->escortPathMatcher->isAdmin();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}

<?php

namespace Drupal\escort;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\escort\Entity\Escort;
use Drupal\Core\Session\AccountProxy;

/**
 * Provides a repository for Escort config entities.
 */
class EscortRepository implements EscortRepositoryInterface {

  /**
   * The escorts.
   *
   * @var array
   *   An array of \Drupal\escort\Entity\EscortInterface
   */
  protected $escorts;

  /**
   * The escort storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $escortStorage;

  /**
   * The region manager.
   *
   * @var \Drupal\escort\EscortRegionManagerInterface
   */
  protected $escortRegionManager;

  /**
   * The escort patch matcher.
   *
   * @var \Drupal\escort\EscortPathMatcherInterface
   */
  protected $escortPathMatcher;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new EscortRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EscortRegionManagerInterface $escort_region_manager, ContextHandlerInterface $context_handler, EscortPathMatcherInterface $escort_path_matcher, AccountProxy $current_user) {
    $this->escortStorage = $entity_type_manager->getStorage('escort');
    $this->escortRegionManager = $escort_region_manager;
    $this->contextHandler = $context_handler;
    $this->escortPathMatcher = $escort_path_matcher;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function hasEscortOfType($type_id) {
    foreach ($this->getEscortsPerRegion() as $group_id => $sections) {
      foreach ($sections as $section_id => $section) {
        foreach ($section as $escort_id => $escort) {
          if ($escort->getPluginId() == $type_id) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEscortsPerRegion(array &$cacheable_metadata = NULL) {
    if (!isset($this->escorts) || is_array($cacheable_metadata)) {
      $cacheable_metadata = is_array($cacheable_metadata) ? $cacheable_metadata : [];
      $raw_regions = $this->escortRegionManager->getRaw(TRUE);
      $regions = $this->escortRegionManager->getRegions(TRUE);
      $full = array();
      foreach ($this->escortStorage->loadByProperties(array('region' => array_keys($regions))) as $escort_id => $escort) {
        /** @var \Drupal\escort\Entity\EscortInterface $escort */
        $access = $escort->access('view', NULL, TRUE);
        $region = $escort->getRegion();
        $group_id = $this->escortRegionManager->getGroupId($region);
        $section_id = $this->escortRegionManager->getSectionId($region);
        if (!isset($cacheable_metadata[$region])) {
          $cacheable_metadata[$group_id][$section_id] = CacheableMetadata::createFromObject($access);
        }
        else {
          $cacheable_metadata[$group_id][$section_id] = $cacheable_metadata[$region]->merge(CacheableMetadata::createFromObject($access));
        }

        // Set the contexts on the escort before checking access.
        if ($access->isAllowed()) {
          $full[$group_id][$section_id][$escort_id] = $escort;
        }
      }

      // Check if admin and add additional dynamic escorts.
      $this->addAddEscorts($full, $raw_regions);

      // Merge it with the actual values to maintain the region ordering.
      $empty = array_fill_keys(array_keys($raw_regions), array());
      $regions = array_filter(array_intersect_key(array_merge($empty, $full), $empty));
      foreach ($regions as $group_id => &$sections) {
        $empty = array_fill_keys(array_keys($raw_regions[$group_id]['sections']), array());
        $sections = array_filter(array_intersect_key(array_merge($empty, $sections), $empty));
      }

      // Sort sections.
      foreach ($regions as $group_id => &$sections) {
        foreach ($sections as $section_id => &$escorts) {
          // Allow escorts to remove themselves based on region requirements.
          foreach ($escorts as $escort_id => $escort) {
            $plugin = $escort->getPlugin();
            $require_region = $plugin->requireRegion();
            if ($require_region && empty($regions[$require_region])) {
              unset($escorts[$escort_id]);
            }
          }
          // Suppress errors because PHPUnit will indirectly modify the
          // contents, triggering https://bugs.php.net/bug.php?id=50688.
          @uasort($escorts, 'Drupal\escort\Entity\Escort::sort');
        }
      }
      $this->escorts = $regions;
    }
    return $this->escorts;
  }

  /**
   * Add 'add' escorts to repository.
   *
   * @var array $escorts
   *   The currest escort list.
   * @var array $raw_regions
   *   The raw region data.
   */
  protected function addAddEscorts(&$escorts, $raw_regions) {
    if ($this->currentUser->hasPermission('administer escort') && $this->escortPathMatcher->isAdmin()) {
      foreach ($raw_regions as $group_id => $group) {
        $offset = 1;
        foreach ($group['sections'] as $section_id => $section) {
          // Create 'add' escort to every available region.
          $escort = $this->createEscort('add', [
            'region' => $group_id . EscortRegionManagerInterface::ESCORT_REGION_SECTION_SEPARATOR . $section_id,
          ], $group_id, $section_id, 1000 * $offset);
          $escorts[$group_id][$section_id]['add'] = $escort;
          $offset = -1;
        }
      }
    }
  }

  /**
   * Create a dynamic escort.
   */
  protected function createEscort($plugin_id, $plugin_settings, $group_id, $section_id, $weight = 0) {
    $plugin = \Drupal::service('plugin.manager.escort')
      ->createInstance($plugin_id, $plugin_settings);
    $plugin->addCacheContexts(['route']);
    $plugin->addCacheTags(['config:escort.config']);
    $escort = Escort::create([
      'plugin' => $plugin_id,
      'weight' => $weight,
      'region' => $group_id . EscortRegionManagerInterface::ESCORT_REGION_SECTION_SEPARATOR . $section_id,
    ])->setPlugin($plugin)->enforceIsTemporary();
    return $escort;
  }

}

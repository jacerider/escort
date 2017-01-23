<?php

namespace Drupal\escort;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\escort\Entity\Escort;

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
   * Constructs a new EscortRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EscortRegionManagerInterface $escort_region_manager) {
    $this->escortStorage = $entity_type_manager->getStorage('escort');
    $this->escortRegionManager = $escort_region_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEscortsPerRegion(array &$cacheable_metadata = NULL) {
    if (!isset($this->escorts) || is_array($cacheable_metadata)) {
      $cacheable_metadata = is_array($cacheable_metadata) ? $cacheable_metadata : [];
      $raw_regions = $this->escortRegionManager->getRawRegions();
      $regions = $this->escortRegionManager->getRegions();
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

      // Check for toggle elements.
      foreach ($raw_regions as $group_id => $group) {
        // Check if we have a toggle request and that the toggle region exists.
        if (isset($group['toggle']) && is_array($group['toggle'])) {
          foreach ($group['toggle'] as $data) {
            // Check to make sure region is not empty.
            if (isset($full[$data['region']])) {
              // Create a placeholder plugin and escort entity.
              $plugin = \Drupal::service('plugin.manager.escort')
                ->createInstance('toggle', ['region' => $data['region'], 'event' => $data['event']]);
              $escort = Escort::create([
                'plugin' => 'toggle',
                'weight' => $data['weight'],
                'region' => $group_id . EscortRegionManagerInterface::ESCORT_REGION_SECTION_SEPARATOR . $data['section'],
              ])->setPlugin($plugin);
              $full[$group_id][$data['section']][$group_id . '_' . $data['section'] . '_' . $data['region'] . '_toggle'] = $escort;
            }
          }
        }
      }

      // Merge it with the actual values to maintain the region ordering.
      $empty = array_fill_keys(array_keys($raw_regions), array());
      $escorts = array_filter(array_intersect_key(array_merge($empty, $full), $empty));
      foreach ($escorts as $group_id => &$sections) {
        $empty = array_fill_keys(array_keys($raw_regions[$group_id]['sections']), array());
        $sections = array_filter(array_intersect_key(array_merge($empty, $sections), $empty));
      }

      // Sort sections.
      foreach ($escorts as $group_id => &$sections) {
        foreach ($sections as &$escort) {
          // Suppress errors because PHPUnit will indirectly modify the
          // contents, triggering https://bugs.php.net/bug.php?id=50688.
          @uasort($escort, 'Drupal\escort\Entity\Escort::sort');
        }
      }
      $this->escorts = $escorts;
    }
    return $this->escorts;
  }

}

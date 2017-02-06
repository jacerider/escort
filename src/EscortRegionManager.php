<?php

namespace Drupal\escort;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides list of regions.
 */
class EscortRegionManager implements EscortRegionManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Creates a new EscortRegionManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('escort.config');
  }

  /**
   * Get an array of all regions.
   *
   * @return array
   *   An array of regions.
   */
  public static function rawRegions() {
    $regions = [
      'top' => [
        'label' => t('Top'),
        'type' => 'horizontal',
        'sections' => [
          'left' => [
            'label' => t('Left'),
          ],
          'right' => [
            'label' => t('Right'),
          ],
        ],
      ],
      'bottom' => [
        'label' => t('Bottom'),
        'type' => 'horizontal',
        'sections' => [
          'left' => [
            'label' => t('Left'),
          ],
          'right' => [
            'label' => t('Right'),
          ],
        ],
      ],
      'left' => [
        'label' => t('Left'),
        'type' => 'vertical',
        'sections' => [
          'top' => [
            'label' => t('Top'),
          ],
          'bottom' => [
            'label' => t('Bottom'),
          ],
        ],
      ],
      'right' => [
        'label' => t('Right'),
        'type' => 'vertical',
        'sections' => [
          'top' => [
            'label' => t('Top'),
          ],
          'bottom' => [
            'label' => t('Bottom'),
          ],
        ],
      ],
      'mini' => [
        'label' => t('Mini'),
        'type' => 'horizontal',
        'sections' => [
          'left' => [
            'label' => t('Left'),
          ],
          'right' => [
            'label' => t('Right'),
          ],
        ],
      ],
    ];
    return $regions;
  }

  /**
   * Return the raw regions.
   *
   * @param bool $enabled_only
   *   Include only regions enabled via settings.
   *
   * @return array
   *   An array of regions.
   */
  public function getRaw($enabled_only = FALSE, $excluded_groups = []) {
    $regions = static::rawRegions();
    if ($enabled_only && $enabled = $this->config->get('enabled')) {
      $regions = array_intersect_key($regions, $enabled);
    }
    if (!empty($excluded_groups)) {
      $regions = array_diff_key($regions, array_flip($excluded_groups));
    }
    return $regions;
  }

  /**
   * Get a flat array of all groups.
   *
   * @param bool $enabled_only
   *   Include only regions enabled via settings.
   * @param array $excluded_groups
   *   An array of group ids to exclude.
   *
   * @return array
   *   An array of group_id => name.
   */
  public function getGroups($enabled_only = FALSE, $excluded_groups = []) {
    $groups = [];
    foreach ($this->getRaw($enabled_only, $excluded_groups) as $group_id => $group) {
      $groups[$group_id] = $group['label'];
    }
    return $groups;
  }

  /**
   * Get a flat array of all regions.
   *
   * @param bool $enabled_only
   *   Include only regions enabled via settings.
   * @param array $excluded_groups
   *   An array of group ids to exclude.
   *
   * @return array
   *   An array of group_id . section_id => name pairs.
   */
  public function getRegions($enabled_only = FALSE, $excluded_groups = []) {
    $regions = [];
    foreach ($this->getRaw($enabled_only, $excluded_groups) as $group_id => $group) {
      foreach ($group['sections'] as $section_id => $section) {
        $regions[$group_id . self::ESCORT_REGION_SECTION_SEPARATOR . $section_id] = $group['label'] . ': ' . $section['label'];
      }
    }
    return $regions;
  }

  /**
   * Get the group_id from the group_id . section_id.
   *
   * @var $region_id string
   *   The region id.
   *
   * @return string
   *   The region group id.
   */
  public function getGroupId($region_id) {
    foreach (static::rawRegions() as $group_id => $group) {
      foreach ($group['sections'] as $section_id => $name) {
        if ($region_id == $group_id . self::ESCORT_REGION_SECTION_SEPARATOR . $section_id) {
          return $group_id;
        }
      }
    }
    return NULL;
  }

  /**
   * Get the section_id from the group_id . section_id.
   *
   * @var $region_id string
   *   The region id.
   *
   * @return string
   *   The region section id.
   */
  public function getSectionId($region_id) {
    foreach (static::rawRegions() as $group_id => $group) {
      foreach ($group['sections'] as $section_id => $name) {
        if ($region_id == $group_id . self::ESCORT_REGION_SECTION_SEPARATOR . $section_id) {
          return $section_id;
        }
      }
    }
    return NULL;
  }

  /**
   * Get the label from the group_id OR group_id . section_id.
   *
   * @var $region_or_group_id string
   *   The region id.
   *
   * @return string
   *   The region group label.
   */
  public function getGroupLabel($region_or_group_id) {
    $regions = static::rawRegions();
    $title = '';
    $group_id = isset($regions[$region_or_group_id]) ? $region_or_group_id : $this->getGroupId($region_or_group_id);
    if ($group_id && isset($regions[$group_id])) {
      $title = $regions[$group_id]['label'];
    }
    return $title;
  }

  /**
   * Get the type from the group_id OR group_id . section_id.
   *
   * @var $region_or_group_id string
   *   The region id.
   *
   * @return string
   *   The region group type.
   */
  public function getGroupType($region_or_group_id) {
    $regions = static::rawRegions();
    $type = '';
    $group_id = isset($regions[$region_or_group_id]) ? $region_or_group_id : $this->getGroupId($region_or_group_id);
    if ($group_id && isset($regions[$group_id])) {
      $type = $regions[$group_id]['type'];
    }
    return $type;
  }

}

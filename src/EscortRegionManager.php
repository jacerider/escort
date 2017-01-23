<?php

namespace Drupal\escort;

/**
 * Provides list of regions.
 */
class EscortRegionManager implements EscortRegionManagerInterface {

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
        'sections' => [
          'first' => t('Left'),
          'second' => t('Right'),
        ],
        'toggle' => [
          ['section' => 'first', 'region' => 'left', 'weight' => -100, 'event' => 'hover'],
          ['section' => 'first', 'region' => 'bottom', 'weight' => -99, 'event' => 'click'],
        ],
      ],
      'bottom' => [
        'label' => t('Bottom'),
        'sections' => [
          'first' => t('Left'),
          'second' => t('Right'),
        ],
        'toggle' => [
          ['section' => 'second', 'region' => 'right', 'weight' => 100, 'event' => 'hover'],
        ],
      ],
      'left' => [
        'label' => t('Left'),
        'sections' => [
          'first' => t('Top'),
          'second' => t('Bottom'),
        ],
      ],
      'right' => [
        'label' => t('Right'),
        'sections' => [
          'first' => t('Top'),
          'second' => t('Bottom'),
        ],
      ],
    ];
    return $regions;
  }

  /**
   * Return the raw regions.
   *
   * @return array
   *   An array of regions.
   */
  public function getRawRegions() {
    return static::rawRegions();
  }

  /**
   * Get a flat array of all regions.
   *
   * @return array
   *   An array of group_id . section_id => name pairs.
   */
  public function getRegions() {
    $regions = [];
    foreach ($this->getRawRegions() as $group_id => $region) {
      foreach ($region['sections'] as $section_id => $name) {
        $regions[$group_id . self::ESCORT_REGION_SECTION_SEPARATOR . $section_id] = $region['label'] . ': ' . $name;
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
   *   The region base id.
   */
  public function getGroupId($region_id) {
    foreach ($this->getRawRegions() as $group_id => $region) {
      foreach ($region['sections'] as $section_id => $name) {
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
   *   The region base id.
   */
  public function getSectionId($region_id) {
    foreach ($this->getRawRegions() as $group_id => $region) {
      foreach ($region['sections'] as $section_id => $name) {
        if ($region_id == $group_id . self::ESCORT_REGION_SECTION_SEPARATOR . $section_id) {
          return $section_id;
        }
      }
    }
    return NULL;
  }

}

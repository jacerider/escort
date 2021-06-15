<?php

namespace Drupal\escort\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\escort\EscortRegionManagerInterface;

/**
 * Provides a render element for Escort.
 *
 * @RenderElement("escort")
 */
class Escort extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'escort',
      '#attributes' => [
        'id' => 'escort',
      ],
      '#pre_render' => [
        [$class, 'preRenderEscort'],
      ],
    ];
  }

  /**
   * Builds the Escort as a structured array ready for drupal_render().
   *
   * Since building the escort takes some time, it is done just prior to
   * rendering to ensure that it is built only if it will be displayed.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   A renderable array.
   *
   * @see escort_page_top()
   */
  public static function preRenderEscort($element) {
    $view_builder = \Drupal::service('entity_type.manager')->getViewBuilder('escort');
    $regionManager = \Drupal::service('escort.region_manager');
    $regions = \Drupal::service('escort.repository')->getEscortsPerRegion();
    $config = \Drupal::config('escort.config')->get('regions');
    $is_admin = \Drupal::service('escort.path.matcher')->isAdmin();
    // Element cache build.
    $element_cachable_metadata = CacheableMetadata::createFromRenderArray($element);
    foreach ($regions as $group_id => $sections) {
      $element[$group_id] = [
        '#theme' => 'escort_region',
        '#attached' => ['library' => ['escort/escort.region.' . $group_id]],
        '#attributes' => [
          'id' => 'escort-' . $group_id,
          'role' => 'group',
          'aria-label' => t('Site administration toolbar'),
          'class' => [
            Html::cleanCssIdentifier('escort-' . $group_id),
            Html::cleanCssIdentifier('escort-' . $regionManager->getGroupType($group_id)),
          ],
          'data-region' => $group_id,
          'data-offset-' . $regionManager->getGroupPosition($group_id) => '',
        ],
      ];
      if (!empty($config[$group_id]['icon_only'])) {
        $element[$group_id]['#attributes']['class'][] = 'icon-only';
      }
      // Region cache build.
      $region_cacheable_metadata = CacheableMetadata::createFromRenderArray($element[$group_id]);
      foreach ($sections as $section_id => $escorts) {
        $region_id = $group_id . EscortRegionManagerInterface::ESCORT_REGION_SECTION_SEPARATOR . $section_id;
        $id = Html::cleanCssIdentifier('escort-' . $region_id);
        $element[$group_id][$section_id] = [
          '#theme' => 'escort_section',
          '#attributes' => [
            'id' => $id,
            'class' => [
              $id,
            ],
          ],
          '#sorted' => TRUE,
        ];
        if ($is_admin) {
          $element[$group_id][$section_id]['#attributes']['class'][] = 'escort-sort';
          $element[$group_id][$section_id]['#attributes']['data-escort-region'][] = $region_id;
        }
        // Section cache build.
        $section_cacheable_metadata = CacheableMetadata::createFromRenderArray($element[$group_id][$section_id]);
        foreach ($escorts as $key => $escort) {
          $plugin = $escort->getPlugin();
          $content = $view_builder->view($escort);
          if (!Element::isEmpty($content)) {
            // Add escort to section.
            $element[$group_id][$section_id][$key] = $content;
          }
          // Section cache add.
          $section_cacheable_metadata = $section_cacheable_metadata->merge(CacheableMetadata::createFromRenderArray($content));
          // Allow placement of additional render arrays within the region
          // wrapper.
          if ($build_suffix = $plugin->buildRegionSuffix()) {
            $element[$group_id]['#after'][$key] = $build_suffix;
            $section_cacheable_metadata = $section_cacheable_metadata->merge(CacheableMetadata::createFromRenderArray($build_suffix));
          }
          // Allow placement of additional render arrays within the element
          // wrapper.
          if ($build_suffix = $plugin->buildElementSuffix()) {
            $element['#after'][$key] = $build_suffix;
            $element_cachable_metadata = $element_cachable_metadata->merge(CacheableMetadata::createFromRenderArray($build_suffix));
          }
        }
        // Section cache apply.
        $section_cacheable_metadata->applyTo($element[$group_id][$section_id]);
        // If #after is not empty we need to add cache data.
        if (!empty($element[$group_id]['#after'])) {
          $section_cacheable_metadata->applyTo($element[$group_id]['#after']);
        }
        // Region cache add.
        $region_cacheable_metadata = $region_cacheable_metadata->merge(CacheableMetadata::createFromRenderArray($element[$group_id][$section_id]));
      }
      // Region cache apply.
      $region_cacheable_metadata->applyTo($element[$group_id]);
      // Element cache add.
      $element_cachable_metadata = $element_cachable_metadata->merge(CacheableMetadata::createFromRenderArray($element[$group_id]));
    }
    // Element cache apply.
    $element_cachable_metadata->applyTo($element);
    // If #after is not empty we need to add cache data.
    if (!empty($element['#after'])) {
      $section_cacheable_metadata->applyTo($element['#after']);
    }
    return $element;
  }

}

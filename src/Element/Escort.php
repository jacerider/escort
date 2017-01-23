<?php

namespace Drupal\escort\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Component\Utility\Html;

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
    return array(
      '#pre_render' => array(
        array($class, 'preRenderEscort'),
      ),
      '#attached' => array(
        'library' => array(
          'escort/escort',
        ),
      ),
    );
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
    $regions = \Drupal::service('escort.repository')->getEscortsPerRegion();
    foreach ($regions as $group_id => $sections) {
      $element[$group_id] = [
        '#theme' => 'escort_region',
        '#attributes' => array(
          'id' => 'escort-' . $group_id,
          'role' => 'group',
          'aria-label' => t('Site administration toolbar'),
          'class' => [
            Html::cleanCssIdentifier('escort-' . $group_id),
          ],
        ),
      ];
      foreach ($sections as $section_id => $escorts) {
        $id = Html::cleanCssIdentifier('escort-' . $section_id);
        $element[$group_id][$section_id] = [
          '#theme' => 'escort_section',
          '#attributes' => array(
            'id' => $id,
            'class' => [
              $id,
            ],
          ),
          '#sorted' => TRUE,
        ];
        foreach ($escorts as $key => $escort) {
          $element[$group_id][$section_id][$key] = $view_builder->view($escort);
        }
      }
    }
    return $element;
  }

}

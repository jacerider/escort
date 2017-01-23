<?php

namespace Drupal\escort\Plugin\Escort;

/**
 * Defines a base escort implementation that most escorts plugins will extend.
 *
 * This abstract class provides the generic escort configuration form, default
 * escort settings, and handling for general user-defined escort visibility
 * settings.
 *
 * @ingroup escort_api
 */
abstract class EscortPluginMultipleBase extends EscortPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $provideMultiple = TRUE;

  /**
   * Ignored for plugins that provide multiple items.
   *
   * {@inheritdoc}
   */
  public function build() {
    return NULL;
  }

  /**
   * Builds and returns the renderable array for this escort plugin.
   *
   * Should return an array of multiple renderable arrays.
   *
   * If a escort should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the escort.
   *
   * @see \Drupal\escort\EscortViewBuilder
   */
  public function buildItems() {
    return NULL;
  }

}

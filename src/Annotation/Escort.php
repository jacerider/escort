<?php

namespace Drupal\escort\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Escort annotation object.
 *
 * @ingroup escort_api
 *
 * @Annotation
 */
class Escort extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the escort.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';

  /**
   * The category in the admin UI where the escort will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * A boolean stating that escorts of this type cannot be created through the
   * UI.
   *
   * @var bool
   */
  public $no_ui = FALSE;

}

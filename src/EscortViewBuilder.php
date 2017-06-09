<?php

namespace Drupal\escort;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\escort\Entity\Escort;
use Drupal\escort\Entity\EscortInterface;
use Drupal\escort\Plugin\Escort\EscortPluginImmediateInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a Escort view builder.
 */
class EscortViewBuilder extends EntityViewBuilder {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new EscortViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple(array($entity), $view_mode, $langcode);
    return reset($build);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\escort\EscortInterface[] $entities */
    $build = array();
    foreach ($entities as $entity) {
      $entity_id = $entity->id();
      $plugin = $entity->getPlugin();

      $cache_tags = Cache::mergeTags($this->getCacheTags(), $entity->getCacheTags());
      $cache_tags = Cache::mergeTags($cache_tags, $plugin->getCacheTags());

      // Create the render array for the escort as a whole.
      // @see template_preprocess_escort().
      $build[$entity_id] = array(
        '#cache' => [
          'keys' => ['entity_view', 'escort', $entity->id()],
          'contexts' => Cache::mergeContexts(
            $entity->getCacheContexts(),
            $plugin->getCacheContexts()
          ),
          'tags' => $cache_tags,
          'max-age' => $plugin->getCacheMaxAge(),
        ],
        '#weight' => $entity->getWeight(),
      );

      // Add escort admin cache context.
      // @TODO There may be a better way to ignore caching on admin pages.
      $build[$entity_id]['#cache']['contexts'][] = 'url.path.is_escort_admin';

      // Allow altering of cacheability metadata or setting #create_placeholder.
      $this->moduleHandler->alter(['escort_build', "escort_build_" . $plugin->getBaseId()], $build[$entity_id], $plugin);

      if ($plugin instanceof EscortPluginImmediateInterface || $plugin->isImmediate()) {
        // Immediately build a #pre_render-able escort, since this escort cannot
        // be built lazily.
        $build[$entity_id] += static::buildPreRenderableEscort($entity, $this->moduleHandler());
      }
      else {
        // Assign a #lazy_builder callback, which will generate a #pre_render-
        // able escort lazily (when necessary).
        $build[$entity_id] += [
          '#lazy_builder' => [static::class . '::lazyBuilder',
            [
              $entity_id,
              $view_mode,
              $langcode,
            ],
          ],
        ];
      }
    }

    return $build;
  }

  /**
   * The #lazy_builder callback; builds a #pre_render-able escort.
   *
   * @param int $entity_id
   *   A escort config entity ID.
   * @param string $view_mode
   *   The view mode the escort is being viewed in.
   *
   * @return array
   *   A render array with a #pre_render callback to render the escort.
   */
  public static function lazyBuilder($entity_id, $view_mode) {
    return static::buildPreRenderableEscort(Escort::load($entity_id), \Drupal::service('module_handler'));
  }

  /**
   * Builds a #pre_render-able escort render array.
   *
   * @param \Drupal\escort\EscortInterface $entity
   *   A escort config entity.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param array $content
   *   (optional) The render array to use as the content. If not supplied,
   *   the rendering will happen in #pre_render.
   *
   * @return array
   *   A render array with a #pre_render callback to render the escort.
   */
  protected static function buildPreRenderableEscort(EscortInterface $entity, ModuleHandlerInterface $module_handler, $content = NULL) {
    $plugin = $entity->getPlugin();
    $plugin_id = $plugin->getPluginId();
    $base_id = $plugin->getBaseId();
    $derivative_id = $plugin->getDerivativeId();
    $configuration = $plugin->getConfiguration();

    // Inject runtime contexts.
    if ($plugin instanceof ContextAwarePluginInterface) {
      $contexts = \Drupal::service('context.repository')->getRuntimeContexts($plugin->getContextMapping());
      \Drupal::service('context.handler')->applyContextMapping($plugin, $contexts);
    }

    $build = [
      '#theme' => 'escort_container',
      '#weight' => $entity->getWeight(),
      '#configuration' => $configuration,
      '#plugin_id' => $plugin_id,
      '#base_plugin_id' => $base_id,
      '#derivative_plugin_id' => $derivative_id,
      '#id' => $entity->id(),
      '#pre_render' => [
        static::class . '::preRender',
      ],
      '#is_escort_admin' => FALSE,
      // Add the entity so that it can be used in the #pre_render method.
      '#escort' => $entity,
      'children' => NULL,
    ];

    // If an alter hook wants to modify the block contents, it can append
    // another #pre_render hook.
    $module_handler->alter(['escort_view', "escort_view_$base_id"], $build, $plugin);

    return $build;
  }

  /**
   * The #pre_render callback for building a escort.
   *
   * Renders the content using the provided escort plugin, and then:
   * - if there is no content, aborts rendering, and makes sure the escort won't
   *   be rendered.
   * - if there is content, moves the contextual links from the escort content
   *   to the escort itself.
   */
  public static function preRender($build) {
    $entity = $build['#escort'];
    $plugin = $entity->getPlugin();
    $plugin_id = $plugin->getPluginId();
    $base_id = $plugin->getBaseId();
    $derivative_id = $plugin->getDerivativeId();
    $configuration = $plugin->getConfiguration();
    $is_admin = \Drupal::service('escort.path.matcher')->isAdmin();
    $is_temporary = $plugin->isTemporary();
    $cacheability = CacheableMetadata::createFromRenderArray($build);

    $content = $plugin->build();
    $content = static::mergeProperties($build, $content);

    // Remove the escort entity from the render array, to ensure that escorts
    // can be rendered without the escort config entity.
    unset($build['#escort']);

    if ($content !== NULL && !Element::isEmpty($content)) {
      $build['children'] = $content;
      $cacheability->merge(CacheableMetadata::createFromRenderArray($content))->applyTo($build);
      if ($is_admin && !$is_temporary) {
        // Set sortable class for use in escort.admin.js.
        $build['#attributes']['class'][] = 'escort-sortable';
        $build['#attributes']['data-escort-id'] = $entity->id();
        // Add entity operations.
        $build['ops']['links'] = [
          '#theme' => 'links',
          '#links' => $entity->buildOps(),
          '#attached' => ['library' => [escort_dialog_library()]],
          '#attributes' => ['class' => ['escort-ops']],
        ];
      }
    }
    else {
      // Abort rendering: render as the empty string and ensure this block is
      // render cached, so we can avoid the work of having to repeatedly
      // determine whether the block is empty. For instance, modifying or adding
      // entities could cause the block to no longer be empty.
      $build = array(
        '#cache' => $build['#cache'],
      );
      // If $content is not empty, then it contains cacheability metadata, and
      // we must merge it with the existing cacheability metadata. This allows
      // blocks to be empty, yet still bubble cacheability metadata, to indicate
      // why they are empty.
      if (!empty($content)) {
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromRenderArray($content))
          ->applyTo($build);
      }
    }
    return $build;
  }

  /**
   * Merge properties of two render arrays.
   *
   * @param array $build
   *   A render array.
   * @param array $content
   *   A render array.
   *
   * @return array
   *   A render array.
   */
  protected static function mergeProperties(&$build, $content) {
    // Place the $content returned by the escort plugin into a 'content' child
    // element, as a way to allow the plugin to have complete control of its
    // properties and rendering (for instance, its own #theme) without
    // conflicting with the properties used above, or alternate ones used by
    // alternate escort rendering approaches in contrib.
    foreach (array(
      '#attributes',
    ) as $property) {
      if (isset($content[$property])) {
        if (!isset($build[$property])) {
          $build[$property] = $content[$property];
        }
        elseif (is_array($content[$property])) {
          $build[$property] = NestedArray::mergeDeep($build[$property], $content[$property]);
        }
        else {
          $build[$property] = $content[$property];
        }
        unset($content[$property]);
      }
    }
    return $content;
  }

}

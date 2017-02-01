<?php

namespace Drupal\escort\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\escort\EscortPluginCollection;
use Drupal\escort\Plugin\Escort\EscortPluginInterface;;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\escort\EscortAjaxTrait;

/**
 * Defines the Escort entity.
 *
 * @ConfigEntityType(
 *   id = "escort",
 *   label = @Translation("Escort"),
 *   handlers = {
 *     "access" = "Drupal\escort\EscortAccessControlHandler",
 *     "list_builder" = "Drupal\escort\EscortListBuilder",
 *     "view_builder" = "Drupal\escort\EscortViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\escort\Form\EscortForm",
 *       "disable" = "Drupal\escort\Form\EscortDisableForm",
 *       "delete" = "Drupal\escort\Form\EscortDeleteForm"
 *     },
 *   },
 *   config_prefix = "escort",
 *   admin_permission = "administer escort",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/escort/{escort}/edit",
 *     "disable-form" = "/admin/config/escort/{escort}/disable",
 *     "delete-form" = "/admin/config/escort/{escort}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "region",
 *     "weight",
 *     "provider",
 *     "plugin",
 *     "settings",
 *   }
 * )
 */
class Escort extends ConfigEntityBase implements EscortInterface, EntityWithPluginCollectionInterface {
  use EscortAjaxTrait;

  /**
   * The Escort ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * The region this escort is placed in.
   *
   * @var string
   */
  protected $region = self::ESCORT_REGION_NONE;

  /**
   * The escort weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that holds the escort plugin for this entity.
   *
   * @var \Drupal\escort\EscortPluginCollection
   */
  protected $pluginCollection;

  /**
   * The plugin instance.
   *
   * @var \Drupal\escort\Plugin\Escort\EscortPluginInterface
   */
  protected $pluginInstance;

  /**
   * Flag that indicates if escort is dynamic/temporary.
   *
   * @var bool
   */
  protected $enforceIsTemporary = FALSE;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this->getRegion() == EscortInterface::ESCORT_REGION_NONE) {
      $this->disable();
    }
    else {
      $this->enable();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $settings = $this->get('settings');
    if ($settings['label']) {
      return $settings['label'];
    }
    else {
      $definition = $this->getPlugin()->getPluginDefinition();
      return $definition['admin_label'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return isset($this->id) ? $this->id : ($this->isTemporary() ? $this->uuidGenerator()->generate() : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin(EscortPluginInterface $plugin) {
    $this->pluginInstance = $plugin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    if (!isset($this->pluginInstance)) {
      $this->pluginInstance = $this->getPluginCollection()->get($this->plugin);
    }
    return $this->pluginInstance;
  }

  /**
   * Encapsulates the creation of the escort's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The escort's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new EscortPluginCollection(\Drupal::service('plugin.manager.escort'), $this->plugin, $this->get('settings'), $this->id());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'settings' => $this->getPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegion($region = self::ESCORT_REGION_NONE) {
    $this->region = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isTemporary() {
    return !empty($this->enforceIsTemporary);
  }

  /**
   * {@inheritdoc}
   */
  public function enforceIsTemporary($value = TRUE) {
    $this->enforceIsTemporary = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOps() {
    $links = [];
    $links[] = [
      'title' => t('Edit'),
      'url' => $this->toUrl(),
    ];
    $links[] = [
      'title' => t('Disable'),
      'url' => $this->toUrl('disable-form'),
    ];
    $links[] = [
      'title' => t('Delete'),
      'url' => $this->toUrl('delete-form'),
    ];
    $links = array_map(function ($link) {
      // Dialog ajaxify.
      $this->ajaxLinkAttributes($link, NULL, 'attributes', FALSE);
      return $link;
    }, $links);
    return $links;
  }

  /**
   * Sorts active escorts by weight; sorts inactive escorts by name.
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    // Separate enabled from disabled.
    $status = (int) $b->status() - (int) $a->status();
    if ($status !== 0) {
      return $status;
    }
    // Sort by weight, unless disabled.
    if ($a->getRegion() != static::ESCORT_REGION_NONE) {
      $weight = $a->getWeight() - $b->getWeight();
      if ($weight) {
        return $weight;
      }
    }
    // Sort by label.
    return strcmp($a->label(), $b->label());
  }

}

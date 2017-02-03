<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Defines a plugin for display the current user.
 *
 * @Escort(
 *   id = "user",
 *   admin_label = @Translation("User"),
 *   category = @Translation("User"),
 * )
 */
class User extends Dropdown implements ContainerFactoryPluginInterface {

  /**
   * The menu used for the links.
   *
   * @var string
   */
  protected $menuName = 'account';

  /**
   * {@inheritdoc}
   */
  protected $usesTrigger = FALSE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\user\UserInterface definition.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $currentAccount;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

  /**
   * Creates a UserEscort instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->currentAccount = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuStorage = $this->entityTypeManager->getStorage('menu');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    // No fields to add.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    // No settings to save.
  }

  /**
   * {@inheritdoc}
   */
  protected function buildLink() {
    $image = [];
    if (user_picture_enabled() && $image = $this->currentAccount->user_picture->entity) {
      $image = $this->currentAccount->user_picture->entity->getFileUri();
    }
    else {
      $image = $this->getGravatar($this->currentAccount->getEmail());
    }
    return [
      '#tag' => 'a',
      '#markup' => $this->currentAccount->label(),
      '#image' => $image,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDropdown() {
    $build = [
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => ['class' => ['escort-list']],
    ];

    foreach ($this->menuItems() as $item) {
      $link = $item->link;
      $title = $link->getTitle();
      $attributes = [];
      // Icon support.
      if ($this->hasIconSupport()) {
        $title = micon($title)->setMatchString('menu.' . $this->menuName . '.' . $title);
      }
      // Set active class.
      if ($item->inActiveTrail) {
        $attributes['class'][] = 'is-active';
      }
      $build['#links'][] = [
        'title' => $title,
        'url' => $link->getUrlObject(),
        'attributes' => $attributes,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function menuItems() {
    $items = [];
    $menu_name = $this->menuName;
    $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);
    $parameters = new MenuTreeParameters();
    $parameters->setActiveTrail($active_trail);

    $level = 1;
    $depth = 1;
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $items = $this->menuTree->transform($tree, $manipulators);

    return $items;
  }

  /**
   * Get a Gravatar URL for a specified email address.
   *
   * @param string $email
   *   The email address.
   * @param string $s
   *   Size in pixels, defaults to 80px [ 1 - 2048 ].
   * @param string $d
   *   Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ].
   * @param string $r
   *   Maximum rating (inclusive) [ g | pg | r | x ].
   *
   * @return string
   *   String containing either just a URL or a complete image tag
   */
  protected function getGravatar($email, $s = 128, $d = 'mm', $r = 'g') {
    $url = 'https://www.gravatar.com/avatar/';
    $url .= md5(strtolower(trim($email)));
    $url .= "?s=$s&d=$d&r=$r";
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $cache_tags[] = 'config:system.menu.' . $this->menuName;
    return Cache::mergeTags($cache_tags, $this->currentAccount->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // ::build() uses MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters()
    // to generate menu tree parameters, and those take the active menu trail
    // into account. Therefore, we must vary the rendered menu by the active
    // trail of the rendered menu.
    // Additional cache contexts, e.g. those that determine link text or
    // accessibility of a menu, will be bubbled automatically.
    $menu_name = $this->menuName;
    return Cache::mergeContexts(parent::getCacheContexts(), ['route.menu_active_trails:' . $menu_name]);
  }

}

<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\micon\MiconIconize;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * A trait that provides link utilities.
 */
trait EscortPluginLinkTrait {

  /**
   * The default icon to use when an icon is not set.
   *
   * @var string
   */
  protected $defaultLinkIcon = 'fa-circle-o';

  /**
   * Build a link given a title and uri or Drupal\Core\Url.
   *
   * @param string $title
   *   The title of the link.
   * @param mixed $uri
   *   The uri or Drupal\Core\Url of the link.
   *
   * @return array
   *   A render array.
   */
  protected function buildLink($title, $uri, $attributes = []) {
    $title = $this->titleAndUrlToIcon($title, $uri);
    list($title, $icon) = $this->titleToTitleIcon($title);
    $attributes += $this->getUriAsAttributes($uri);
    $options = $uri->getOptions();

    $attributes['title'] = $title;

    // Support icon set in url attributes.
    if (!empty($options['attributes']['data-icon'])) {
      $icon = $options['attributes']['data-icon'];
    }

    return [
      '#tag' => 'a',
      '#icon' => $icon,
      '#attributes' => $attributes,
      '#markup' => $title,
    ];
  }

  /**
   * Givent a title, return as Micon object.
   *
   * @param string $title
   *   The title of the link.
   */
  protected function titleAndUrlToIcon($title, $uri, $prefix = 'escort') {
    $title = $this->titleToIcon($title);
    if ($url = $this->getUrl($uri)) {
      $options = $url->getOptions();
      if (!empty($options['attributes']['data-icon'])) {
        $title->setIcon($options['attributes']['data-icon']);
      }
    }
    return $title;
  }

  /**
   * Givent a title, return as Micon object.
   *
   * @param string $title
   *   The title of the link.
   */
  protected function titleToIcon($title, $prefix = 'escort') {
    if (!$title instanceof MiconIconize) {
      $title = MiconIconize::iconize($title);
    }
    $title->addMatchPrefix($prefix);
    if (!$title->getIcon()) {
      $title->setIcon($this->getDefaultLinkIcon());
    }
    return $title;
  }

  /**
   * Given a title, return an array containing title and icon.
   *
   * @param string $title
   *   The title of the link.
   *
   * @return array
   *   An array containing a title and icon key.
   */
  protected function titleToTitleIcon($title) {
    $title = $title;
    $icon = '';

    // Check if title has already been MiconIfied.
    if (!$title instanceof MiconIconize) {
      $title = $this->titleToIcon($title);
    }
    if ($icon = $title->getIcon()) {
      $icon = $icon->getSelector();
    }
    else {
      $icon = $this->getDefaultLinkIcon();
    }
    $title = $title->getTitle();

    return [$title, $icon];
  }

  /**
   * Convert a uri or Drupal\Core\Url into Drupal\Core\Url.
   *
   * @var mixed $uri
   *  A uri or Drupal\Core\Url.
   */
  protected function getUrl($uri) {
    if ($uri instanceof Url) {
      $url = $uri;
    }
    else {
      $url = Url::fromUri($uri);
    }
    return $url;
  }

  /**
   * Convert a uri or Drupal\Core\Url into attributes.
   *
   * @var mixed $uri
   *  A uri or Drupal\Core\Url.
   */
  protected function getUriAsAttributes($uri, $attributes = []) {
    if ($url = $this->getUrl($uri)) {
      // External URLs can not have cacheable metadata.
      if ($url->isExternal()) {
        $href = $url->toString(FALSE);
      }
      elseif ($url->isRouted() && $url->getRouteName() === '<nolink>') {
        $href = '';
      }
      else {
        $generated_url = $url->toString(TRUE);
        // The result of the URL generator is a plain-text URL to use as the
        // href attribute, and it is escaped by \Drupal\Core\Template\Attribute.
        $href = $generated_url->getGeneratedUrl();

        if ($url->isRouted()) {
          // Set data element for active link setting.
          // @todo Drupal's active-link.js seems to not work for this. Why?
          $system_path = $url->getInternalPath();
          // Special case for the front page.
          $attributes['data-escort-path'] = $system_path == '' ? '<front>' : $system_path;
        }
      }
      $attributes['href'] = $href;
    }
    return $attributes;
  }

  /**
   * Get the default icon.
   */
  protected function getDefaultLinkIcon() {
    return $this->defaultLinkIcon;
  }

  /**
   * Check if user has access to page.
   *
   * @param mixed $uri
   *   The uri or \Drupal\Core\Url object.
   *
   * @return Drupal\Core\Access\AccessResult
   *   The access result.
   */
  protected function uriAccess($uri) {
    if (!empty($uri)) {
      if ($uri instanceof Url) {
        $url = $uri;
      }
      else {
        $url = Url::fromUri($uri);
      }
      return $url->access() ? AccessResult::allowed() : AccessResult::forbidden();
    }
    return AccessResult::forbidden();
  }

  /**
   * Render a menu for display within escort.
   *
   * @param string $menu_name
   *   The menu name to render.
   * @param string $level
   *   The menu level to start rendering from.
   * @param string $depth
   *   The menu deptch to render.
   */
  protected function buildMenuTree($menu_name, $level = 1, $depth = 1) {
    $parameters = new MenuTreeParameters();

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $level;
    $depth = $depth;
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($tree);
    if ($build['#items']) {
      $build['#items'] = $this->buildMenuTreeItems($build['#items']);
    }
    $build['#theme'] = 'escort_menu__' . $menu_name;
    return $build;
  }

  /**
   * Prepare tab for rendering.
   *
   * @param array $items
   *   The renderable menu items.
   *
   * @return array
   *   An array of altered menu items.
   */
  protected function buildMenuTreeItems($items) {
    foreach ($items as $id => &$item) {
      $icon = $this->titleToIcon($item['title']);
      $item['icon'] = $icon->setIconOnly();
      $item['title'] = $icon->getTitle();
      $url = $item['url'];
      // $bla = $this->getUriAsAttributes($url);
      $attributes['class'][] = 'escort-item';
      $item['wrapper_attributes'] = new Attribute();
      $item['link_attributes'] = new Attribute($this->getUriAsAttributes($url, $attributes));
      if ($item['below']) {
        $item['below'] = $this->buildMenuTreeItems($item['below']);
      }
    }
    return $items;
  }

}

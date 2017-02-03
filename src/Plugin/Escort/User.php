<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
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
   * {@inheritdoc}
   */
  protected $usesTrigger = FALSE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\EntityTypeManagerInterface
   */
  protected $entityManager;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->currentAccount = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
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
      $container->get('current_user')
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
    return [
      '#markup' => 'hi',
    ];
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
    return Cache::mergeTags($cache_tags, $this->currentAccount->getCacheTags());
  }

}

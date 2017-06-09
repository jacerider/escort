<?php

namespace Drupal\escort\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\escort\EscortRegionManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\image\Entity\ImageStyle;

/**
 * Class EscortConfigForm.
 *
 * @package Drupal\escort\Form
 */
class EscortConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\escort\EscortRegionManagerInterface definition.
   *
   * @var \Drupal\escort\EscortRegionManagerInterface
   */
  protected $escortRegionManager;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, EscortRegionManagerInterface $escort_region_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->escortRegionManager = $escort_region_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('escort.region_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'escort.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'escort_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('escort.config');

    $form['messages']['#markup'] = '<div id="escort-messages"></div>';

    $form['enabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Regions'),
      '#options' => $this->escortRegionManager->getGroups(),
      '#default_value' => $config->get('enabled'),
    ];

    $form['regions'] = [
      '#tree' => TRUE,
    ];
    $region_settings = $config->get('regions');
    foreach ($this->escortRegionManager->getRaw(TRUE) as $group_id => $group) {
      $form['regions'][$group_id] = [
        '#type' => 'fieldset',
        '#title' => $this->t('%region settings', ['%region' => $group['label']]),
      ];
      $form['regions'][$group_id]['icon_only'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Icon only'),
        '#default_value' => !empty($region_settings[$group_id]['icon_only']),
      ];
    }

    $form['close_icon'] = [
      '#type' => 'micon',
      '#title' => $this->t('Close Icon'),
      '#description' => $this->t('The icon to use when closing aside elements.'),
      '#required' => TRUE,
      '#default_value' => $config->get('close_icon'),
    ];

    // User entity picture support.
    $has_picture = user_picture_enabled();
    $form['user_picture'] = [
      '#type' => 'details',
      '#title' => $this->t('User Profile Picture'),
      '#open' => !$has_picture,
    ];
    if ($has_picture) {
      $form['user_picture']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove user picture field'),
        '#submit' => [[$this, 'submitUserPictureRemove']],
      ];
    }
    else {
      $form['user_picture']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create user picture field'),
        '#submit' => [[$this, 'submitUserPictureAdd']],
      ];
    }

    // Image style used within plugins.
    $style = ImageStyle::load('escort');
    $form['image_style'] = [
      '#type' => 'details',
      '#title' => $this->t('Image Style'),
      '#open' => !$style,
      '#tree' => TRUE,
    ];
    if ($style) {
      $form['image_style']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove escort image style'),
        '#submit' => [[$this, 'submitImageStyleRemove']],
      ];
    }
    else {
      $form['image_style']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add escort image style'),
        '#submit' => [[$this, 'submitImageStyleAdd']],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Clean up empty settings before saving.
    $regions = $form_state->getValue('regions');
    foreach ($regions as &$region) {
      $region = array_filter($region);
    }

    $this->config('escort.config')
      ->set('enabled', array_filter($form_state->getValue('enabled')))
      ->set('regions', array_filter($regions))
      ->set('close_icon', $form_state->getValue('close_icon'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function submitUserPictureAdd(array &$form, FormStateInterface $form_state) {
    $bundles = ['user'];

    $fields['user_picture'] = [
      'type' => 'image',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'User picture',
      'description' => 'Your virtual face or picture.',
      'required' => FALSE,
      'widget' => [
        'type' => 'image_image',
        'settings' => [
          'progress_indicator' => 'throbber',
          'preview_image_style' => 'thumbnail',
        ],
      ],
      'formatter' => [
        'default' => [
          'type' => 'image',
          'label' => 'hidden',
          'settings' => [
            'image_style' => 'thumbnail',
            'image_link' => 'content',
          ],
        ],
      ],
      'settings' => [
        'file_extensions' => 'png gif jpg jpeg',
        'file_directory' => 'pictures/[date:custom:Y]-[date:custom:m]',
        'max_filesize' => '30 KB',
        'max_resolution' => '128x128',
        'alt_field' => FALSE,
        'title_field' => FALSE,
        'alt_field_required' => FALSE,
        'title_field_required' => FALSE,
      ],
    ];

    foreach ($fields as $field_name => $config) {
      $field_storage = FieldStorageConfig::loadByName($config['entity_type'], $field_name);
      if (empty($field_storage)) {
        FieldStorageConfig::create([
          'field_name' => $field_name,
          'entity_type' => $config['entity_type'],
          'type' => $config['type'],
        ])->save();
      }
    }

    foreach ($bundles as $bundle) {
      foreach ($fields as $field_name => $config) {
        $config_array = [
          'field_name' => $field_name,
          'entity_type' => $config['entity_type'],
          'bundle' => $bundle,
          'label' => $config['label'],
          'required' => $config['required'],
        ];
      }

      if (isset($config['settings'])) {
        $config_array['settings'] = $config['settings'];
      }

      $field = FieldConfig::loadByName($config['entity_type'], $bundle, $field_name);
      if (empty($field) && $bundle !== "" && !empty($bundle)) {
        FieldConfig::create($config_array)->save();
      }

      if ($bundle !== "" && !empty($bundle)) {
        if (!empty($field)) {
          $field->setLabel($config['label'])->save();
          $field->setRequired($config['required'])->save();
        }
        if ($config['widget']) {
          entity_get_form_display($config['entity_type'], $bundle, 'default')
            ->setComponent($field_name, $config['widget'])
            ->save();
        }
        if ($config['formatter']) {
          foreach ($config['formatter'] as $view => $formatter) {
            $view_modes = \Drupal::entityManager()->getViewModes($config['entity_type']);
            if (isset($view_modes[$view]) || $view == 'default') {
              entity_get_display($config['entity_type'], $bundle, $view)
                ->setComponent($field_name, !is_array($formatter) ? $config['formatter']['default'] : $formatter)
                ->save();
            }
          }
        }
      }
    }
    drupal_set_message($this->t('A user picture field has been created within the user entity type.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitUserPictureRemove(array &$form, FormStateInterface $form_state) {
    $bundles = ['user'];

    $fields['user_picture'] = [
      'entity_type' => 'user',
    ];

    foreach ($bundles as $bundle) {
      foreach ($fields as $field_name => $config) {
        $field = FieldConfig::loadByName($config['entity_type'], $bundle, $field_name);
        if (!empty($field)) {
          $field->delete();
        }
      }
    }

    foreach ($fields as $field_name => $config) {
      $field_storage = FieldStorageConfig::loadByName($config['entity_type'], $field_name);
      if (!empty($field_storage)) {
        $field_storage->delete();
      }
    }
    drupal_set_message($this->t('The user picture field has been removed from the user entity type.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitImageStyleAdd(array &$form, FormStateInterface $form_state) {
    $style = ImageStyle::load('escort');
    if (!$style) {
      $style = ImageStyle::create(['name' => 'escort', 'label' => 'Escort']);
      $effect = [
        'id' => 'image_scale_and_crop',
        'data' => [
          'width' => 128,
          'height' => 128,
        ],
      ];
      if ($this->moduleHandler->moduleExists('focal_point')) {
        $effect['id'] = 'focal_point_scale_and_crop';
      }
      $style->addImageEffect($effect);
      $style->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitImageStyleRemove(array &$form, FormStateInterface $form_state) {
    $style = ImageStyle::load('escort');
    if ($style) {
      $style->delete();
    }
  }

}

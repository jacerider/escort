<?php

namespace Drupal\escort\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\escort\Plugin\Escort\EscortPluginInterface;
use Drupal\escort\Entity\EscortInterface;
use Drupal\escort\EscortManagerInterface;
use Drupal\escort\EscortRegionManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EscortForm.
 *
 * @package Drupal\escort\Form
 */
class EscortForm extends EntityForm {

  /**
   * The escort entity.
   *
   * @var \Drupal\escort\Entity\EscortInterface
   */
  protected $entity;

  /**
   * The escort storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The escort plugin manager.
   *
   * @var \Drupal\escort\EscortManagerInterface
   */
  protected $escortItemManager;

  /**
   * The region manager.
   *
   * @var \Drupal\escort\EscortRegionManagerInterface
   */
  protected $escortRegionManager;

  /**
   * Constructs a BlockForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\escort\EscortManagerInterface $escort_manager
   *   The escort plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EscortManagerInterface $escort_manager, EscortRegionManagerInterface $escort_region_manager) {
    $this->storage = $entity_type_manager->getStorage('escort');
    $this->escortItemManager = $escort_manager;
    $this->escortRegionManager = $escort_region_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.escort'),
      $container->get('escort.region_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $form['#tree'] = TRUE;
    $form['settings'] = [];

    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($entity->getPlugin())->buildConfigurationForm($form['settings'], $subform_state);

    // If creating a new escort, calculate a safe default machine name.
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this escort. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$entity->isNew() ? $entity->id() : $this->getUniqueMachineName($entity),
      '#machine_name' => array(
        'exists' => '\Drupal\escort\Entity\Escort::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => array('settings', 'label'),
      ),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    );

    // Hidden weight setting.
    $weight = $entity->isNew() ? $this->getRequest()->query->get('weight', 0) : $entity->getWeight();
    $form['weight'] = array(
      '#type' => 'hidden',
      '#default_value' => $weight,
    );

    // Region settings.
    $entity_region = $entity->getRegion();
    $region = $entity->isNew() ? $this->getRequest()->query->get('region', $entity_region) : $entity_region;
    $form['region'] = array(
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t('Select the region where this escort should be displayed.'),
      '#default_value' => $region,
      '#empty_value' => EscortInterface::ESCORT_REGION_NONE,
      '#options' => $this->escortRegionManager->getRegions(),
      '#prefix' => '<div id="edit-escort-item-region-wrapper">',
      '#suffix' => '</div>',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $entity = $this->entity;

    // The Escort Entity form puts all escort plugin form elements in the
    // settings form element, so just pass that to the escort for
    // submission.
    $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    // Call the plugin submit handler.
    $escort = $entity->getPlugin();
    $this->getPluginForm($escort)->submitConfigurationForm($form, $sub_form_state);

    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Escort.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Escort.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('escort.escort_list');
  }

  /**
   * Generates a unique machine name for a escort.
   *
   * @param \Drupal\escort\EscortInterface $escort
   *   The escort entity.
   *
   * @return string
   *   Returns the unique name.
   */
  public function getUniqueMachineName(EscortInterface $escort) {
    $suggestion = $escort->getPlugin()->getMachineNameSuggestion();

    // Get all the escorts which start with the suggested machine name.
    $query = $this->storage->getQuery();
    $query->condition('id', $suggestion, 'CONTAINS');
    $escort_ids = $query->execute();

    $escort_ids = array_map(function ($escort_id) {
      $parts = explode('.', $escort_id);
      return end($parts);
    }, $escort_ids);

    // Iterate through potential IDs until we get a new one. E.g.
    // 'plugin', 'plugin_2', 'plugin_3', etc.
    $count = 1;
    $machine_default = $suggestion;
    while (in_array($machine_default, $escort_ids)) {
      $machine_default = $suggestion . '_' . ++$count;
    }
    return $machine_default;
  }

  /**
   * Retrieves the plugin form for a given escort and operation.
   *
   * @param \Drupal\escort\Plugin\Escort\EscortPluginInterface $escort
   *   The escort plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the escort.
   */
  protected function getPluginForm(EscortPluginInterface $escort) {
    if ($escort instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($escort, 'configure');
    }
    return $escort;
  }

}

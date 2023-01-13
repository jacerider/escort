<?php

namespace Drupal\escort\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\escort\Plugin\Escort\EscortPluginInterface;
use Drupal\escort\Entity\EscortInterface;
use Drupal\escort\EscortManagerInterface;
use Drupal\escort\EscortRegionManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\escort\EscortAjaxTrait;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class EscortForm.
 *
 * @package Drupal\escort\Form
 */
class EscortForm extends EntityForm {
  use EscortAjaxTrait;

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
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

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
   * The language manager.
   * 
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language;

  /**
   * Constructs a BlockForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for building the visibility UI.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\escort\EscortManagerInterface $escort_manager
   *   The escort plugin manager.
   * @param \Drupal\escort\EscortRegionManagerInterface $escort_region_manager
   *   The escort region manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ExecutableManagerInterface $manager, ContextRepositoryInterface $context_repository, EscortManagerInterface $escort_manager, EscortRegionManagerInterface $escort_region_manager, LanguageManagerInterface $language) {
    $this->storage = $entity_type_manager->getStorage('escort');
    $this->manager = $manager;
    $this->contextRepository = $context_repository;
    $this->escortItemManager = $escort_manager;
    $this->escortRegionManager = $escort_region_manager;
    $this->language = $language;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('plugin.manager.escort'),
      $container->get('escort.region_manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    // Add region to entity.
    $entity_region = $entity->getRegion();
    $region = $entity->isNew() ? $this->getRequest()->query->get('region', $entity_region) : $entity_region;
    $entity->setRegion($region);

    // Store entity for use in subforms.
    $form_state->setTemporaryValue('entity', $entity);

    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($entity->getPlugin())->buildConfigurationForm($form['settings'], $subform_state);
    $form['visibility'] = $this->buildVisibilityInterface([], $form_state);

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
    $form['region'] = array(
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t('Select the region where this escort should be displayed.'),
      '#default_value' => $region,
      '#empty_value' => EscortInterface::ESCORT_REGION_NONE,
      '#options' => $this->escortRegionManager->getRegions(),
      '#prefix' => '<div id="edit-escort-item-region-wrapper">',
      '#suffix' => '</div>',
      '#access' => FALSE,
    );

    return $form;
  }

  /**
   * Helper function for building the visibility UI form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array with the visibility UI added in.
   */
  protected function buildVisibilityInterface(array $form, FormStateInterface $form_state) {
    $form['visibility_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Visibility'),
      '#parents' => ['visibility_tabs'],
      '#attached' => [
        'library' => [
          'block/drupal.block',
        ],
      ],
    ];
    // @todo Allow list of conditions to be configured in
    //   https://www.drupal.org/node/2284687.
    $visibility = $this->entity->getVisibility();
    foreach ($this->manager->getDefinitionsForContexts($form_state->getTemporaryValue('gathered_contexts')) as $condition_id => $definition) {
      // Don't display the current theme condition.
      if ($condition_id == 'current_theme') {
        continue;
      }
      // Don't display the language condition until we have multiple languages.
      if ($condition_id == 'language' && !$this->language->isMultilingual()) {
        continue;
      }
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->manager->createInstance($condition_id, isset($visibility[$condition_id]) ? $visibility[$condition_id] : []);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'visibility_tabs';
      $form[$condition_id] = $condition_form;
    }

    if (isset($form['node_type'])) {
      $form['node_type']['#title'] = $this->t('Content types');
      $form['node_type']['bundles']['#title'] = $this->t('Content types');
      $form['node_type']['negate']['#type'] = 'value';
      $form['node_type']['negate']['#title_display'] = 'invisible';
      $form['node_type']['negate']['#value'] = $form['node_type']['negate']['#default_value'];
    }
    if (isset($form['user_role'])) {
      $form['user_role']['#title'] = $this->t('Roles');
      unset($form['user_role']['roles']['#description']);
      $form['user_role']['negate']['#type'] = 'value';
      $form['user_role']['negate']['#value'] = $form['user_role']['negate']['#default_value'];
    }
    if (isset($form['request_path'])) {
      $form['request_path']['#title'] = $this->t('Pages');
      $form['request_path']['negate']['#type'] = 'radios';
      $form['request_path']['negate']['#default_value'] = (int) $form['request_path']['negate']['#default_value'];
      $form['request_path']['negate']['#title_display'] = 'invisible';
      $form['request_path']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
    }
    if (isset($form['language'])) {
      $form['language']['negate']['#type'] = 'value';
      $form['language']['negate']['#value'] = $form['language']['negate']['#default_value'];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if (!empty($_POST['_drupal_ajax'])) {
      $this->ajaxSubmitAttributes($actions['submit']);
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValue('weight', (int) $form_state->getValue('weight'));
    // The Escort Entity form puts all escort plugin form elements in the
    // settings form element, so just pass that to the escort for validation.
    $this->getPluginForm($this->entity->getPlugin())->validateConfigurationForm($form['settings'], SubformState::createForSubform($form['settings'], $form, $form_state));
    $this->validateVisibility($form, $form_state);
  }

  /**
   * Helper function to independently validate the visibility UI.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateVisibility(array $form, FormStateInterface $form_state) {
    // Validate visibility condition settings.
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue(['visibility', $condition_id, 'negate'], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->validateConfigurationForm($form['visibility'][$condition_id], SubformState::createForSubform($form['visibility'][$condition_id], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $entity = $this->entity;
    // The Escort Entity form puts all escort plugin form elements in the
    // settings form element, so just pass that to the escort for submission.
    $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    // Call the plugin submit handler.
    $escort = $entity->getPlugin();
    $this->getPluginForm($escort)->submitConfigurationForm($form, $sub_form_state);
    // If this escort is context-aware, set the context mapping.
    if ($escort instanceof ContextAwarePluginInterface && $escort->getContextDefinitions()) {
      $context_mapping = $sub_form_state->getValue('context_mapping', []);
      $escort->setContextMapping($context_mapping);
    }

    $this->submitVisibility($form, $form_state);

    // Save the settings of the plugin.
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
   * Helper function to independently submit the visibility UI.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function submitVisibility(array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->submitConfigurationForm($form['visibility'][$condition_id], SubformState::createForSubform($form['visibility'][$condition_id], $form, $form_state));

      // Setting conditions' context mappings is the plugins' responsibility.
      // This code exists for backwards compatibility, because
      // \Drupal\Core\Condition\ConditionPluginBase::submitConfigurationForm()
      // did not set its own mappings until Drupal 8.2.
      // @todo Remove the code that sets context mappings in Drupal 9.0.0.
      if ($condition instanceof ContextAwarePluginInterface) {
        $context_mapping = isset($values['context_mapping']) ? $values['context_mapping'] : [];
        $condition->setContextMapping($context_mapping);
      }

      $condition_configuration = $condition->getConfiguration();
      // Update the visibility conditions on the escort.
      $this->entity->getVisibilityConditions()->addInstanceId($condition_id, $condition_configuration);
    }
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

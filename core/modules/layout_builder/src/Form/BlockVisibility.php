<?php


namespace Drupal\layout_builder\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\layout_builder\Traits\TempstoreIdHelper;
use Drupal\outside_in\Ajax\OpenOffCanvasDialogCommand;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlockVisibility extends FormBase {
  use TempstoreIdHelper;

  /**
   * Tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs a new ConfigureBlock.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(SharedTempStoreFactory $tempstore, ContextRepositoryInterface $context_repository, EntityTypeManagerInterface $entity_type_manager, ConditionManager $condition_manager) {
    $this->tempStoreFactory = $tempstore;
    $this->contextRepository = $context_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore'),
      $container->get('context.repository'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_block_visibility';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $entity = NULL, $field_name = NULL, $delta = NULL, $region = NULL, $uuid = NULL) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entity_type)->loadRevision($entity);
    list($collection, $id) = $this->generateTempstoreId($entity, $field_name);
    $tempstore = $this->tempStoreFactory->get($collection)->get($id);
    if (!empty($tempstore['entity'])) {
      $entity = $tempstore['entity'];
    }
    /** @var \Drupal\layout_builder\LayoutSectionItemInterface $field */
    $field = $entity->$field_name->get($delta);
    $values = $field->section;
    $visibility = !empty($values[$region][$uuid]['visibility']) ? $values[$region][$uuid]['visibility'] : [];
    $conditions = [];
    foreach ($this->conditionManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts()) as $plugin_id => $definition) {
      $conditions[$plugin_id] = $definition['label'];
    }
    $form['condition'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a visibility condition'),
      '#options' => $conditions,
      '#empty_value' => '',
    ];
    $form['parameters'] = [
      '#type' => 'value',
      '#value' => [
        'entity_type' => $entity_type,
        'entity' => $entity->id(),
        'field_name' => $field_name,
        'delta' => $delta,
        'region' => $region,
        'uuid' => $uuid,
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Condition'),
      '#ajax' => [
        'callback' => [$this, 'submitFormDialog'],
        'event' => 'click',
      ]
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormDialog(array &$form, FormStateInterface $form_state) {
    $condition = $form_state->getValue('condition');
    $parameters = $form_state->getValue('parameters');
    $new_form = \Drupal::formBuilder()->getForm('\Drupal\layout_builder\Form\ConfigureVisibility', $parameters['entity_type'], $parameters['entity'], $parameters['field_name'], $parameters['delta'], $parameters['region'], $parameters['uuid'], $condition);
    $response = new AjaxResponse();
    $response->addCommand(new OpenOffCanvasDialogCommand('', $new_form));
    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}

}

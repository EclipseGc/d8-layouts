<?php


namespace Drupal\layout_builder\Form;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\Traits\TempstoreIdHelper;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeleteSection extends ConfirmFormBase {
  use TempstoreIdHelper;

  /**
   * Tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The plugin being configured.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $condition;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  protected $entityType;

  protected $entity;

  protected $fieldName;

  protected $delta;

  /**
   * Constructs a new ConfigureBlock.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(SharedTempStoreFactory $tempstore, EntityTypeManagerInterface $entity_type_manager) {
    $this->tempStoreFactory = $tempstore;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore'),
      $container->get('entity_type.manager')
    );
  }

  public function getQuestion() {
    return $this->t("Are you sure you want to delete this region section?");
  }

  public function getCancelUrl() {
    $parameters = [
      $this->entityType => $this->entity
    ];
    return new Url('entity.' . $this->entityType . '.layout', $parameters);
  }

  public function getFormId() {
    return 'layout_builder_delete_section';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $entity = NULL, $field_name = NULL, $delta = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form['parameters'] = [
      '#type' => 'value',
      '#value' => [
        'entity_type' => $entity_type,
        'entity' => $entity,
        'field_name' => $field_name,
        'delta' => $delta,
      ],
    ];
    $this->entityType = $entity_type;
    $this->entity = $entity;
    $this->fieldName = $field_name;
    $this->delta = $delta;
    $form['actions']['cancel'] = $this->buildCancelLink();
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $parameters = $form_state->getValue('parameters');
    $entity_type = $parameters['entity_type'];
    $entity = $parameters['entity'];
    $field_name = $parameters['field_name'];
    $delta = $parameters['delta'];
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entity_type)->loadRevision($entity);
    list($collection, $id) = $this->generateTempstoreId($entity, $field_name);
    $tempstore = $this->tempStoreFactory->get($collection)->get($id);
    if (!empty($tempstore['entity'])) {
      $entity = $tempstore['entity'];
    }
    $entity->$field_name->removeItem($delta);
    $tempstore['entity'] = $entity;
    $this->tempStoreFactory->get($collection)->set($id, $tempstore);
    $form_state->setRedirect("entity.{$entity->getEntityTypeId()}.layout", [$entity->getEntityTypeId() => $entity->id()]);
  }

  protected function buildCancelLink() {
    return [
      '#type' => 'button',
      '#value' => $this->getCancelText(),
      '#ajax' => [
        'callback' => '::ajaxCancel'
      ]
    ];
  }

  public function ajaxCancel(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand('#drupal-off-canvas'));
    return $response;
  }

}

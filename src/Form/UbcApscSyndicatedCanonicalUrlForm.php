<?php

namespace Drupal\ubc_apsc_syndicated_canonical_url\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UbcApscSyndicatedCanonicalUrlForm extends ConfigFormBase {

  protected $state;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

   /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an AutoParagraphForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, StateInterface $state, ModuleHandlerInterface $moduleHandler) {
    $this->entityTypeManager = $entityTypeManager;
    $this->state = $state;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),$container
      ->get('state'),$container->get('module_handler')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ubc_apsc_syndicated_canonical_url_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ubc_apsc_syndicated_canonical_url.settings',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
	
    // Default settings.
    $config = $this->config('ubc_apsc_syndicated_canonical_url.settings');
	
	// Syndicated content origin site label.
    $form['local_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Local content label'),
      '#default_value' => $config->get('ubc_apsc_syndicated_canonical_url.local_label'),
      '#description' => $this->t('Optional, the label for local content. If empty, no label will be applied.'),
    ];
	
	// Syndicated content origin site label.
    $form['origin_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Syndicated content origin label'),
      '#default_value' => $config->get('ubc_apsc_syndicated_canonical_url.origin_label'),
      '#description' => $this->t('The label of the source for the syndicated content, e.g. UBC Applied Science'),
      '#required' => TRUE,
    ];
	
	// Syndicated content origin domain.
    $form['origin_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Syndicated content domain origin'),
      '#default_value' => $config->get('ubc_apsc_syndicated_canonical_url.origin_domain'),
      '#description' => $this->t('The source domain for the syndicated content, e.g. apsc.ubc.ca (domain only, no protocol or trailing slashes)'),
      '#required' => TRUE,
    ];
	
    $existingContentTypeOptions = $this->getExistingContentTypes();

    // Content types where canonical URL should be replaced
    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Types'),
      '#description' => $this->t('Select the content types that have syndicated items'),
      '#options' => $existingContentTypeOptions,
      '#default_value' => $config->get('ubc_apsc_syndicated_canonical_url.content_types', []) ?: [],
    ];

    // Re-write meta data canonical link for origin site.
    $form['sitemap_exclusion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude syndicated content from the sitemap'),
      '#default_value' => $this->moduleHandler->moduleExists('simple_sitemap') ? $config->get('ubc_apsc_syndicated_canonical_url.sitemap_exclusion') : FALSE,
      '#description' => $this->t(($this->moduleHandler->moduleExists('simple_sitemap') ? '' : 'This functionality requires the simple_sitemap module.<br />') . 'Check this box to exclude the syndicated content from the sitemap.This is recommended to avoid canonical URL SEO issues.<br />Note: changing this value this will trigger a rebuild of the sitemap on the next cron run.'),
      '#disabled' => $this->moduleHandler->moduleExists('simple_sitemap') ? FALSE : TRUE,
      '#access' => $this->moduleHandler->moduleExists('simple_sitemap') ? TRUE : FALSE,
    ];

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    return parent::validateForm($form, $form_state);
  }
  
    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	  
    $config = $this->config('ubc_apsc_syndicated_canonical_url.settings');
	
    $config->set('ubc_apsc_syndicated_canonical_url.local_label', $form_state->getValue('local_label'));
    $config->set('ubc_apsc_syndicated_canonical_url.origin_label', $form_state->getValue('origin_label'));
    $config->set('ubc_apsc_syndicated_canonical_url.origin_domain', $form_state->getValue('origin_domain'));
    $config->set('ubc_apsc_syndicated_canonical_url.content_types', $form_state->getValue('content_types'));
    $config->set('ubc_apsc_syndicated_canonical_url.sitemap_exclusion', ($this->moduleHandler->moduleExists('simple_sitemap') ? $form_state->getValue('sitemap_exclusion') : FALSE));
	
    $config->save();

    if ($this->moduleHandler->moduleExists('simple_sitemap') && ( $config->get('ubc_apsc_syndicated_canonical_url.sitemap_exclusion') !== $form_state->getValue('sitemap_exclusion') )) {
      // Queue all elements for sitemap generation when this setting has changed.
      /** @var \Drupal\simple_sitemap\Manager\Generator $sitemap_generator */
      $sitemap_generator = \Drupal::service('simple_sitemap.generator');
      $sitemap_generator->rebuildQueue();
    }
	
    return parent::submitForm($form, $form_state);
  }
  
  /**
   * Returns a list of all the content types currently installed.
   *
   * @return array
   *   An array of content types.
   */
  public function getExistingContentTypes() {
	  
    $types = [];
	
    $contentTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
	
    foreach ($contentTypes as $contentType) {
      $types[$contentType->id()] = $contentType->label();
    }
	
    return $types;
  }
  
}

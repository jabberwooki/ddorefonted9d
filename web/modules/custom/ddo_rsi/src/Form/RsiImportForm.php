<?php

namespace Drupal\ddo_rsi\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\ddo_rsi\Service\RsiImporterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ressources SI settings form.
 */
final class RsiImportForm extends FormBase {

  /**
   * Number of items processed at each step.
   */
  const BATCH_PROCESSING_ITEMS = 25;

  /**
   * Constructor.
   */
  public function __construct(
    protected $messenger,
    private readonly StateInterface $state,
    private readonly DateFormatterInterface $dateFormatter,
    private readonly RsiImporterService $rsiImporter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('ddo_rsi.importer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rsi_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($form_state->get('step'))) {
      $form_state->set('step', 1);
    }

    $step = $form_state->get('step');

    return call_user_func([$this, 'buildForm' . $step], $form, $form_state);
  }

  /**
   * First step form.
   */
  public function buildForm1(array $form, FormStateInterface $form_state) {
    $last_import = $this->state->get('ddo_rsi.last_import', 0);

    $form['info'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>Dernière importation le %date. Les spectacles importés ne seront pas publiés automatiquement.</p>', [
        '%date' => $this->dateFormatter->format($last_import, 'short'),
      ]),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Voir la liste des spectacles à importer'),
      '#submit' => ['::submitForm1'],
    ];

    return $form;
  }

  /**
   * First step submit.
   */
  public function submitForm1(array &$form, FormStateInterface $form_state) {
    $shows = $this->rsiImporter->getShows();

    if (empty($shows)) {
      $this->messenger->addStatus($this->t('Pas de nouveau spectacle à importer.'));
      return;
    }

    $form_state->set('shows', $shows);
    $form_state->set('step', 2);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Confirmation form.
   */
  public function buildForm2(array $form, FormStateInterface $form_state) {
    $form['explanation'] = [
      '#type' => 'item',
      '#markup' => $this->t('Voici la liste des spectacles qui seront importés :'),
    ];

    $form['shows'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Identifiant RSI'),
        $this->t('Title'),
        $this->t('Dernière modification'),
        $this->t('Festival'),
        $this->t('Première représentation'),
      ],
      '#rows' => array_map(fn($show) => [
        $show['IDENT'],
        $show['NOM'],
        $this->dateFormatter->format(strtotime($show['TS_MODIF']), 'short'),
        $show['CHAMP_LIBRE2'],
        $show['DATES'][0] ?? 'N/A',
      ], $form_state->get('shows')),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#limit_validation_errors' => [],
      '#submit' => ['::submitFormBack'],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer'),
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * Back button form submit.
   */
  public function submitFormBack(array &$form, FormStateInterface $form_state) {
    // Nothing to do here actually.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    batch_set($this->getBatchDefinition($form_state->get('shows')));
  }

  /**
   * Batch definition.
   *
   * @param array $shows
   *   An array of show data retrieved from RSI, keyed by RSI show ID.
   *
   * @return array
   *   Batch definition
   */
  private function getBatchDefinition(array $shows) : array {
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Importation des spectacles'))
      ->setFinishCallback([__CLASS__, 'batchFinished']);
    $batch_builder->addOperation([__CLASS__, 'batchInit'], []);

    foreach (array_chunk($shows, self::BATCH_PROCESSING_ITEMS) as $index => $chunk) {
      $batch_builder->addOperation([__CLASS__, 'batchProcess'], [
        $chunk,
        count($shows),
      ]);
    }

    return $batch_builder->toArray();
  }

  /**
   * Init values at the beginning of the batch.
   */
  public static function batchInit(&$context) : void {
    $context['results']['nb_imported'] = 0;
  }

  /**
   * Batch processor.
   *
   * @param array $shows
   *   Show data retrieved from RSI.
   * @param int $total
   *   Total number of shows to import.
   * @param array $context
   *   Batch context.
   */
  public static function batchProcess(array $shows, int $total, array &$context) {
    $ids = \Drupal::service('ddo_rsi.importer')->saveShows($shows);

    $context['results']['nb_imported'] += count($ids);
    $context['message'] = t('Traitement de @processed spectacles sur @total.', [
      '@processed' => count($shows),
      '@total' => $total,
    ]);
  }

  /**
   * Complete a batch process.
   *
   * This callback may be specified in a batch to perform clean-up operations,
   * or to analyze the results of the batch operations.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function batchFinished($success, array $results, array $operations) {
    \Drupal::state()->set('ddo_rsi.last_import', \Drupal::time()->getCurrentTime());
    \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural($results['nb_imported'], '@count spectacle importé. Voir la liste sur <a href=":url">cette page</a>.', '@count spectacles importés. Voir la liste sur <a href=":url">cette page</a>.', [
      ':url' => Url::fromRoute('view.content.page_1', [], [
        'query' => [
          'type' => 'show',
          'status' => '2',
          'langcode' => 'fr',
          'order' => 'changed',
          'sort' => 'desc',
        ],
      ])->toString(),
    ]));
  }

}

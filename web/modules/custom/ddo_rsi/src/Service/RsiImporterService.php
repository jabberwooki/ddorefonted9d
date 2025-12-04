<?php

namespace Drupal\ddo_rsi\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Import shows from Ressources SI.
 */
final class RsiImporterService {

  use StringTranslationTrait;

  /**
   * Ressources SI web service settings.
   */
  private readonly ImmutableConfig $rsiSettings;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    private readonly ClientInterface $httpClient,
    private readonly Connection $database,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly MessengerInterface $messenger,
    private readonly AccountProxyInterface $currentUser,
    private readonly TimeInterface $time,
  ) {
    $this->rsiSettings = $config_factory->get('ddo_rsi.settings');
  }

  /**
   * Retrieve shows added to RSI database after a given date.
   *
   * @return array
   *   Array of data returned by Ressources SI.
   */
  public function getShows() : array {
    $xml = $this->getXml();

    if (empty($xml)) {
      return [];
    }

    $reader = $this->getXmlReader($xml);

    if (empty($reader)) {
      return [];
    }

    $shows = $this->parseShows($reader);

    return $this->discardExistingShows($shows);
  }

  /**
   * Get XML data from RSI web services.
   *
   * @return string|null
   *   XML string, or NULL in case of error.
   */
  private function getXml() : ?string {
    $base_url = $this->rsiSettings->get('base_url') . 'ws_cat_spectacle';

    try {
      $response = $this->httpClient->get($base_url, [
        'query' => [
          'x_rsi_ws_login' => $this->rsiSettings->get('login'),
          'x_rsi_ws_mdp' => $this->rsiSettings->get('password'),
          'x_rsi_client' => $this->rsiSettings->get('client'),
        ],
      ]);
    }
    catch (RequestException $e) {
      Error::logException($this->logger, $e);
      $this->messenger->addError($this->t('Récupération des données impossible. Message : @message', [
        '@message' => $e->getMessage(),
      ]));
      return NULL;
    }

    return (string) $response->getBody();
  }

  /**
   * Construct an XML reader object to read XML retrieved from RSI.
   *
   * @param string $xml
   *   XML string retrieved from RSI.
   *
   * @return null|\XMLReader
   *   XML reader, or NULL in case of error.
   */
  private function getXmlReader(string $xml) : ?\XMLReader {
    $reader = new \XMLReader();

    if (!$reader->XML($xml)) {
      $this->logger->error('Could not read RSI XML data.');
      $this->messenger->addError($this->t('Lecture données au format XML impossible'));
      return NULL;
    }

    return $reader;
  }

  /**
   * Parse XML to retrieve show data.
   *
   * @param \XMLReader $reader
   *   Loaded XML object.
   *
   * @return array
   *   Array of show data, keyed by RSI ID.
   */
  private function parseShows(\XMLReader $reader) : array {
    $rooms = $grilles = $shows = [];

    while ($reader->read()) {
      // Retrieve parent room IDs.
      if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'SALLE') {
        $salle_xml = simplexml_load_string($reader->readOuterXml());
        if ($salle_xml === FALSE) {
          continue;
        }

        $rooms += $this->parseRoomElement($salle_xml);
      }

      // Get min and max prices from each price list.
      if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'GRILLE') {
        $grille_xml = simplexml_load_string($reader->readOuterXml());
        if ($grille_xml === FALSE) {
          continue;
        }

        $grilles += $this->parseGrilleElement($grille_xml);
      }

      // Read show data.
      if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'SPECTACLE') {
        $show_xml = simplexml_load_string($reader->readOuterXml());
        if ($show_xml === FALSE) {
          continue;
        }

        $shows += $this->parseShowElement($show_xml, $rooms, $grilles);
      }
    }

    return $shows;
  }

  /**
   * Parse the SALLE XML element.
   *
   * @param \SimpleXMLElement $salle_xml
   *   XML object of the SALLE element.
   *
   * @return array
   *   An array containing one SALLE_MERE_ID, keyed by its corresponding
   *   SALLE_ID.
   */
  private function parseRoomElement(\SimpleXMLElement $salle_xml) : array {
    $salle_mere_id = (string) $salle_xml['SALLE_MERE_ID'];
    $salle_id = (string) $salle_xml['SALLE_ID'];

    return [$salle_id => $salle_mere_id];
  }

  /**
   * Parse the GRILLE XML element.
   *
   * @param \SimpleXMLElement $grille_xml
   *   XML object of the GRILLE element.
   *
   * @return array
   *   An array of min and max for each price list.
   */
  private function parseGrilleElement(\SimpleXMLElement $grille_xml) : array {
    $grilles = $prix_totaux = [];
    $libelle = (string) $grille_xml->libelle;
    $libelle_web = (string) $grille_xml->libelle_web;

    foreach ($grille_xml->xpath('CATEGORIE_PUBLIQUE/ZONE/*') as $zone_xml) {
      $frais = $this->decodeFloatValue((string) $zone_xml['FRAIS']);
      $prix = $this->decodeFloatValue((string) $zone_xml);
      $prix_totaux[] = $frais + $prix;
    }
    $grilles[$libelle] = $this->minmax($prix_totaux);

    if (!empty($libelle_web)) {
      $grilles[$libelle_web] = $grilles[$libelle];
    }

    return $grilles;
  }

  /**
   * Convert textual value using comma as decimal separator to float.
   *
   * @param string $value
   *   Value using a comma as a decimal separator, e.g. "5,5".
   *
   * @return float
   *   Value converted to float.
   */
  private function decodeFloatValue(string $value) : float {
    return (float) str_replace(',', '.', $value);
  }

  /**
   * Parse the SPECTACLE XML element.
   *
   * @param \SimpleXMLElement $show_xml
   *   XML object of the SPECTACLE element.
   * @param array $rooms
   *   Parent room IDs, keyed by room ID.
   * @param array $grilles
   *   An array of min and max for each price list.
   *
   * @return array
   *   An array containing one array of show values, keyed by RSI show ID.
   */
  private function parseShowElement(\SimpleXMLElement $show_xml, array $rooms, array $grilles) {
    $show = $this->initShow($show_xml);
    $mins = $maxs = [];
    // Iterate over representations of this show.
    foreach ($show_xml->xpath('REPRESENTATIONS/REPRESENTATION') as $rep_xml) {
      $show['DATES'][] = (string) $rep_xml['REP_DATE'];
      $show['HEURES'][] = (string) $rep_xml['REP_HEURE'];

      $grille_names = [
        (string) $rep_xml['REP_GRILLE'],
        (string) $rep_xml['REP_GRILLE_WEB'],
      ];
      foreach ($grille_names as $grille_name) {
        if (!isset($grilles[$grille_name])) {
          continue;
        }

        $mins[] = $grilles[$grille_name]['min'];
        $maxs[] = $grilles[$grille_name]['max'];
      }
    }

    $show['PRIX'] = [
      'min' => empty($mins) ? 0 : min($mins),
      'max' => empty($maxs) ? 0 : max($maxs),
    ];

    if (!empty($rep_xml)) {
      $show['DUREE'] = (string) $rep_xml['REP_DUREE'];
      $salle_id = (string) $rep_xml['REP_SALLE'];
      $show['SALLE'] = $rooms[$salle_id] ?? '';
    }

    return [$show['IDENT'] => $show];
  }

  /**
   * Initialize simple values of a show, given it XML version.
   *
   * @param \SimpleXMLElement $show_xml
   *   XML object of the SPECTACLE element.
   *
   * @return array
   *   Array of show values.
   */
  private function initShow(\SimpleXMLElement $show_xml) : array {
    return [
      'IDENT' => (string) $show_xml['IDENT'],
      'NOM' => (string) $show_xml['NOM'],
      'CHAMP_LIBRE2' => (string) $show_xml->CHAMP_LIBRE2,
      'TITRE_LIBRE2' => (string) $show_xml->TITRE_LIBRE2,
      'TYPE' => (string) $show_xml->TYPE,
      'CHAMP_LIBRE1' => (string) $show_xml->CHAMP_LIBRE1,
      'TS_MODIF' => (string) $show_xml->TS_MODIF,
      'DATES' => [],
      'HEURES' => [],
      'PRIX' => ['min' => 0, 'max' => 0],
      'DUREE' => '',
      'SALLE' => '',
    ];
  }

  /**
   * Compute both min and max in one pass.
   *
   * @param array $values
   *   Numerical values.
   *
   * @return array
   *   min and max, keyed by name.
   */
  private function minmax(array $values) : array {
    if (empty($values)) {
      return ['min' => 0, 'max' => 0];
    }

    return array_reduce($values, fn($minmax, $value) => [
      'min' => min($minmax['min'], $value),
      'max' => max($minmax['max'], $value),
    ],
    [
      'min' => PHP_INT_MAX,
      'max' => 0,
    ]);
  }

  /**
   * Remove shows that were already imported from the list.
   *
   * @param array $shows
   *   Subset of data returned by Ressources SI.
   *
   * @return array
   *   Only new shows left.
   */
  private function discardExistingShows(array $shows) : array {
    if (empty($shows)) {
      return $shows;
    }

    $existing = $this->database
      ->select('node__field_rsi_ident', 'i')
      ->fields('i', ['field_rsi_ident_value'])
      ->condition('field_rsi_ident_value', array_keys($shows), 'IN')
      ->execute()
      ->fetchCol();

    return array_diff_key($shows, array_combine($existing, $existing));
  }

  /**
   * Save a subset of shows.
   *
   * @param array $shows
   *   Subset of data returned by Ressources SI.
   *
   * @return array
   *   An array of node IDs saved into the database.
   */
  public function saveShows(array $shows) : array {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $festival_lookup = $this->getFestivalLookupTable();
    $show_type_lookup = $this->getShowTypeLookupTable();
    $place_lookup = $this->getPlaceLookupTable();
    $ids = [];

    foreach ($shows as $show) {
      $node = $node_storage->create([
        'type' => 'show',
        'uid' => $this->currentUser->id(),
        'status' => 0,
        'field_rsi_ident' => $show['IDENT'],
        'title' => $show['NOM'],
        'field_subtitle' => $show['TITRE_LIBRE2'],
        'field_festival' => $festival_lookup[$show['CHAMP_LIBRE2']] ?? NULL,
        'field_show_type' => $this->lookupShowType($show['TYPE'], $show_type_lookup),
        'field_entrance' => $show['CHAMP_LIBRE1'],
        'field_location' => $place_lookup[$show['SALLE']] ?? NULL,
        'field_dates' => $this->convertDates($show['DATES'], $show['HEURES']),
        'field_price' => $this->convertPrice($show['PRIX']),
        'field_duration' => $this->convertDuration($show['DUREE']),
        // URL billetterie manuelle (pas Mapado).
        'field_reservation' => '9',
        'field_ticketing_url' => [
          'uri' => $this->getTicketingUrl($show['IDENT']),
          'title' => 'Réserver',
        ],
      ]);

      $node->setNewRevision(TRUE);
      $node->setRevisionLogMessage($this->t('Importation automatique depuis Ressources SI'));
      $node->setRevisionCreationTime($this->time->getCurrentTime());
      $node->setRevisionUserId($this->currentUser->id());
      $node->save();

      $ids[] = $node->id();
    }

    return $ids;
  }

  /**
   * Return a mapping table between festival names and node IDs.
   *
   * Return a mapping table between human festival names and latest seasons node
   * IDs.
   *
   * @return array
   *   Latest seasons node IDs, keyed by human name.
   */
  private function getFestivalLookupTable() : array {
    $lookup = [];
    $ids = ddo_utils_get_latest_festival_seasons_ids();
    $names = ddo_utils_get_festival_names();

    foreach ($ids as $machine_name => $id) {
      if (empty($names[$machine_name])) {
        continue;
      }

      $lookup[$names[$machine_name]] = $id;
    }

    return $lookup;
  }

  /**
   * Return a mapping table beetween RSI types and term IDs.
   *
   * @return array
   *   Term IDs, keyed by RSI name.
   */
  private function getShowTypeLookupTable() : array {
    $lookup = [];

    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'show_types']);

    foreach ($terms as $term) {
      if (!$term->hasTranslation('fr')) {
        continue;
      }

      // Ensure we work on the French version of the term.
      $term = $term->getTranslation('fr');

      $id = $term->id();

      if ($term->label() === 'Autre') {
        $lookup['default'] = $id;
        continue;
      }

      foreach ($term->field_rsi_mapping->getValue() as $value) {
        $key = mb_strtoupper($value['value']);
        $lookup[$key] = $id;
      }
    }

    return $lookup;
  }

  /**
   * Return term ID corresponding to an RSI type.
   *
   * @param string $type
   *   RSI type ID.
   * @param array $lookup
   *   Lookup table, see getShowTypeLookupTable().
   *
   * @return null|string
   *   Term ID.
   */
  private function lookupShowType(string $type, array $lookup) : ?string {
    $key = mb_strtoupper($type);

    if (!empty($lookup[$key])) {
      return $lookup[$key];
    }

    if (!empty($lookup['default'])) {
      return $lookup['default'];
    }

    return NULL;
  }

  /**
   * Return a mapping table beetween RSI places and term IDs.
   *
   * @return array
   *   Term IDs, keyed by RSI place ID.
   */
  private function getPlaceLookupTable() : array {
    $lookup = [];

    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'show_locations']);

    foreach ($terms as $term) {
      if ($term->field_rsi_id->isEmpty()) {
        continue;
      }

      $lookup[$term->field_rsi_id->value] = $term->id();
    }

    return $lookup;
  }

  /**
   * Convert dates (and times) provided by RSI.
   *
   * @param array $dates
   *   Dates, formatted as dd/mm/yyyy.
   * @param array $times
   *   Times, formatted as hh:mm:ss.
   *
   * @return array
   *   An array of dates in ISO 8601 format.
   */
  private function convertDates(array $dates, array $times) : array {
    $converted_dates = [];
    $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    foreach ($dates as $index => $date) {
      [$day, $month, $year] = explode('/', $date);
      [$hour, $minute, $second] = explode(':', $times[$index] ?? '00:00:00');
      $date = DrupalDateTime::createFromArray([
        'year' => $year,
        'month' => $month,
        'day' => $day,
        'hour' => $hour,
        'minute' => $minute,
        'second' => $second,
      ]);

      $converted_dates[] = $date->setTimeZone($storage_timezone)->format($storage_format);
    }

    return $converted_dates;
  }

  /**
   * Convert times provided by RSI.
   *
   * @param string $duration
   *   Time provided by RSI, using the hh:mm:ss format.
   *
   * @return string
   *   Formatted time in hours or minutes.
   */
  private function convertDuration(string $duration) : string {
    if (empty($duration)) {
      return '';
    }

    [$hours, $minutes] = explode(':', $duration);

    if ($hours == '0' && $minutes == '0') {
      return '';
    }

    return $hours . 'h' . $minutes;
  }

  /**
   * Format prices provided by RSI.
   *
   * @param array $prices
   *   Min and max.
   *
   * @return string
   *   Formatted price
   */
  private function convertPrice(array $prices) : string {
    ['min' => $min, 'max' => $max] = $prices;

    if ($min == 0 && $max == 0) {
      return $this->t('Gratuit');
    }

    if ($min == $max) {
      return $this->t('@price €', ['@price' => $min]);
    }

    return $this->t('De @min € à @max €', [
      '@min' => $min,
      '@max' => $max,
    ]);
  }

  /**
   * Build ticketing URL.
   *
   * @param string $rsi_id
   *   Ressources SI show ID.
   *
   * @return string
   *   Ticketing URL.
   */
  private function getTicketingUrl(string $rsi_id) : string {
    $base_url = $this->rsiSettings->get('base_url') . 'spectacle';

    return Url::fromUri($base_url, [
      'query' => [
        'lng' => '1',
        'id_spectacle' => $rsi_id,
      ],
    ])->toString();
  }

}

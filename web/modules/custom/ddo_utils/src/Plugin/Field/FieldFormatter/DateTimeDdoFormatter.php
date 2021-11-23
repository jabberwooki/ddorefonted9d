<?php

namespace Drupal\ddo_utils\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeFormatterBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Plugin implementation of the 'Custom' formatter for 'datetime_ddo' fields.
 *
 * Si un spectacle ne possède qu'une seule date
 *   il faut l'afficher avec le format suivant : Mar 23 novembre 16h00
 * Sinon
 *   il faut afficher la première et la dernière date
 *   avec le format suivant : 25/11/2021 > 28/11/2021
 *
 * Ces deux formats sont les valeurs par défaut.
 * Il est possible de choisir un autre format pour chacun des deux cas
 * via l'interface des view modes.
 *
 * @FieldFormatter(
 *   id = "datetime_ddo",
 *   label = @Translation("Dates DDO"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeDdoFormatter extends DateTimeFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'single_date_format' => 'show_date',
        'range_date_format' => 'show_date_short',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $nb_dates = count($items);
    $formatted_dates = '';

    if ($nb_dates < 2 ) {
      $single_date = $items[0]->date;
      if (!empty($single_date)) {
        list($single_formatted_date, $range_formatted_date) = $this->formatDate($single_date);
        $formatted_dates = ucfirst($single_formatted_date);
      }
    }
    else {
      $first_date = $items[0]->date;
      if (!empty($first_date)) {
        list($single_formatted_first_date, $range_formatted_first_date) = $this->formatDate($first_date);
      }
      $last_date = $items[$nb_dates -1]->date;
      if (!empty($last_date)) {
        list($single_formatted_last_date, $range_formatted_last_date) = $this->formatDate($last_date);
      }
      $formatted_dates = $range_formatted_first_date . '>' . $range_formatted_last_date;
    }

    $elements[0] = [
      '#markup' => $formatted_dates,
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    $single_date_format = $this->getSetting('single_date_format');
    $range_date_format = $this->getSetting('range_date_format');
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return [
      $this->dateFormatter->format($date->getTimestamp(), $single_date_format, '', $timezone != '' ? $timezone : NULL),
      $this->dateFormatter->format($date->getTimestamp(), $range_date_format, '', $timezone != '' ? $timezone : NULL)
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $time = new DrupalDateTime();
    $format_types = $this->dateFormatStorage->loadMultiple();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $format = $this->dateFormatter->format($time->getTimestamp(), $type);
      $options[$type] = $type_info->label() . ' (' . $format . ')';
    }

    $form['single_date_format'] = [
      '#type' => 'select',
      '#title' => t('Format de date unique'),
      '#description' => t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $options,
      '#default_value' => $this->getSetting('single_date_format'),
    ];

    $form['range_date_format'] = [
      '#type' => 'select',
      '#title' => t('Format de dates multiples'),
      '#description' => t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $options,
      '#default_value' => $this->getSetting('range_date_format'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $date = new DrupalDateTime();
    list($single_date_format, $range_date_format) =  $this->formatDate($date);

    $summary[] = $this->t('Affiche les dates au format Domaine d\'O');
    $summary[] = $this->t('Format de date unique = ' . $single_date_format);
    $summary[] = $this->t('Format de dates multiples = ' . $range_date_format);

    return $summary;
  }

}


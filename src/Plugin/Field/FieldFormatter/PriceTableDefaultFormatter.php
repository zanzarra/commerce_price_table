<?php

namespace Drupal\commerce_price_table\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;

/**
 * Plugin implementation of the 'commerce_price_table' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_multiprice_default",
 *   label = @Translation("Price chart"),
 *   field_types = {
 *     "commerce_price_table"
 *   }
 * )
 */
class PriceTableDefaultFormatter extends PriceDefaultFormatter {

  const COMMERCE_PRICE_TABLE_HORIZONTAL = 0;
  const COMMERCE_PRICE_TABLE_VERTICAL = 1;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CurrencyFormatterInterface $currency_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $currency_formatter);

    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'table_orientation' => PriceTableDefaultFormatter::COMMERCE_PRICE_TABLE_HORIZONTAL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $table_orientation = $this->getSetting('table_orientation');
    $res =  [
      'price_label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Price label for the price table'),
        '#default_value' => $this->getSetting('price_label'),
      ],
      'quantity_label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Quantity label for the price table'),
        '#default_value' => $this->getSetting('quantity_label'),
      ],
      'table_orientation' => [
        '#type' => 'radios',
        '#options' => [
          PriceTableDefaultFormatter::COMMERCE_PRICE_TABLE_HORIZONTAL => $this->t('Horizontal'),
          PriceTableDefaultFormatter::COMMERCE_PRICE_TABLE_VERTICAL => $this->t('Vertical'),
        ],
        '#title' => $this->t('Orientation of the price table'),
        '#default_value' => $table_orientation,
      ],

    ] + parent::settingsForm($form, $form_state);

    return $res;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $table_orientation = $this->getSetting('table_orientation');
    $quantity_label = $this->getSetting('quantity_label');
    $price_label = $this->getSetting('price_label');
    $orientation = isset($table_orientation) && $table_orientation == PriceTableDefaultFormatter::COMMERCE_PRICE_TABLE_VERTICAL ? $this->t('Vertical') : $this->t('Horizontal');
    $summary = [
        $this->t('Quantity label: @quantity_label', array('@quantity_label' => isset($quantity_label) ? $quantity_label : $this->t('Quantity'))),
        $this->t('Price label: @price_label', array('@price_label' => isset($price_label) ? $price_label : $this->t('Price'))),
        $this->t('Orientation: @orientation', array('@orientation' => $orientation)),
    ]  + parent::settingsSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $options = $this->getFormattingOptions();
    $table_orientation = $this->getSetting('table_orientation');
    $elements = [];
    $header = [];
    foreach ($items as $delta => $item) {
      if (isset($item->min_qty) && $item->max_qty && $item->amount) {
        $header[] = $this->getQuantityHeaders($item);
        $row[] = array('data' => $this->currencyFormatter->format($item->amount, $item->currency_code, $options));
      }
    }

    if (isset($table_orientation) && $table_orientation == PriceTableDefaultFormatter::COMMERCE_PRICE_TABLE_VERTICAL) {
      $header_old = $header;
      $rows = [];
      $header = array($header_old[0], $row[0]);
      for ($index = 1; $index < count($row); $index++) {
        $rows[] = array('data' => array($header_old[$index], $row[$index]['data']));
      }
    }
    else {
      $rows = isset($row) ?[$row] : [];
    }

    $elements[] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

    return $elements;
  }

  /**
   * Gets the formatting options for the currency formatter.
   *
   * @return array
   *   The formatting options.
   */
  protected function getFormattingOptions() {
    $options = [
      'currency_display' => $this->getSetting('currency_display'),
    ];
    if ($this->getSetting('strip_trailing_zeroes')) {
      $options['minimum_fraction_digits'] = 0;
    }

    return $options;
  }

  /**
   * Helper method that takes care of the quantity displayed in the headers of
   * the price table.
   */
  protected function getQuantityHeaders($item) {
    // Set the quantity text to unlimited if it's -1. $item->min_qty
    $max_qty = $item->max_qty == -1 ? t('Unlimited') : $item->max_qty;
    // If max and min qtys are the same, only show one.
    if ($item->min_qty == $max_qty) {
      $quantity_text = $item->min_qty ;
    }
    else {
      $quantity_text = $item->min_qty  . ' - ' . $max_qty;
    }
    return $quantity_text;
  }
}

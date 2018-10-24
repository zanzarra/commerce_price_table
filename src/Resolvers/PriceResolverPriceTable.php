<?php

namespace Drupal\commerce_price_table\Resolvers;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Field\FieldItemList;

/**
 * Price resolver alter the base price.
 */
class PriceResolverPriceTable implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $fields = $entity->getFieldDefinitions();
    foreach ($fields as $name => $field) {
      $types[] = $field->getType();
      if ($field->getType() == 'commerce_price_table') {
        $price = $this->getTablePrice($entity->get($name), $quantity);
        if ($price) {
          $entity->setPrice($price);
        }
      }
    }
  }

  /**
   * Get Price object depending on quantity.
   *
   *  @return object
   *   Drupal\commerce_price\Price.
   */
  public function getTablePrice(FieldItemList $values, $quantity) {
    foreach ($values as $item) {
      if ($quantity >= $item->min_qty and ($quantity <= $item->max_qty or $item->max_qty == -1 )) {
        return new Price($item->amount, $item->currency_code);
        break;
      }
    }
    return NULL;
  }
}

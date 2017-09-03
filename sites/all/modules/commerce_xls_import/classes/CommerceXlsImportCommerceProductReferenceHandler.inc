<?php
/**
 * @file
 * Class definition for CommerceXlsImportCommerceProductReferenceHandler.
 */

/**
 * Class CommerceXlsImportCommerceProductReferenceHandler
 */
class CommerceXlsImportCommerceProductReferenceHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function validate($value, $wrapper, $field_name) {
    $valid = TRUE;
    $message = FALSE;

    $value = self::fromCsv($value);

    $field_info = field_info_field($field_name);
    $multivalue = (int) $field_info['cardinality'] !== 1;

    if ($multivalue) {
      $messages = array();
      foreach ($value as $sku) {
        $valid = commerce_product_validate_sku($sku);
        if (!$valid) {
          $message[] = t('Invalid SKU: @sku', array('@sku' => $sku));
        }
      }
      $message = implode(' ', $messages);
    }
    else {
      if (count($value) > 1) {
        $valid = FALSE;
      }
    }

    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => ($message) ? $message : NULL,
    );

  }

  /**
   * {@inheritdoc}
   */
  public static function set($value, $wrapper, $field_name) {
    $field_info = field_info_field($field_name);
    $multivalue = (int) $field_info['cardinality'] !== 1;

    $valid = self::validate($value, $wrapper, $field_name);

    if ($valid['status'] === COMMERCE_XLS_IMPORT_DATA_SUCCESS) {
      $skus = self::fromCsv($value);
      $products = array();
      foreach ($skus as $sku) {
        if ($product = commerce_product_load_by_sku($sku)) {
          $products[] = $product;
        }
      }

      if (!$multivalue) {
        $wrapper->{$field_name} = reset($products);
      }
      else {
        $wrapper->{$field_name} = $products;
      }
    }

    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  public static function get($wrapper, $field_name) {
    $products = $wrapper->{$field_name}->value();

    if (!is_array($products)) {
      $products = array($products);
    }

    $products = array_map(function ($item) {
      return $item->sku;
    }, $products);

    return self::toCsv($products);
  }
}

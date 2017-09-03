<?php
/**
 * @file
 * Class definition for CommerceXlsImportSkuHandler.
 */

/**
 * Class CommerceXlsImportSkuHandler
 */
class CommerceXlsImportSkuHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function validate($value, $wrapper, $field_name) {
    $valid = commerce_product_validate_sku($value) && commerce_product_validate_sku_unique($value, $wrapper->product_id->value());

    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => (!$valid) ? t('Invalid SKU.') : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function set($value, $wrapper, $field_name) {
    $valid = self::validate($value, $wrapper, $field_name);

    if ($valid['status'] === COMMERCE_XLS_IMPORT_DATA_SUCCESS) {
      $wrapper->{$field_name} = $value;
    }

    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  public static function get($wrapper, $field_name) {
    return $wrapper->{$field_name}->value();
  }
}

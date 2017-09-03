<?php
/**
 * @file
 * Class definition for CommerceXlsImportNumberDecimalHandler.
 */

/**
 * Class CommerceXlsImportNumberDecimalHandler
 */
class CommerceXlsImportNumberDecimalHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function validate($value, $wrapper, $field_name) {
    $valid = is_numeric($value);
    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => (!$valid) ? t('Value must be a valid number.') : NULL,
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

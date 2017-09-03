<?php
/**
 * @file
 * Class definition for CommerceXlsImportBooleanHandler.
 */

/**
 * Class CommerceXlsImportBooleanHandler
 */
class CommerceXlsImportBooleanHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * Values that are considered to be TRUE.
   *
   * @var array
   */
  protected static $valid_true_values = array(
    '1',
    'yes',
    'true',
  );

  /**
   * Values that are considered to be FALSE.
   *
   * @var array
   */
  protected static $valid_false_values = array(
    '0',
    'no',
    'false',
  );

  /**
   * {@inheritdoc}
   */
  public static function validate($value, $wrapper, $field_name) {
    $lower_value = strtolower($value);
    $valid = in_array($lower_value, self::$valid_true_values) || in_array($lower_value, self::$valid_false_values);
    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => (!$valid) ? t('Invalid value. Allowed values are 0, 1, yes, no, true, and false.') : NULL,
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
    $value = $wrapper->{$field_name}->value();
    // Export as an integer to avoid translating since we don't handle
    // translation on import either.
    return ($value) ? "1" : "0";
  }
}

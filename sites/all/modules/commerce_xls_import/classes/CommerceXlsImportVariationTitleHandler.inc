<?php
/**
 * @file
 * Class definition for CommerceXlsImportVariationTitleHandler.
 */

/**
 * Class CommerceXlsImportVariationTitleHandler
 */
class CommerceXlsImportVariationTitleHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * Get the value of the product variation's title.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The product entity's wrapper.
   * @param string $field_name
   *   The faked field name 'variation_title'.
   *
   * @return string
   *   The product's title.
   */
  public static function get($wrapper, $field_name) {
    return $wrapper->title->value();
  }

  /**
   * Set the title of a product variation.
   *
   * @param mixed $value
   *   The value to set.
   * @param \EntityDrupalWrapper $wrapper
   *   The product entity's wrapper.
   * @param string $field_name
   *   The faked field name 'variation_title'.
   *
   * @return array
   *   Information about whether or not the value was set and a message if
   *   applicable.
   */
  public static function set($value, $wrapper, $field_name) {
    $valid = is_string($value);

    if ($valid) {
      $wrapper->title = $value;
    }

    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => (!$valid) ? t('Invalid variation title.') : NULL,
    );
  }

}

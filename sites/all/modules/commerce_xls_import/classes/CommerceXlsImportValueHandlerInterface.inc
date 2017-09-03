<?php
/**
 * @file
 * Interface definition for CommerceXlsImportValueHandlerInterface.
 */

/**
 * Interface CommerceXlsImportValueHandlerInterface
 */
interface CommerceXlsImportValueHandlerInterface {

  /**
   * Returns the data type used for cells of this type.
   *
   * @return string
   *   The data type of the cell.
   */
  public static function dataType();

  /**
   * Validate a value without setting it.
   *
   * @param string $value
   *   The value directly from the spreadsheet.
   * @param EntityDrupalWrapper $wrapper
   *   A wrapper for the entity to set the value on.
   * @param string $field_name
   *   The name of the field to get the value from.
   *
   * @return array
   *   An array providing information about whether the value is valid or not
   *   with the following keys and values:
   *   - 'status': An integer indicating whether the value is valid or not.
   *     COMMERCE_XLS_IMPORT_DATA_SUCCESS or COMMERCE_XLS_IMPORT_DATA_ERROR.
   *   - 'message': (optional) A translated string describing what happened.
   *     This SHOULD be used if the value was not set so the message can be
   *     returned to a user.
   */
  public static function validate($value, $wrapper, $field_name);

  /**
   * Set a value on a field of an entity.
   *
   * @param mixed $value
   *   The value to set. Usually comes straight from the spreadsheet.
   * @param EntityDrupalWrapper $wrapper
   *   A wrapper for the entity to set the value on.
   * @param string $field_name
   *   The name of the field to get the value from.
   *
   * @return array
   *   An array providing information about whether the value was set or not
   *   with the following keys and values:
   *   - 'status': An integer indicating whether the value was set or not.
   *     COMMERCE_XLS_IMPORT_DATA_SUCCESS or COMMERCE_XLS_IMPORT_DATA_ERROR.
   *   - 'message': (optional) A translated string describing what happened.
   *     This SHOULD be used if the value was not set so the message can be
   *     returned to a user.
   */
  public static function set($value, $wrapper, $field_name);

  /**
   * Get the value from a field in a form suitable to insert into a spreadsheet.
   *
   * @param EntityDrupalWrapper $wrapper
   *   A wrapper for the entity to get the value from.
   * @param string $field_name
   *   The name of the field to get the value from.
   *
   * @return string
   *   The value to insert into the spreadsheet.
   */
  public static function get($wrapper, $field_name);
}

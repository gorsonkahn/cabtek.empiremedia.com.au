<?php
/**
 * @file
 * Class definition for CommerceXlsImportValueHandler.
 */

/**
 * Class CommerceXlsImportValueHandler
 */
abstract class CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * Convert an array of values to CSV.
   *
   * PHP has str_getcsv() but no str_putcsv(). fputcsv() can be used to get a
   * value by writing to memory. https://gist.github.com/johanmeiring/2894568
   *
   * @param mixed $value
   *   A string or array of strings.
   * @param string $delimiter
   *  The character to separate values by.
   * @param string $enclosure
   *   The character to enclose values in.
   *
   * @return string
   */
  public static function toCsv($value, $delimiter = ',', $enclosure = '"') {
    if (empty($value)) {
      $value = '';
    }
    elseif (is_array($value)) {
      // Protect against values that can't be converted to strings.
      $value = array_filter($value, function ($item) {
        return (is_string($item) || is_numeric($item));
      });

      // Value smaller than 5MB will be kept in memory instead of writing to a
      // temporary file.
      $fp = fopen('php://temp/maxmemory:' . (5*1024*1024), 'r+b');
      fputcsv($fp, $value, $delimiter, $enclosure);
      rewind($fp);
      $value = rtrim(stream_get_contents($fp), "\n");
    }
    elseif (!is_scalar($value)) {
      // Protect from errors by not allowing any invalid values to be exported.
      $value = '';
    }

    return $value;
  }

  /**
   * Get each value from a CSV-formatted string.
   *
   * @param string $value
   *  A string that may be a CSV format.
   *
   * @return array
   *   An array of values
   */
  public static function fromCsv($value) {
    $value = str_getcsv($value);
    return array_map('trim', $value);
  }

  /**
   * {@inheritdoc}
   */
  public static function dataType() {
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  public static function validate($value, $wrapper, $field_name) {
    return array(
      'status' => COMMERCE_XLS_IMPORT_DATA_SUCCESS,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function set($value, $wrapper, $field_name) {
    $field_info = field_info_field($field_name);
    $multivalue = (isset($field_info['cardinality']) && (int) $field_info['cardinality'] !== 1);

    if ($multivalue) {
      $value = self::fromCsv($value);
    }

    $wrapper->{$field_name} = $value;
    return array(
      'status' => COMMERCE_XLS_IMPORT_DATA_SUCCESS,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function get($wrapper, $field_name) {
    $value = $wrapper->{$field_name}->value();
    return self::toCsv($value);
  }
}

<?php
/**
 * @file
 * Class definition for CommerceXlsImportTextWithSummaryHandler.
 */

/**
 * Class CommerceXlsImportTextWithSummaryHandler
 */
class CommerceXlsImportTextWithSummaryHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function set($value, $wrapper, $field_name) {
    $field_info = field_info_field($field_name);
    $multivalue = ((int) $field_info['cardinality'] !== 1);

    if ($multivalue) {
      $value = array();
      $values = self::fromCsv($value);
      foreach ($values as $val)  {
        $value[] = array(
          'value' => $val,
          'format' => 'full_html',
        );
      }
    }
    else {
      $value = array(
        'value' => $value,
        'format' => 'full_html',
      );
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
    $field_info = field_info_field($field_name);
    $multivalue = ((int) $field_info['cardinality'] !== 1);

    $value = $wrapper->{$field_name}->value();

    if ($multivalue) {
      $value = array_map(function($item) {
        return $item['value'];
      }, $value);
      $value = self::toCsv($value);
    }
    else {
      $value = $value['value'];
    }

    return $value;
  }
}

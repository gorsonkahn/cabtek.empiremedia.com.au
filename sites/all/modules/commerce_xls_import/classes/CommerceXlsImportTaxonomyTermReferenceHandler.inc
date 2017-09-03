<?php
/**
 * @file
 * Class definition for CommerceXlsImportTaxonomyTermReferenceHandler.
 */

/**
 * Class CommerceXlsImportTaxonomyTermReferenceHandler
 */
class CommerceXlsImportTaxonomyTermReferenceHandler extends CommerceXlsImportValueHandler  implements CommerceXlsImportValueHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function validate($value, $wrapper, $field_name) {
    // TODO: Implement validate() method.
    return array(
      'status' => COMMERCE_XLS_IMPORT_DATA_SUCCESS,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function set($value, $wrapper, $field_name) {
    $vocabulary = NULL;
    $terms = array();

    $field_info = field_info_field($field_name);
    $multivalue = ((int) $field_info['cardinality'] !== 1);
    $term_names = ($multivalue) ? self::fromCsv($value) : array($value);

    // Look up the vocabulary in the field settings to limit term lookup to that
    // vocabulary.
    $type = $wrapper->type();
    $bundle = $wrapper->getBundle();
    if (!$bundle) {
      $bundle = $type;
    }
    $info = field_info_instance($type, $field_name, $bundle);
    if (isset($info['settings']['allowed_values']['vocabulary'])) {
      $vocabulary = $info['settings']['allowed_values']['vocabulary'];
    }

    // Get term ids for each term.
    // TODO this does not cause failure or an error if a term is not found.
    // Should it create a new term (option?!) or cause an error?
    foreach ($term_names as $term_name) {
      $found_terms = taxonomy_get_term_by_name($term_name, $vocabulary);
      if (!empty($found_terms)) {
        // Take the first matching term; if there are multiple it's the best we
        // can do.
        $found_term = reset($found_terms);
        $terms[] = $found_term;
      }
    }

    if ($multivalue) {
      $wrapper->{$field_name} = $terms;
    }
    else {
      $wrapper->{$field_name} = (!empty($terms)) ? reset($terms) : NULL;
    }

    return array(
      'status' => COMMERCE_XLS_IMPORT_DATA_SUCCESS,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function get($wrapper, $field_name) {
    $value = array();
    $terms = $wrapper->{$field_name}->value();
    if (!is_array($terms)) {
      $terms = array($terms);
    }

    foreach ($terms as $term) {
      $value[] = $term->name;
    }

    return self::toCsv($value);
  }
}

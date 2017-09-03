<?php
/**
 * @file
 * Class definition for CommerceXlsImportEntityReferenceHandler.
 */

/**
 * Class CommerceXlsImportEntityReferenceHandler
 */
class CommerceXlsImportEntityReferenceHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * Look up and entity by its label.
   *
   * @param string $label
   *   The entity label to search for.
   * @param string $type
   *   The entity type to search for.
   * @param string[] $bundles
   *   An array of target bundles.
   *
   * @return int[]
   *   An array of entity IDs.
   */
  protected static function label_lookup($label, $type, $bundles = array()) {
    $ids = array();

    $entity_info = entity_get_info($type);
    if (!empty($entity_info['entity keys']['label'])) {
      $label_property = $entity_info['entity keys']['label'];

      $query = new EntityFieldQuery();
      $query->entityCondition('entity_type', $type);
      if (!empty($bundles)) {
        $query->entityCondition('bundle', $bundles, 'IN');
      }
      $query->propertyCondition($label_property, $label, '=');

      $results = $query->execute();

      if (isset($results[$type])) {
        $ids = array_keys($results[$type]);
      }
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public static function set($value, $wrapper, $field_name) {
    $field_info = field_info_field($field_name);
    $type = $field_info['settings']['target_type'];
    $bundles = (!empty($field_info['settings']['handler_settings']['target_bundles'])) ? $field_info['settings']['handler_settings']['target_bundles'] : NULL;
    $multivalue = (int) $field_info['cardinality'] !== 1;

    $valid = self::validate($value, $wrapper, $field_name);

    if ($valid['status'] === COMMERCE_XLS_IMPORT_DATA_SUCCESS) {
      $entity_labels = self::fromCsv($value);
      $entity_ids = array();
      foreach ($entity_labels as $label) {
        if ($found_ids = self::label_lookup($label, $type, $bundles)) {
          // Take only the first result if multiple entities match a label.
          $entity_ids[] = reset($found_ids);
        }
      }

      if (!$multivalue) {
        $wrapper->{$field_name} = reset($entity_ids);
      }
      else {
        $wrapper->{$field_name} = $entity_ids;
      }
    }

    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  public static function get($wrapper, $field_name) {
    $field_info = field_info_field($field_name);
    $type = $field_info['settings']['target_type'];

    $entities = $wrapper->{$field_name}->value();

    if (!is_array($entities)) {
      $entities = array($entities);
    }

    $labels = array_map(function ($item) use ($type) {
      return entity_label($type, $item);
    }, $entities);

    return self::toCsv($labels);
  }
}

<?php

/**
 * @file
 * CommerceXlsImportSettings class definition.
 */

/**
 * Class CommerceXlsImportSettings contains settings used by Commerce XLS Import.
 */
class CommerceXlsImportSettings {
  private static $type = 'Import';
  private static $rowErrorStatus = array();

  /**
   * A list of field types that are excluded from being imported or exported.
   *
   * @var string[]
   */
  private static $excludedFieldTypes = array(
    'fivestar',
    'commerce_product_reference',
  );

  /**
   * The real path to extract the import images.
   *
   * @var string
   */
  private static $imageExtractPath;

  /**
   * The URI to the uploaded images archive.
   *
   * @var string
   */
  private static $imageZipUri;

  /**
   * An array of files extracted from the images archive.
   *
   * @var array
   */
  private static $extractedImageFiles;

  /**
   * Field aliases keyed by field or property name.
   *
   * @var array
   */
  private static $aliases = array();

  /**
   * Field or property names keyed by their alias.
   *
   * @var array
   */
  private static $field_names = array();

  /**
   * Settings for the import.
   *
   * @var array
   */
  private static $settings = array();

  /**
   * sets the import status to validate
   */
  public static function setValidate() {
    self::setType('Validate');
  }

  /**
   * @param $type string of the status to set
   */
  private static function setType($type) {
    self::$type = $type;
  }

  /**
   * @return bool true if the status is validate
   */
  public static function isValidate() {
    return self::isType('Validate');
  }

  /**
   * @return bool true if the status is import
   */
  public static function isImport() {
    return self::isType('Import');
  }

  /**
   * @param $type string the status you wish to check
   * @return bool if the status matches
   */
  private static function isType($type) {
    return $type == self::$type;
  }

  /**
   * Set the settings for the import.
   *
   * @param array $settings
   *   An array of settings which are arrays keyed by group.
   */
  public static function setSettings($settings) {
    self::$settings = $settings;
  }

  /**
   * Get the settings for the import.
   *
   * @return array
   *   All of the import settings.
   */
  public static function getSettings() {
    return self::$settings;
  }

  /**
   * Get a specific setting.
   *
   * @param string $settings_group
   *   The name of the settings group.
   * @param string $setting
   *   Optional name of a specific setting.
   *
   * @return mixed
   *   The value of the setting if it exists, otherwise FALSE.
   *
   * Example usage:
   * @code
   * CommerceXlsImportSettings::getSetting('my_module', 'my_custom_setting');
   * @endcode
   */
  public static function getSetting($settings_group, $setting = NULL) {
    if (isset(self::$settings[$settings_group])) {
      // Get a specific setting.
      if (isset($setting) && isset(self::$settings[$settings_group][$setting])) {
        return self::$settings[$settings_group][$setting];
      }

      // Get the whole settings group.
      return self::$settings[$settings_group];
    }

    return FALSE;
  }

  /**
   * @param $row int row to set the error for
   * @param $status
   */
  public static function setRowError($row) {
    self::$rowErrorStatus[$row] = TRUE;
  }

  /**
   * @param $row int row to test against
   * @return bool return true of row has errors
   */
  public static function rowHasError($row) {
    if (isset(self::$rowErrorStatus[$row])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Determine if a field type is excluded from import or export.
   *
   * @param string $type
   *   A field type.
   *
   * @return bool
   *   TRUE if the type is excluded.
   */
  public static function isExcluded($type) {
    return in_array($type, self::$excludedFieldTypes);
  }

  /**
   * Get the path to extract the import images.
   *
   * @return string
   *   The real path to extract the images.
   */
  public static function imageExtractPath() {
    if (!isset(self::$imageExtractPath)) {
      $temp_dir = drupal_realpath('temporary://');
      $images_dir = "{$temp_dir}/commerce_xls_import/images";
      $writable = file_prepare_directory($images_dir, FILE_CREATE_DIRECTORY|FILE_MODIFY_PERMISSIONS);
      self::$imageExtractPath = ($writable) ? $images_dir : FALSE;
    }

    return self::$imageExtractPath;
  }

  /**
   * Get the URI for the uploaded images zip file.
   *
   * @return string
   */
  public static function imageZipUri() {
    if (!isset(self::$imageZipUri)) {
      self::$imageZipUri = file_build_uri('product_images.zip');
    }

    return self::$imageZipUri;
  }

  /**
   * Get the real path to an image from the images archive.
   *
   * @param string $filename
   *   The name of the image file.
   *
   * @return FALSE|string
   *   Returns the path if the file exists in the uploaded files, FALSE
   *   otherwise.
   */
  public static function imagePath($filename) {
    if (!isset(self::$extractedImageFiles)) {
      self::$extractedImageFiles = array_diff(scandir(self::imageExtractPath()), array('.', '..'));
    }

    $index = array_search($filename, self::$extractedImageFiles);
    if ($index !== FALSE) {
      return self::imageExtractPath() . '/' . self::$extractedImageFiles[$index];
    }

    return FALSE;
  }

  /**
   * Set the list of field aliases.
   *
   * @param array $aliases
   *   An array of aliases where keys are field or property names and values are
   *   the alias for that field.
   */
  public static function setAliases($aliases) {
    self::$aliases = $aliases;
    self::$field_names = array_flip($aliases);
  }

  /**
   * Get the field or property name for an alias.
   *
   * @param string $alias
   *   A field name that may be an alias.
   *
   * @return string
   */
  public static function field_name($alias) {
    return (!empty(self::$field_names[$alias])) ? self::$field_names[$alias] : $alias;
  }

  /**
   * Get the alias for a field or property name.
   *
   * @param string $field_name
   *   A field name.
   *
   * @return string
   */
  public static function field_alias($field_name) {
    return (!empty(self::$aliases[$field_name])) ? self::$aliases[$field_name] : $field_name;
  }

}

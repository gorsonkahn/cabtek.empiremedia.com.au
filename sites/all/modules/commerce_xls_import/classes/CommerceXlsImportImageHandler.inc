<?php
/**
 * @file
 * Class definition for CommerceXlsImportImageHandler.
 */

/**
 * Class CommerceXlsImportImageHandler
 */
class CommerceXlsImportImageHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * @param string $filename
   *   The name of the image file.
   * @param string $file_directory
   *   The URI for the directory the image could be in if not included in the
   *   images archive. Will be used as an alternate place to check for the
   *   image.
   *
   * @return array
   *   An array of validation information as with validate() and set().
   */
  private static function validImage($filename, $file_directory = NULL) {
    $valid = TRUE;
    $message = NULL;

    $image_path = CommerceXlsImportSettings::imagePath($filename);
    if (!$image_path) {
      $image_path = $file_directory . $filename;
    }

    if (file_exists($image_path)){
      $mime_type = file_get_mimetype($image_path);
      if (!preg_match('/^image/', $mime_type)) {
        $valid = FALSE;
        $message = t('Invalid image format for @file (@mime).', array(
          '@file' => $filename,
          '@mime' => $mime_type,
        ));
      }
    }
    else {
      $valid = FALSE;
      $message = t('File @filename does not exist.', array(
        '@filename' => $filename,
      ));
    }

    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => $message,
    );
  }

  /**
   * {@inheritdoc]
   */
  public static function validate($value, $wrapper, $field_name) {
    $valid = TRUE;

    $field_info = field_info_field($field_name);
    $multivalue = (int) $field_info['cardinality'] !== 1;

    $entity_type = $wrapper->type();
    $bundle = $wrapper->getBundle();
    if (!$bundle) {
      $bundle = $entity_type;
    }
    $instance = field_info_instance($entity_type, $field_name, $bundle);
    $file_destination = $field_info['settings']['uri_scheme'] . '://' . ($instance['settings']['file_directory'] ? $instance['settings']['file_directory'] . '/' : '');

    //TODO should add check if Csv value provided for single cardinality field
    $images = ($multivalue) ? self::fromCsv($value) : array($value);

    $messages = [];
    foreach ($images as $filename) {
      $valid_filename = self::validImage($filename, $file_destination);
      if ($valid_filename['status'] !== COMMERCE_XLS_IMPORT_DATA_SUCCESS) {
        $valid = FALSE;
        $messages[] = (!empty($valid_filename['message'])) ? $valid_filename['message'] : t('Unknown error for file @filename.', array('@filename' => $filename));
      }
    }

    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => (!$valid) ? implode("\n", $messages) : NULL,
    );
  }

  /**
   * {@inheritdoc]
   */
  public static function set($value, $wrapper, $field_name) {
    $valid = TRUE;
    $field_info = field_info_field($field_name);

    $entity_type = $wrapper->type();
    $bundle = $wrapper->getBundle();
    if (!$bundle) {
      $bundle = $entity_type;
    }

    $instance = field_info_instance($entity_type, $field_name, $bundle);
    $file_destination = $field_info['settings']['uri_scheme'] . '://' . ($instance['settings']['file_directory'] ? $instance['settings']['file_directory'] . '/' : '');
    $multivalue = (int) $field_info['cardinality'] !== 1;
    $file_names = ($multivalue) ? self::fromCsv($value) : array($value);

    $files = array();
    $messages = array();
    foreach ($file_names as $filename) {
      if (empty($filename)) {
        break;
      }

      // Validate the file and skip it if it's invalid.
      $valid_filename = self::validImage($filename, $file_destination);
      if ($valid_filename['status'] !== COMMERCE_XLS_IMPORT_DATA_SUCCESS) {
        $valid = FALSE;
        $messages[] = (!empty($valid_filename['message'])) ? $valid_filename['message'] : t('Unknown error for file @filename.', array('@filename' => $filename));
        break;
      }

      $file_uri = $file_destination . $filename;
      if ($path = CommerceXlsImportSettings::imagePath($filename)) {
        $writable = file_prepare_directory($file_destination, FILE_CREATE_DIRECTORY|FILE_MODIFY_PERMISSIONS);
        if (!$writable) {
          $valid = FALSE;
          $messages[] = t('File @filename can not be saved. Unable to write to @dir.', array(
            '@filename' => $filename,
            '@dir' => $file_destination,
          ));
          break;
        }
      }

      // Break if file does not exist in the archive images or the image field's
      // directory.
      if (file_exists($path)) {
        if (!file_unmanaged_copy($path, $file_uri, FILE_EXISTS_REPLACE)) {
          $valid = FALSE;
          $messages[] = t('Unable to move file @filename to @uri.', array('@filename' => $filename, '@uri' => $file_uri));
          break;
        }
      }
      else {
        if (!file_exists(drupal_realpath($file_uri))) {
          $valid = FALSE;
          $messages[] = t('File @filename not found.', array('@filename' => $filename));
          break;
        }
      }

      // If a file entity already exists for the URI, use it.
      if ($fid = commerce_xls_import_file_entry_exists($file_uri)) {
        $file = file_load($fid);
      }
      else {
        $file = new stdClass();
      }

      $file->uid = $GLOBALS['user']->uid;
      $file->filename = $filename;
      $file->uri = $file_uri;
      $file->filemime = file_get_mimetype($file_uri);
      $file->filesize = filesize($file_uri);
      $file->status = 1;
      $file->display = 1;
      file_save($file);
      $files[] = (array) $file;
    }

    //TODO, move this check up so as to not waste time?
    //the field could have an instance, but now be deleted and not be part of this wrapper anymore
    if(isset($wrapper->{$field_name})) {
      $wrapper->{$field_name} = (!empty($files)) ? $files : NULL;
    }

    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => (!$valid) ? implode("\n", $messages) : NULL,
    );
  }

  /**
   * {@inheritdoc]
   */
  public static function get($wrapper, $field_name) {
    $field_info = field_info_field($field_name);
    $multivalue = (int) $field_info['cardinality'] !== 1;

    $images = $wrapper->{$field_name}->value();
    if (!$multivalue) {
      $images = array($images);
    }

    $images = array_map(function ($image) {
      return $image['filename'];
    }, $images);

    return self::toCsv($images);
  }
}

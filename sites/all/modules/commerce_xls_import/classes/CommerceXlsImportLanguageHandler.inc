<?php
/**
 * @file
 * Class definition for CommerceXlsImportLanguageHandler.
 */

/**
 * Class CommerceXlsImportLanguageHandler
 */
class CommerceXlsImportLanguageHandler extends CommerceXlsImportValueHandler implements CommerceXlsImportValueHandlerInterface {

  /**
   * Languages that are enabled on the site.
   *
   * @var array
   *
   * @see language_list().
   */
  public static $languages;

  /**
   * {@inheritdoc}
   */
  public static function validate($value, $wrapper, $field_name) {
    if (!isset(self::$languages)) {
      self::$languages = language_list();
    }

    $value = strtolower($value);
    $valid = FALSE;

    // Empty value is fine since it'll be set to LANGUAGE_NONE.
    if (empty($value) || $value === 'und' || in_array($value, array_keys(self::$languages))) {
      $valid = TRUE;
    }
    else {
      foreach (self::$languages as $language) {
        if ($value == $language->name || $value == $language->native) {
          $valid = TRUE;
          break;
        }
      }
    }

    return array(
      'status' => ($valid) ? COMMERCE_XLS_IMPORT_DATA_SUCCESS : COMMERCE_XLS_IMPORT_DATA_ERROR,
      'message' => (!$valid) ? t('Invalid value. You must enable a language before importing with it.') : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function set($value, $wrapper, $field_name) {
    $valid = self::validate($value, $wrapper, $field_name);

    if (empty($value) || $value === LANGUAGE_NONE) {
      $wrapper->language(LANGUAGE_NONE);
      return $valid;
    }

    $value = strtolower($value);

    if ($valid['status'] === COMMERCE_XLS_IMPORT_DATA_SUCCESS) {
      foreach (self::$languages as $language) {
        if ($value == $language->language || $value == $language->name || $value == $language->native) {
          $wrapper->language($language->language);
          break;
        }
      }
    }

    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  public static function get($wrapper, $field_name) {
    $value = $wrapper->{$field_name}->value();
    return $value;
  }

}

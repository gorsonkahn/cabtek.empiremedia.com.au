<?php
/**
 * @file
 * Module API documentation and examples.
 */

/**
 * Provide information about the handler for a field type.
 *
 * Classes will be loaded when required and should not be placed in the module's
 * info file.
 *
 * @return array
 *   An array of handler definitions keyed by field type. Values are:
 *   - class_name: The name of the handler's class.
 *
 * @see hook_commerce_xls_import_field_type_handler_info_alter().
 */
function hook_commerce_xls_import_field_type_handler_info() {
  return array(
    'foo_type' => array(
      'class_name' => 'MyModuleCommerceXlsImportFooHandler',
    ),
  );
}

/**
 * Alter information about the field type handlers.
 *
 * @param array $handlers
 *   The existing handler definitions.
 *
 * @see hook_commerce_xls_import_field_type_handler_info().
 */
function hook_commerce_xls_import_field_type_handler_info_alter(&$handlers) {
  if (isset($handlers['foo_type'])) {
    $handlers['foo_type']['class_name'] = 'MyModuleCommerceXlsImportFooTypeHandler';
  }
}

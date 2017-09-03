<?php

/**
 * @file
 * Admin page for product exports.
 */

/**
 * Import Menu Form.
 *
 * Contains the buttons export products.
 *
 * @return array $form
 *  The array containing the export and import buttons.
 */
function commerce_xls_import_start_export() {
  $form = array();
  $product_types = array();
  $display_types = array();

  foreach (commerce_product_types() as $product_type) {
    $product_types[$product_type['type']] = $product_type['name'];
  }

  foreach (node_type_get_types() as $node_type) {
    $display_types[$node_type->type] = $node_type->name;
  }

  $export_uri = file_build_uri('export');
  $files = file_scan_directory($export_uri, '/.*/');
  if (!empty($files)) {
    $form['downloads'] = array(
      '#type' => 'fieldset',
      '#title' => t('Download Exports'),
    );

    foreach ($files as $file) {
      $url = file_create_url($file->uri);
      $real_path = drupal_realpath($file->uri);
      $modified = date('Y-m-d H:i:s', filemtime($real_path));

      $form['downloads'][] = array(
        '#markup' => l($file->name . ' (' . $modified . ')', $url),
      );
    }
  }

  $form['title'] = array(
    '#markup' => '<h1>' . t('Commerce XLS Import - Export') . '</h1>',
  );

  $form['product_type'] = array(
    '#title' => t('Product Type'),
    '#type' => 'select',
    '#description' => t('Select the product type to export.'),
    '#options' => $product_types,
  );

  $form['display_type'] = array(
    '#title' => t('Display Type'),
    '#type' => 'select',
    '#description' => t('Select the display (node) type to export.'),
    '#options' => $display_types,
  );

  // Create a container for other modules to add custom settings to.
  $form['settings'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
  );

  $form['export'] = array(
    '#type' => 'markup',
    '#prefix' => '<div class="product-options ' . t('export') . '">',
    '#suffix' => '</div><div style="clear:both"></div>',
  );

  $library = libraries_load('spout');
  if ($library['loaded'] === FALSE) {
    drupal_set_message(t('The Spout library could not be loaded. It must be installed to export products.'), 'error');
  }

  if (!commerce_xls_import_is_running()) {
    $form['export_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Begin Export'),
      '#disabled' => ($library['loaded'] === FALSE) ? TRUE : FALSE,
    );
  }
  else {
    $form['warning'] = array(
      '#markup' => '<h6 style="color: red">' . t('Please wait for the current import/validation to complete') . '</h6>',
    );
    drupal_set_message(t('You are currently running an import or validation, you will not be able to run an export until it is finished or stopped manually'), 'warning', FALSE);
  }

  return $form;
}

/**
 * Submit handler for the import start form.
 *
 * @param array $form
 *  The form containing the values being submitted.
 *
 * @param array $form_state
 *  The form state array, passed by reference.
 */
function commerce_xls_import_start_export_submit($form, &$form_state) {
  if (commerce_xls_import_is_running()) {
    drupal_set_message(t('You are currently running an file validation. Please wait until it is finished or cancel the current one'), 'error', FALSE);
    return;
  }

  $product_type = $form_state['values']['product_type'];
  $display_type = $form_state['values']['display_type'];

  // Default settings to an empty array as it will not exist unless another
  // module adds custom settings.
  $settings = (!empty($form_state['values']['settings'])) ? $form_state['values']['settings'] : array();

  commerce_xls_import_export_process($product_type, $display_type, $settings);
}

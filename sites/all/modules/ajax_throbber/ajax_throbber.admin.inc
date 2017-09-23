<?php
/**
 * Admin page.
 */

/**
 * Menu callback for admin form.
 *
 * @return mixed
 */
function ajax_throbber_admin() {
  $form = array();

  $exclude_machine = 'exclude';
  $include_machine = 'include';

  $exclude_paths = variable_get(AJAX_THROBBER_VARIABLE_EXCLUDE_PATH, 'admin/*');
  $include_paths = variable_get(AJAX_THROBBER_VARIABLE_INCLUDE_PATH, '');
  $selection = variable_get('ajax_throbber_selection', 'three_dots');
  $bg_color = variable_get('ajax_throbber_bg_color', '0,0,0, 0.75');
  $item_color = variable_get('ajax_throbber_item_color', '255,255,255, 0.8');

  $form[AJAX_THROBER_VARIABLE_EXCL_INCL] = array(
    '#type' => 'select',
    '#options' => array (
      $exclude_machine => t('Exclude'),
      $include_machine => t('Include'),
    ),
    '#title' => t('Select exclude or include paths'),
    '#default_value' => variable_get(AJAX_THROBER_VARIABLE_EXCL_INCL, 'exclude'),
  );

  $form[AJAX_THROBBER_VARIABLE_EXCLUDE_PATH] = array(
    '#type' => 'textarea',
    '#title' => t('Exclude paths for ajax throbber'),
    '#description' => t('Specify pages by using their paths. Enter one path per line. The \'*\' character is a wildcard. Example paths are blog for the blog page and blog/* for every personal blog.'),
    '#default_value' => $exclude_paths,
    '#required' => FALSE,
    '#states' => array(
      'visible' => array(
        ':input[name="ajax_throbber_exclude_include"]' => array(
          'value' => $exclude_machine),
      ),
    ),
  );

  $form[AJAX_THROBBER_VARIABLE_INCLUDE_PATH] = array(
    '#type' => 'textarea',
    '#title' => t('Include paths for ajax throbber'),
    '#description' => t('Specify pages by using their paths. Enter one path per line. The \'*\' character is a wildcard. Example paths are blog for the blog page and blog/* for every personal blog.'),
    '#default_value' => $include_paths,
    '#required' => FALSE,
    '#states' => array(
      'visible' => array(
        ':input[name="ajax_throbber_exclude_include"]' => array(
          'value' => $include_machine),
      ),
    ),
  );

  $form['ajax_throbber_selection'] = array(
    '#type' => 'select',
    '#options' => array (
      'circle' => 'circle',
      'three_dots' => 'three dots',
      'square' => 'square',
      'fading_circle' => 'fading circle',
    ),
    '#title' => t('Select Throbber Style'),
    '#default_value' => $selection,
    '#required' => TRUE,
  );

  $form['ajax_throbber_bg_color'] = array(
    '#type' => 'textfield',
    '#title' => t('Background Color'),
    '#description' => t('Use the rgba-format, e.g. 0,0,0, 0.75'),
    '#default_value' => $bg_color,
    '#required' => TRUE,
  );

  $form['ajax_throbber_item_color'] = array(
    '#type' => 'textfield',
    '#title' => t('Item Color'),
    '#description' => t('Use the rgba-format, e.g. 255,255,255, 0.8'),
    '#default_value' => $item_color,
    '#required' => TRUE,
  );

  $form['ajax_throbber_test'] = array(
    '#type' => 'button',
    '#value' => t('Test the Ajax throbber (@time sec)',
      array(
        '@time' => AJAX_THROBBER_ADMIN_TEST_SLEEP_TIME
      )),
    '#suffix' => t('Save configuration below before testing.'),
    '#ajax' => array(
      'callback' => '_ajax_throbber_form_ajax_submit',
    ),
  );

  return system_settings_form($form);
}

/**
 * Ajax callback function.
 */
function _ajax_throbber_form_ajax_submit($form) {
  sleep(AJAX_THROBBER_ADMIN_TEST_SLEEP_TIME);
  return $form;
}

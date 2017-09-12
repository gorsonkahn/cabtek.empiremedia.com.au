<?php

/**
 * @file
 * Implements the shopping cart system and add to cart features.
 *
 * In Drupal Commerce, the shopping cart is really just an order that makes
 * special considerations to associate it with a user and
 */

// Define constants for the shopping cart refresh modes.
define('COMMERCE_CART_REFRESH_ALWAYS', 'always');
define('COMMERCE_CART_REFRESH_OWNER_ONLY', 'owner_only');
define('COMMERCE_CART_REFRESH_ACTIVE_CART_ONLY', 'active_cart_only');
define('COMMERCE_CART_REFRESH_DEFAULT_FREQUENCY', 15);

/**
 * Implements hook_menu().
 */
function commerce_cart_menu() {
  $items = array();

  $items['cart'] = array(
    'title' => 'Shopping cart',
    'page callback' => 'commerce_cart_view',
    'access arguments' => array('access content'),
    'file' => 'includes/commerce_cart.pages.inc',
  );

  $items['cart/my'] = array(
    'title' => 'Shopping cart (# items)',
    'title callback' => 'commerce_cart_menu_item_title',
    'title arguments' => array(TRUE),
    'page callback' => 'commerce_cart_menu_item_redirect',
    'access arguments' => array('access content'),
    'type' => MENU_SUGGESTED_ITEM,
  );

  $items['checkout'] = array(
    'title' => 'Checkout',
    'page callback' => 'commerce_cart_checkout_router',
    'access arguments' => array('access checkout'),
    'type' => MENU_CALLBACK,
    'file' => 'includes/commerce_cart.pages.inc',
  );

  // If the Order UI module is installed, add a local action to it that lets an
  // administrator execute a cart order refresh on the order. Modules that
  // define their own order edit menu item are also responsible for defining
  // their own local action menu items if needed.
  if (module_exists('commerce_order_ui')) {
    $items['admin/commerce/orders/%commerce_order/edit/refresh'] = array(
      'title' => 'Apply pricing rules',
      'description' => 'Executes the cart order refresh used to apply all current pricing rules on the front end.',
      'page callback' => 'drupal_get_form',
      'page arguments' => array('commerce_cart_order_refresh_form', 3),
      'access callback' => 'commerce_cart_order_refresh_form_access',
      'access arguments' => array(3),
      'type' => MENU_LOCAL_ACTION,
      'file' => 'includes/commerce_cart.admin.inc',
    );
  }

  return $items;
}

/**
 * Returns the title of the shopping cart menu item with an item count.
 */
function commerce_cart_menu_item_title() {
  global $user;

  // Default to a static title.
  $title = t('Shopping cart');

  // If the user actually has a cart order...
  if ($order = commerce_cart_order_load($user->uid)) {
    // Count the number of product line items on the order.
    $wrapper = entity_metadata_wrapper('commerce_order', $order);
    $quantity = commerce_line_items_quantity($wrapper->commerce_line_items, commerce_product_line_item_types());

    // If there are more than 0 product line items on the order...
    if ($quantity > 0) {
      // Use the dynamic menu item title.
      $title = format_plural($quantity, 'Shopping cart (1 item)', 'Shopping cart (@count items)');
    }
  }

  return $title;
}

/**
 * Redirects a valid page request to cart/my to the cart page.
 */
function commerce_cart_menu_item_redirect() {
  drupal_goto('cart');
}

/**
 * Access callback: determines access to the "Apply pricing rules" local action.
 */
function commerce_cart_order_refresh_form_access($order) {
  // Do not show the link for cart orders as they're refreshed automatically.
  if (commerce_cart_order_is_cart($order)) {
    return FALSE;
  }

  // Returns TRUE if the link is enabled via the order settings form and the
  // user has access to update the order.
  return variable_get('commerce_order_apply_pricing_rules_link', TRUE) && commerce_order_access('update', $order);
}

/**
 * Implements hook_hook_info().
 */
function commerce_cart_hook_info() {
  $hooks = array(
    'commerce_cart_order_id' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_order_is_cart' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_order_convert' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_line_item_refresh' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_order_refresh' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_order_empty' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_attributes_refresh_alter' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_product_comparison_properties_alter' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_product_prepare' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_product_add' => array(
      'group' => 'commerce',
    ),
    'commerce_cart_product_remove' => array(
      'group' => 'commerce',
    ),
  );

  return $hooks;
}

/**
 * Implements hook_commerce_order_state_info().
 */
function commerce_cart_commerce_order_state_info() {
  $order_states = array();

  $order_states['cart'] = array(
    'name' => 'cart',
    'title' => t('Shopping cart'),
    'description' => t('Orders in this state have not been completed by the customer yet.'),
    'weight' => -5,
    'default_status' => 'cart',
  );

  return $order_states;
}

/**
 * Implements hook_commerce_order_status_info().
 */
function commerce_cart_commerce_order_status_info() {
  $order_statuses = array();

  $order_statuses['cart'] = array(
    'name' => 'cart',
    'title' => t('Shopping cart'),
    'state' => 'cart',
    'cart' => TRUE,
  );

  return $order_statuses;
}

/**
 * Implements hook_commerce_checkout_pane_info().
 */
function commerce_cart_commerce_checkout_pane_info() {
  $checkout_panes = array();

  $checkout_panes['cart_contents'] = array(
    'title' => t('Shopping cart contents'),
    'base' => 'commerce_cart_contents_pane',
    'file' => 'includes/commerce_cart.checkout_pane.inc',
    'page' => 'checkout',
    'weight' => -10,
  );

  return $checkout_panes;
}

/**
 * Implements hook_commerce_checkout_complete().
 */
function commerce_cart_commerce_checkout_complete($order) {
  // Move the cart order ID to a completed order ID.
  if (commerce_cart_order_session_exists($order->order_id)) {
    commerce_cart_order_session_save($order->order_id, TRUE);
    commerce_cart_order_session_delete($order->order_id);
  }
}

/**
 * Implements hook_commerce_line_item_summary_link_info().
 */
function commerce_cart_commerce_line_item_summary_link_info() {
  return array(
    'view_cart' => array(
      'title' => t('View cart'),
      'href' => 'cart',
      'attributes' => array('rel' => 'nofollow'),
      'weight' => 0,
    ),
    'checkout' => array(
      'title' => t('Checkout'),
      'href' => 'checkout',
      'attributes' => array('rel' => 'nofollow'),
      'weight' => 5,
      'access' => user_access('access checkout'),
    ),
  );
}

/**
 * Implements hook_form_alter().
 */
function commerce_cart_form_alter(&$form, &$form_state, $form_id) {
  if (strpos($form_id, 'views_form_commerce_cart_form_') === 0) {
    // Only alter buttons if the cart form View shows line items.
    $view = reset($form_state['build_info']['args']);

    if (!empty($view->result)) {
      // Change the Save button to say Update cart.
      $form['actions']['submit']['#value'] = t('Update cart');
      $form['actions']['submit']['#submit'] = array_merge($form['#submit'], array('commerce_cart_line_item_views_form_submit'));

      // Change any Delete buttons to say Remove.
      if (!empty($form['edit_delete'])) {
        foreach(element_children($form['edit_delete']) as $key) {
          // Load and wrap the line item to have the title in the submit phase.
          if (!empty($form['edit_delete'][$key]['#line_item_id'])) {
            $line_item_id = $form['edit_delete'][$key]['#line_item_id'];
            $form_state['line_items'][$line_item_id] = commerce_line_item_load($line_item_id);

            $form['edit_delete'][$key]['#value'] = t('Remove');
            $form['edit_delete'][$key]['#submit'] = array_merge($form['#submit'], array('commerce_cart_line_item_delete_form_submit'));
          }
        }
      }
    }
    else {
      // Otherwise go ahead and remove any buttons from the View.
      unset($form['actions']);
    }
  }
  elseif (strpos($form_id, 'commerce_checkout_form_') === 0 && !empty($form['buttons']['cancel'])) {
    // Override the submit handler for changing the order status on checkout cancel.
    foreach ($form['buttons']['cancel']['#submit'] as $key => &$value) {
      if ($value == 'commerce_checkout_form_cancel_submit') {
        $value = 'commerce_cart_checkout_form_cancel_submit';
      }
    }
  }
  elseif (strpos($form_id, 'views_form_commerce_cart_block') === 0) {
    // No point in having a "Save" button on the shopping cart block.
    unset($form['actions']);
  }
}

/**
 * Submit handler to take back the order to cart status on cancel in checkout.
 */
function commerce_cart_checkout_form_cancel_submit($form, &$form_state) {
  // Update the order to the cart status.
  $order = commerce_order_load($form_state['order']->order_id);
  $form_state['order'] = commerce_order_status_update($order, 'cart', TRUE);

  // Skip saving in the status update and manually save here to force a save
  // even when the status doesn't actually change.
  if (variable_get('commerce_order_auto_revision', TRUE)) {
    $form_state['order']->revision = TRUE;
    $form_state['order']->log = t('Customer manually canceled the checkout process.');
  }

  commerce_order_save($form_state['order']);

  drupal_set_message(t('Checkout of your current order has been canceled and may be resumed when you are ready.'));

  // Redirect to cart on cancel.
  $form_state['redirect'] = 'cart';
}

/**
 * Submit handler to show the shopping cart updated message.
 */
function commerce_cart_line_item_views_form_submit($form, &$form_state) {
  // Reset the status of the order to cart.
  $order = commerce_order_load($form_state['order']->order_id);
  $form_state['order'] = commerce_order_status_update($order, 'cart', TRUE);

  // Skip saving in the status update and manually save here to force a save
  // even when the status doesn't actually change.
  if (variable_get('commerce_order_auto_revision', TRUE)) {
    $form_state['order']->revision = TRUE;
    $form_state['order']->log = t('Customer updated the order via the shopping cart form.');
  }

  commerce_order_save($form_state['order']);

  drupal_set_message(t('Your shopping cart has been updated.'));
}

/**
 * Submit handler to show the line item delete message.
 */
function commerce_cart_line_item_delete_form_submit($form, &$form_state) {
  $line_item_id = $form_state['triggering_element']['#line_item_id'];

  // Get the corresponding wrapper to show the correct title.
  $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $form_state['line_items'][$line_item_id]);

  // If the deleted line item is a product...
  if (in_array($line_item_wrapper->getBundle(), commerce_product_line_item_types())) {
    $title = $line_item_wrapper->commerce_product->title->value();
  }
  else {
    $title = $line_item_wrapper->line_item_label->value();
  }

  drupal_set_message(t('%title removed from your cart.', array('%title' => $title)));
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Adds a checkbox to the order settings form to enable the local action on
 * order edit forms to apply pricing rules.
 */
function commerce_cart_form_commerce_order_settings_form_alter(&$form, &$form_state) {
  $form['commerce_order_apply_pricing_rules_link'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable the local action link on order edit forms to apply pricing rules.'),
    '#description' => t('Even if enabled the link will not appear on shopping cart order edit forms.'),
    '#default_value' => variable_get('commerce_order_apply_pricing_rules_link', TRUE),
    '#weight' => 10,
  );

  // Add a fieldset for settings pertaining to the shopping cart refresh.
  $form['cart_refresh'] = array(
    '#type' => 'fieldset',
    '#title' => t('Shopping cart refresh'),
    '#description' => t('Shopping cart orders comprise orders in shopping cart and some checkout related order statuses. These settings let you control how the shopping cart orders are refreshed, the process during which product prices are recalculated, to improve site performance in the case of excessive refreshes on sites with less dynamic pricing needs.'),
    '#weight' => 40,
  );
  $form['cart_refresh']['commerce_cart_refresh_mode'] = array(
    '#type' => 'radios',
    '#title' => t('Shopping cart refresh mode'),
    '#options' => array(
      COMMERCE_CART_REFRESH_ALWAYS => t('Refresh a shopping cart when it is loaded regardless of who it belongs to.'),
      COMMERCE_CART_REFRESH_OWNER_ONLY => t('Only refresh a shopping cart when it is loaded if it belongs to the current user.'),
      COMMERCE_CART_REFRESH_ACTIVE_CART_ONLY => t("Only refresh a shopping cart when it is loaded if it is the current user's active shopping cart."),
    ),
    '#default_value' => variable_get('commerce_cart_refresh_mode', COMMERCE_CART_REFRESH_OWNER_ONLY),
  );
  $form['cart_refresh']['commerce_cart_refresh_frequency'] = array(
    '#type' => 'textfield',
    '#title' => t('Shopping cart refresh frequency'),
    '#description' => t('Shopping carts will only be refreshed if more than the specified number of seconds have passed since they were last refreshed.'),
    '#default_value' => variable_get('commerce_cart_refresh_frequency', COMMERCE_CART_REFRESH_DEFAULT_FREQUENCY),
    '#required' => TRUE,
    '#size' => 32,
    '#field_suffix' => t('seconds'),
    '#element_validate' => array('commerce_cart_validate_refresh_frequency'),
  );
  $form['cart_refresh']['commerce_cart_refresh_force'] = array(
    '#type' => 'checkbox',
    '#title' => t('Always refresh shopping cart orders on shopping cart and checkout form pages regardless of other settings.'),
    '#description' => t('Note: this option only applies to the core /cart and /checkout/* paths.'),
    '#default_value' => variable_get('commerce_cart_refresh_force', TRUE),
  );
}

/**
 * Form element validation handler for the cart refresh frequency value.
 */
function commerce_cart_validate_refresh_frequency($element, &$form_state) {
  $value = $element['#value'];
  if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value < 0)) {
    form_error($element, t('%name must be 0 or a positive integer.', array('%name' => $element['#title'])));
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Alter the order edit form so administrators cannot attempt to alter line item
 * unit prices for orders still in a shopping cart status. On order load, the
 * cart module refreshes these prices based on the current product price and
 * pricing rules, so any alterations would not be persistent anyways.
 *
 * @see commerce_cart_commerce_order_load()
 */
function commerce_cart_form_commerce_order_ui_order_form_alter(&$form, &$form_state) {
  $order = $form_state['commerce_order'];

  // If the order being edited is in a shopping cart status and the form has the
  // commerce_line_items element present...
  if (commerce_cart_order_is_cart($order) && !empty($form['commerce_line_items'])) {
    // Grab the instance info for commerce_line_items and only alter the form if
    // it's using the line item manager widget.
    $instance = field_info_instance('commerce_order', 'commerce_line_items', field_extract_bundle('commerce_order', $order));

    if ($instance['widget']['type'] == 'commerce_line_item_manager') {
      // Loop over the line items on the form...
      foreach ($form['commerce_line_items'][$form['commerce_line_items']['#language']]['line_items'] as &$line_item) {
        // Disable the unit price amount and currency code fields.
        $language = $line_item['commerce_unit_price']['#language'];
        $line_item['commerce_unit_price'][$language][0]['amount']['#disabled'] = TRUE;
        $line_item['commerce_unit_price'][$language][0]['currency_code']['#disabled'] = TRUE;
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Alters the Field UI field edit form to add per-instance settings for fields
 * on product types governing the use of product fields as attribute selection
 * fields on the Add to Cart form.
 */
function commerce_cart_form_field_ui_field_edit_form_alter(&$form, &$form_state) {
  // Extract the instance info from the form.
  $instance = $form['#instance'];

  // If the current field instance is not locked, is attached to a product type,
  // and of a field type that defines an options list...
  if (empty($form['locked']) && $instance['entity_type'] == 'commerce_product' &&
    function_exists($form['#field']['module'] . '_options_list')) {
    // Get the current instance's attribute settings for use as default values.
    $commerce_cart_settings = commerce_cart_field_instance_attribute_settings($instance);

    $form['instance']['commerce_cart_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Attribute field settings'),
      '#description' => t('Single value fields attached to products can function as attribute selection fields on Add to Cart forms. When an Add to Cart form contains multiple products, attribute field data can be used to allow customers to select a product based on the values of the field instead of just from a list of product titles.'),
      '#weight' => 5,
      '#collapsible' => FALSE,
    );
    $form['instance']['commerce_cart_settings']['attribute_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable this field to function as an attribute field on Add to Cart forms.'),
      '#default_value' => $commerce_cart_settings['attribute_field'],
    );
    $form['instance']['commerce_cart_settings']['attribute_widget'] = array(
      '#type' => 'radios',
      '#title' => t('Attribute selection widget'),
      '#description' => t('The type of element used to select an option if used on an Add to Cart form.'),
      '#options' => array(
        'select' => t('Select list'),
        'radios' => t('Radio buttons'),
      ),
      '#default_value' => $commerce_cart_settings['attribute_widget'],
      '#states' => array(
        'visible' => array(
          ':input[name="instance[commerce_cart_settings][attribute_field]"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Determine the default attribute widget title.
    $attribute_widget_title = $commerce_cart_settings['attribute_widget_title'];

    if (empty($attribute_widget_title)) {
      $attribute_widget_title = $instance['label'];
    }

    $form['instance']['commerce_cart_settings']['attribute_widget_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Attribute widget title'),
      '#description' => t('Specify the title to use for the attribute widget on the Add to Cart form.'),
      '#default_value' => $attribute_widget_title,
      '#states' => array(
        'visible' => array(
          ':input[name="instance[commerce_cart_settings][attribute_field]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['field']['cardinality']['#description'] .= '<br />' . t('Must be 1 for this field to function as an attribute selection field on Add to Cart forms.');
  }

  // If the current field instance is not locked and is attached to a product
  // line item type...
  if (empty($form['locked']) && $instance['entity_type'] == 'commerce_line_item' &&
    in_array($instance['bundle'], commerce_product_line_item_types())) {
    // Get the current instance's line item form settings for use as default values.
    $commerce_cart_settings = commerce_cart_field_instance_access_settings($instance);

    $form['instance']['commerce_cart_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Add to Cart form settings'),
      '#description' =>t('Fields attached to product line item types can be included in the Add to Cart form to collect additional information from customers in conjunction with their purchase of particular products.'),
      '#weight' => 5,
      '#collapsible' => FALSE,
    );
    $form['instance']['commerce_cart_settings']['field_access'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include this field on Add to Cart forms for line items of this type.'),
      '#default_value' => $commerce_cart_settings['field_access'],
    );
  }
}

/**
 * Implements hook_commerce_order_delete().
 */
function commerce_cart_commerce_order_delete($order) {
  commerce_cart_order_session_delete($order->order_id);
  commerce_cart_order_session_delete($order->order_id, TRUE);
}

/**
 * Implements hook_commerce_product_calculate_sell_price_line_item_alter().
 */
function commerce_cart_commerce_product_calculate_sell_price_line_item_alter($line_item) {
  global $user;

  // Reference the current shopping cart order in the line item if it isn't set.
  // We load the complete order at this time to ensure it primes the order cache
  // and avoid any untraceable recursive loops.
  // @see http://drupal.org/node/1268472
  if (empty($line_item->order_id)) {
    $order = commerce_cart_order_load($user->uid);

    if ($order) {
      $line_item->order_id = $order->order_id;
    }
  }
}

/**
 * Implements hook_views_api().
 */
function commerce_cart_views_api() {
  return array(
    'api' => 3,
    'path' => drupal_get_path('module', 'commerce_cart') . '/includes/views',
  );
}

/**
 * Implements hook_theme().
 */
function commerce_cart_theme() {
  return array(
    'commerce_cart_empty_block' => array(
      'variables' => array(),
    ),
    'commerce_cart_empty_page' => array(
      'variables' => array(),
    ),
    'commerce_cart_block' => array(
      'variables' => array('order' => NULL, 'contents_view' => NULL),
      'path' => drupal_get_path('module', 'commerce_cart') . '/theme',
      'template' => 'commerce-cart-block',
    ),
  );
}

/**
 * Implements hook_user_login().
 *
 * When a user logs into the site, if they have a shopping cart order it should
 * be updated to belong to their user account.
 */
function commerce_cart_user_login(&$edit, $account) {
  // Get the user's anonymous shopping cart order if it exists.
  if ($order = commerce_cart_order_load()) {
    // Convert it to an authenticated cart.
    commerce_cart_order_convert($order, $account);
  }
}

/**
 * Implements hook_user_update().
 *
 * When a user account e-mail address is updated, update any shopping cart
 * orders owned by the user account to use the new e-mail address.
 */
function commerce_cart_user_update(&$edit, $account, $category) {
  // If the e-mail address was changed...
  if (!empty($edit['original']->mail) && $account->mail != $edit['original']->mail) {
    // Load the user's shopping cart orders.
    $query = new EntityFieldQuery();

    $query
      ->entityCondition('entity_type', 'commerce_order', '=')
      ->propertyCondition('uid', $account->uid, '=')
      ->propertyCondition('status', array_keys(commerce_order_statuses(array('cart' => TRUE))), 'IN');

    $result = $query->execute();

    if (!empty($result['commerce_order'])) {
      foreach (commerce_order_load_multiple(array_keys($result['commerce_order'])) as $order) {
        if ($order->mail != $account->mail) {
          $order->mail = $account->mail;
          commerce_order_save($order);
        }
      }
    }
  }
}

/**
 * Implements hook_block_info().
 */
function commerce_cart_block_info() {
  $blocks = array();

  // Define the basic shopping cart block and hide it on the checkout pages.
  $blocks['cart'] = array(
    'info' => t('Shopping cart'),
    'cache' => DRUPAL_NO_CACHE,
    'visibility' => 0,
    'pages' => 'checkout*',
  );

  return $blocks;
}

/**
 * Implements hook_block_view().
 */
function commerce_cart_block_view($delta) {
  global $user;

  // Prepare the display of the default Shopping Cart block.
  if ($delta == 'cart') {
    // Default to an empty cart block message.
    $content = theme('commerce_cart_empty_block');

    // First check to ensure there are products in the shopping cart.
    if ($order = commerce_cart_order_load($user->uid)) {
      $wrapper = entity_metadata_wrapper('commerce_order', $order);

      // If there are one or more products in the cart...
      if (commerce_line_items_quantity($wrapper->commerce_line_items, commerce_product_line_item_types()) > 0) {

        // Build the variables array to send to the cart block template.
        $variables = array(
          'order' => $order,
          'contents_view' => commerce_embed_view('commerce_cart_block', 'default', array($order->order_id), $_GET['q']),
        );

        $content = theme('commerce_cart_block', $variables);
      }
    }

    return array('subject' => t('Shopping cart'), 'content' => $content);
  }
}

 /**
 * Checks if a cart order should be refreshed based on the shopping cart refresh
 * settings on the order settings form.
 *
 * @param $order
 *   The cart order to check.
 *
 * @return
 *   Boolean indicating whether or not the cart order can be refreshed.
 */
function commerce_cart_order_can_refresh($order) {
  global $user;

  // Force the shopping cart refresh on /cart and /checkout/* paths if enabled.
  if (variable_get('commerce_cart_refresh_force', TRUE) &&
    (current_path() == 'cart' || strpos(current_path(), 'checkout/') === 0)) {
    return TRUE;
  }

  // Prevent refresh for orders that don't match the current refresh mode.
  switch (variable_get('commerce_cart_refresh_mode', COMMERCE_CART_REFRESH_OWNER_ONLY)) {
    case COMMERCE_CART_REFRESH_OWNER_ONLY:
      // If the order is anonymous, check the session to see if the order
      // belongs to the current user. Otherwise just check that the order uid
      // matches the current user.
      if ($order->uid == 0 && !commerce_cart_order_session_exists($order->order_id)) {
        return FALSE;
      }
      elseif ($order->uid != $user->uid) {
        return FALSE;
      }
      break;

    case COMMERCE_CART_REFRESH_ACTIVE_CART_ONLY:
      // Check to see if the order ID matches the current user's cart order ID.
      if (commerce_cart_order_id($user->uid) != $order->order_id) {
        return FALSE;
      }
      break;

    case COMMERCE_CART_REFRESH_ALWAYS:
    default:
      // Continue on if shopping cart orders should always refresh.
      break;
  }

  // Check to see if the last cart refresh happened long enough ago.
  $seconds = variable_get('commerce_cart_refresh_frequency', COMMERCE_CART_REFRESH_DEFAULT_FREQUENCY);

  if (!empty($seconds) && !empty($order->data['last_cart_refresh']) &&
    REQUEST_TIME - $order->data['last_cart_refresh'] < $seconds) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Implements hook_commerce_order_load().
 *
 * Because shopping carts are merely a special case of orders, we work through
 * the Order API to ensure that products in shopping carts are kept up to date.
 * Therefore, each time a cart is loaded, we calculate afresh the unit and total
 * prices of product line items and save them if any values have changed.
 */
function commerce_cart_commerce_order_load($orders) {
  $refreshed = &drupal_static(__FUNCTION__, array());

  foreach ($orders as $order) {
    // Refresh only if this order object represents the latest revision of a
    // shopping cart order, it hasn't been refreshed already in this request
    // and it meets the criteria in the shopping cart refresh settings.
    if (!isset($refreshed[$order->order_id]) &&
      commerce_cart_order_is_cart($order) &&
      commerce_order_is_latest_revision($order) &&
      commerce_cart_order_can_refresh($order)) {
      // Update the last cart refresh timestamp and record the order's current
      // changed timestamp to detect if the order is actually updated.
      $order->data['last_cart_refresh'] = REQUEST_TIME;

      $unchanged_data = $order->data;
      $last_changed = $order->changed;

      // Refresh the order and add its ID to the refreshed array.
      $refreshed[$order->order_id] = TRUE;
      commerce_cart_order_refresh($order);

      // If order wasn't updated during the refresh, we need to manually update
      // the last cart refresh timestamp in the database.
      if ($order->changed == $last_changed) {
        db_update('commerce_order')
          ->fields(array('data' => serialize($unchanged_data)))
          ->condition('order_id', $order->order_id)
          ->execute();

        db_update('commerce_order_revision')
          ->fields(array('data' => serialize($unchanged_data)))
          ->condition('order_id', $order->order_id)
          ->condition('revision_id', $order->revision_id)
          ->execute();
      }
    }
  }
}

/**
 * Themes an empty shopping cart block's contents.
 */
function theme_commerce_cart_empty_block() {
  return '<div class="cart-empty-block">' . t('Your shopping cart is empty.') . '</div>';
}

/**
 * Themes an empty shopping cart page.
 */
function theme_commerce_cart_empty_page() {
  return '<div class="cart-empty-page">' . t('Your shopping cart is empty.') . '</div>';
}

/**
 * Loads the shopping cart order for the specified user.
 *
 * @param $uid
 *   The uid of the customer whose cart to load. If left 0, attempts to load
 *   an anonymous order from the session.
 *
 * @return
 *   The fully loaded shopping cart order or FALSE if nonexistent.
 */
function commerce_cart_order_load($uid = 0) {
  // Retrieve the order ID for the specified user's current shopping cart.
  $order_id = commerce_cart_order_id($uid);

  // If a valid cart order ID exists for the user, return it now.
  if (!empty($order_id)) {
    return commerce_order_load($order_id);
  }

  return FALSE;
}

/**
 * Returns the current cart order ID for the given user.
 *
 * @param $uid
 *   The uid of the customer whose cart to load. If left 0, attempts to load
 *   an anonymous order from the session.
 *
 * @return
 *   The requested cart order ID or FALSE if none was found.
 */
function commerce_cart_order_id($uid = 0) {
  // Cart order IDs will be cached keyed by $uid.
  $cart_order_ids = &drupal_static(__FUNCTION__);

  // Cache the user's cart order ID if it hasn't been set already.
  if (isset($cart_order_ids[$uid])) {
    return $cart_order_ids[$uid];
  }

  // First let other modules attempt to provide a valid order ID for the given
  // uid. Instead of invoking hook_commerce_cart_order_id() directly, we invoke
  // it in each module implementing the hook and return the first valid order ID
  // returned (if any).
  foreach (module_implements('commerce_cart_order_id') as $module) {
    $order_id = module_invoke($module, 'commerce_cart_order_id', $uid);

    // If a hook said the user should not have a cart, that overrides any other
    // potentially valid order ID. Return FALSE now.
    if ($order_id === FALSE) {
      $cart_order_ids[$uid] = FALSE;
      return FALSE;
    }

    // Otherwise only return a valid order ID.
    if (!empty($order_id) && is_int($order_id)) {
      $cart_order_ids[$uid] = $order_id;
      return $order_id;
    }
  }

  // Create an array of valid shopping cart order statuses.
  $status_ids = array_keys(commerce_order_statuses(array('cart' => TRUE)));

  // If a customer uid was specified...
  if ($uid) {
    // Look for the user's most recent shopping cart order, although they
    // should never really have more than one.
    $cart_order_ids[$uid] = db_query('SELECT order_id FROM {commerce_order} WHERE uid = :uid AND status IN (:status_ids) ORDER BY order_id DESC', array(':uid' => $uid, ':status_ids' => $status_ids))->fetchField();
  }
  else {
    // Otherwise look for a shopping cart order ID in the session.
    if (commerce_cart_order_session_exists()) {
      // We can't trust a user's IP address to remain the same, especially since
      // it may be derived from a proxy server and not the actual client. As of
      // Commerce 1.4, this query no longer restricts order IDs based on IP
      // address, instead trusting Drupal to prevent session hijacking.
      $cart_order_ids[$uid] = db_query('SELECT order_id FROM {commerce_order} WHERE order_id IN (:order_ids) AND uid = 0 AND status IN (:status_ids) ORDER BY order_id DESC', array(':order_ids' => commerce_cart_order_session_order_ids(), ':status_ids' => $status_ids))->fetchField();
    }
    else {
      $cart_order_ids[$uid] = FALSE;
    }
  }

  return $cart_order_ids[$uid];
}

/**
 * Resets the cached array of shopping cart orders.
 */
function commerce_cart_order_ids_reset() {
  $cart_order_ids = &drupal_static('commerce_cart_order_id');
  $cart_order_ids = NULL;
}

/**
 * Creates a new shopping cart order for the specified user.
 *
 * @param $uid
 *   The uid of the user for whom to create the order. If left 0, the order will
 *   be created for an anonymous user and associated with the current session
 *   if it is anonymous.
 * @param $type
 *   The type of the order; defaults to the standard 'commerce_order' type.
 *
 * @return
 *   The newly created shopping cart order object.
 */
function commerce_cart_order_new($uid = 0, $type = 'commerce_order') {
  global $user;

  // Create the new order with the customer's uid and the cart order status.
  $order = commerce_order_new($uid, 'cart', $type);
  $order->log = t('Created as a shopping cart order.');

  // Save it so it gets an order ID and return the full object.
  commerce_order_save($order);

  // Reset the cart cache
  commerce_cart_order_ids_reset();

  // If the user is not logged in, ensure the order ID is stored in the session.
  if (!$uid && empty($user->uid)) {
    commerce_cart_order_session_save($order->order_id);
  }

  return $order;
}

/**
 * Determines whether or not the given order is a shopping cart order.
 */
function commerce_cart_order_is_cart($order) {
  // If the order is in a shopping cart order status, assume it is a cart.
  $is_cart = in_array($order->status, array_keys(commerce_order_statuses(array('cart' => TRUE))));

  // Allow other modules to make the judgment based on some other criteria.
  foreach (module_implements('commerce_cart_order_is_cart') as $module) {
    $function = $module . '_commerce_cart_order_is_cart';

    // As of Drupal Commerce 1.2, $is_cart should be accepted by reference and
    // manipulated directly, but we still check for a return value to preserve
    // backward compatibility with the hook. In future versions, we will
    // deprecate hook_commerce_cart_order_is_cart() and force modules to update
    // to hook_commerce_cart_order_is_cart_alter().
    if ($function($order, $is_cart) === FALSE) {
      $is_cart = FALSE;
    }
  }

  drupal_alter('commerce_cart_order_is_cart', $is_cart, $order);

  return $is_cart;
}

/**
 * Implements hook_commerce_entity_access_condition_commerce_order_alter().
 *
 * This alter hook allows the Cart module to add conditions to the query used to
 * determine if a user has view access to a given order. The Cart module will
 * always grant users access to view their own carts (independent of any
 * permission settings) and also grants anonymous users access to view their
 * completed orders if they've been given the permission.
 */
function commerce_cart_commerce_entity_access_condition_commerce_order_alter(&$conditions, $context) {
  // Find the user's cart order ID and anonymous user's completed orders.
  $current_order_id = commerce_cart_order_id($context['account']->uid);
  $completed_order_ids = commerce_cart_order_session_order_ids(TRUE);

  // Always give the current user access to their own cart regardless of order
  // view permissions.
  if (!empty($current_order_id)) {
    $conditions->condition($context['base_table'] . '.order_id', $current_order_id);
  }

  // Bail now if the access query is for an authenticated user or if the
  // anonymous user doesn't have any completed orders.
  if ($context['account']->uid || empty($completed_order_ids)) {
    return;
  }

  // If the user has access to view his own orders of any bundle...
  if (user_access('view own ' . $context['entity_type'] . ' entities', $context['account'])) {
    // Add a condition granting the user view access to any completed orders
    // in his session.
    $conditions->condition($context['base_table'] . '.order_id', $completed_order_ids, 'IN');
  }

  // Add additional conditions on a per order bundle basis.
  $entity_info = entity_get_info($context['entity_type']);

  foreach ($entity_info['bundles'] as $bundle_name => $bundle_info) {
    // Otherwise if the user has access to view his own entities of the current
    // bundle, add an AND condition group that grants access if the entity
    // specified by the view query matches the same bundle and belongs to the user.
    if (user_access('view own ' . $context['entity_type'] . ' entities of bundle ' . $bundle_name, $context['account'])) {
      $conditions->condition(db_and()
        ->condition($context['base_table'] . '.' . $entity_info['entity keys']['bundle'], $bundle_name)
        ->condition($context['base_table'] . '.order_id', $completed_order_ids, 'IN')
      );
    }
  }
}

/**
 * Converts an anonymous shopping cart order to an authenticated cart.
 *
 * @param $order
 *   The anonymous order to convert to an authenticated cart.
 * @param $account
 *   The user account the order will belong to.
 *
 * @return
 *   The updated order's wrapper or FALSE if the order was not converted,
 *     meaning it was not an anonymous cart order to begin with.
 */
function commerce_cart_order_convert($order, $account) {
  // Only convert orders that are currently anonmyous orders.
  if ($order->uid == 0) {
    // Update the uid and e-mail address to match the current account since
    // there currently is no way to specify a custom e-mail address per order.
    $order->uid = $account->uid;
    $order->mail = $account->mail;

    // Update the uid of any referenced customer profiles.
    $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

    foreach (field_info_instances('commerce_order', $order->type) as $field_name => $instance) {
      $field_info = field_info_field($field_name);

      if ($field_info['type'] == 'commerce_customer_profile_reference') {
        if ($order_wrapper->{$field_name} instanceof EntityListWrapper) {
          foreach ($order_wrapper->{$field_name} as $delta => $profile_wrapper) {
            if ($profile_wrapper->uid->value() == 0) {
              $profile_wrapper->uid = $account->uid;
              $profile_wrapper->save();
            }
          }
        }
        elseif (!is_null($order_wrapper->{$field_name}->value()) &&
          $order_wrapper->{$field_name}->uid->value() == 0) {
          $order_wrapper->{$field_name}->uid = $account->uid;
          $order_wrapper->{$field_name}->save();
        }
      }
    }

    // Allow other modules to operate on the converted order and then save.
    module_invoke_all('commerce_cart_order_convert', $order_wrapper, $account);
    $order_wrapper->save();

    return $order_wrapper;
  }

  return FALSE;
}

/**
 * Refreshes the contents of a shopping cart by finding the most current prices
 * for any product line items on the order.
 *
 * @param $order
 *   The order object whose line items should be refreshed.
 *
 * @return
 *   The updated order's wrapper.
 */
function commerce_cart_order_refresh($order) {
  $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

  // Allow other modules to act on the order prior to the refresh logic.
  module_invoke_all('commerce_cart_order_pre_refresh', $order);

  // Loop over every line item on the order...
  $line_item_changed = FALSE;

  foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
    // If the current line item actually no longer exists...
    if (!$line_item_wrapper->value()) {
      // Remove the reference from the order and continue to the next value.
      $order_wrapper->commerce_line_items->offsetUnset($delta);
      continue;
    }

    // Knowing it exists, clone the line item now.
    $cloned_line_item = clone($line_item_wrapper->value());

    // If the line item is a product line item...
    if (in_array($cloned_line_item->type, commerce_product_line_item_types())) {
      $product = $line_item_wrapper->commerce_product->value();

      // If this price has already been calculated, reset it to its original
      // value so it can be recalculated afresh in the current context.
      if (isset($product->commerce_price[LANGUAGE_NONE][0]['original'])) {
        $original = $product->commerce_price[LANGUAGE_NONE][0]['original'];
        foreach ($product->commerce_price as $langcode => $value) {
          $product->commerce_price[$langcode] = array(0 => $original);
        }
      }

      // Repopulate the line item array with the default values for the product
      // as though it had not been added to the cart yet, but preserve the
      // current quantity and display URI information.
      commerce_product_line_item_populate($cloned_line_item, $product);

      // Process the unit price through Rules so it reflects the user's actual
      // current purchase price.
      rules_invoke_event('commerce_product_calculate_sell_price', $cloned_line_item);
    }

    // Allow other modules to alter line items on a shopping cart refresh.
    module_invoke_all('commerce_cart_line_item_refresh', $cloned_line_item, $order_wrapper);

    // Delete this line item if it no longer has a valid price.
    $current_line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $cloned_line_item);

    if (is_null($current_line_item_wrapper->commerce_unit_price->value()) ||
      is_null($current_line_item_wrapper->commerce_unit_price->amount->value()) ||
      is_null($current_line_item_wrapper->commerce_unit_price->currency_code->value())) {
      commerce_cart_order_product_line_item_delete($order, $cloned_line_item->line_item_id, TRUE);
    }
    else {
      // Compare the refreshed unit price to the original unit price looking for
      // differences in the amount, currency code, or price components.
      $data = $line_item_wrapper->commerce_unit_price->data->value() + array('components' => array());
      $current_data = (array) $current_line_item_wrapper->commerce_unit_price->data->value() + array('components' => array());

      if ($line_item_wrapper->commerce_unit_price->amount->value() != $current_line_item_wrapper->commerce_unit_price->amount->value() ||
        $line_item_wrapper->commerce_unit_price->currency_code->value() != $current_line_item_wrapper->commerce_unit_price->currency_code->value() ||
        $data['components'] != $current_data['components']) {
        // Adjust the unit price accordingly if necessary.
        $line_item_wrapper->commerce_unit_price->amount = $current_line_item_wrapper->commerce_unit_price->amount->value();
        $line_item_wrapper->commerce_unit_price->currency_code = $current_line_item_wrapper->commerce_unit_price->currency_code->value();

        // Only migrate the price components in the data to preserve other data.
        $data['components'] = $current_data['components'];
        $line_item_wrapper->commerce_unit_price->data = $data;

        // Save the updated line item.
        commerce_line_item_save($line_item_wrapper->value());

        $line_item_changed = TRUE;
      }
    }
  }

  // Store a copy of the original order to see if it changes later.
  $original_order = clone($order_wrapper->value());

  // Allow other modules to alter the entire order on a shopping cart refresh.
  module_invoke_all('commerce_cart_order_refresh', $order_wrapper);

  // Save the order once here if it has changed or if a line item was changed.
  if ($order_wrapper->value() != $original_order || $line_item_changed) {
    commerce_order_save($order_wrapper->value());
  }

  return $order_wrapper;
}

/**
 * Entity metadata callback: returns the current user's shopping cart order.
 *
 * @see commerce_cart_entity_property_info_alter()
 */
function commerce_cart_get_properties($data = FALSE, array $options, $name) {
  global $user;

  switch ($name) {
    case 'current_cart_order':
      if ($order = commerce_cart_order_load($user->uid)) {
        return $order;
      }
      else {
        return commerce_order_new($user->uid, 'cart');
      }
  }
}

/**
 * Returns an array of cart order IDs stored in the session.
 *
 * @param $completed
 *   Boolean indicating whether or not the operation should retrieve the
 *   completed orders array instead of the active cart orders array.
 *
 * @return
 *   An array of applicable cart order IDs or an empty array if none exist.
 */
function commerce_cart_order_session_order_ids($completed = FALSE) {
  $key = $completed ? 'commerce_cart_completed_orders' : 'commerce_cart_orders';
  return empty($_SESSION[$key]) ? array() : $_SESSION[$key];
}

/**
 * Saves an order ID to the appropriate cart orders session variable.
 *
 * @param $order_id
 *   The order ID to save to the array.
 * @param $completed
 *   Boolean indicating whether or not the operation should save to the
 *     completed orders array instead of the active cart orders array.
 */
function commerce_cart_order_session_save($order_id, $completed = FALSE) {
  $key = $completed ? 'commerce_cart_completed_orders' : 'commerce_cart_orders';

  if (empty($_SESSION[$key])) {
    $_SESSION[$key] = array($order_id);
  }
  elseif (!in_array($order_id, $_SESSION[$key])) {
    $_SESSION[$key][] = $order_id;
  }
}

/**
 * Checks to see if any order ID or a specific order ID exists in the session.
 *
 * @param $order_id
 *   Optionally specify an order ID to look for in the commerce_cart_orders
 *     session variable; defaults to NULL.
 * @param $completed
 *   Boolean indicating whether or not the operation should look in the
 *     completed orders array instead of the active cart orders array.
 *
 * @return
 *   Boolean indicating whether or not any cart order ID exists in the session
 *     or if the specified order ID exists in the session.
 */
function commerce_cart_order_session_exists($order_id = NULL, $completed = FALSE) {
  $key = $completed ? 'commerce_cart_completed_orders' : 'commerce_cart_orders';

  // If an order was specified, look for it in the array.
  if (!empty($order_id)) {
    return !empty($_SESSION[$key]) && in_array($order_id, $_SESSION[$key]);
  }
  else {
    // Otherwise look for any value.
    return !empty($_SESSION[$key]);
  }
}

/**
 * Deletes all order IDs or a specific order ID from the cart orders session
 *   variable.
 *
 * @param $order_id
 *   The order ID to remove from the array or NULL to delete the variable.
 * @param $completed
 *   Boolean indicating whether or not the operation should delete from the
 *     completed orders array instead of the active cart orders array.
 */
function commerce_cart_order_session_delete($order_id = NULL, $completed = FALSE) {
  $key = $completed ? 'commerce_cart_completed_orders' : 'commerce_cart_orders';

  if (!empty($_SESSION[$key])) {
    if (!empty($order_id)) {
      $_SESSION[$key] = array_diff($_SESSION[$key], array($order_id));
    }
    else {
      unset($_SESSION[$key]);
    }
  }
}

/**
 * Adds the specified product to a customer's shopping cart.
 *
 * @param $uid
 *   The uid of the user whose cart you are adding the product to.
 * @param $line_item
 *   An unsaved product line item to be added to the cart with the following data
 *   on the line item being used to determine how to add the product to the cart:
 *   - $line_item->commerce_product: reference to the product to add to the cart.
 *   - $line_item->quantity: quantity of this product to add to the cart.
 *   - $line_item->data: data array that is saved with the line item if the line
 *     item is added to the cart as a new item; merged into an existing line
 *     item if combination is possible.
 *   - $line_item->order_id: this property does not need to be set when calling
 *     this function, as it will be set to the specified user's current cart
 *     order ID.
 *   Additional field data on the line item may be considered when determining
 *   whether or not line items can be combined in the cart. This includes the
 *   line item type, referenced product, and any line item fields that have been
 *   exposed on the Add to Cart form.
 * @param $combine
 *   Boolean indicating whether or not to combine like products on the same line
 *   item, incrementing an existing line item's quantity instead of adding a
 *   new line item to the cart order. When the incoming line item is combined
 *   into an existing line item, field data on the existing line item will be
 *   left unchanged. Only the quantity will be incremented and the data array
 *   will be updated by merging the data from the existing line item onto the
 *   data from the incoming line item, giving precedence to the most recent data.
 *
 * @return
 *   The new or updated line item object or FALSE on failure.
 */
function commerce_cart_product_add($uid, $line_item, $combine = TRUE) {
  // Do not add the line item if it doesn't have a unit price.
  $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

  if (is_null($line_item_wrapper->commerce_unit_price->value())) {
    return FALSE;
  }

  // First attempt to load the customer's shopping cart order.
  $order = commerce_cart_order_load($uid);

  // If no order existed, create one now.
  if (empty($order)) {
    $order = commerce_cart_order_new($uid);
    $order->data['last_cart_refresh'] = REQUEST_TIME;
  }

  // Set the incoming line item's order_id.
  $line_item->order_id = $order->order_id;

  // Wrap the order for easy access to field data.
  $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

  // Extract the product and quantity we're adding from the incoming line item.
  $product = $line_item_wrapper->commerce_product->value();
  $quantity = $line_item->quantity;

  // Invoke the product prepare event with the shopping cart order.
  rules_invoke_all('commerce_cart_product_prepare', $order, $product, $quantity);

  // Determine if the product already exists on the order and increment its
  // quantity instead of adding a new line if it does.
  $matching_line_item = NULL;

  // If we are supposed to look for a line item to combine into...
  if ($combine) {
    // Generate an array of properties and fields to compare.
    $comparison_properties = array('type', 'commerce_product');

    // Add any field that was exposed on the Add to Cart form to the array.
    // TODO: Bypass combination when an exposed field is no longer available but
    // the same base product is added to the cart.
    foreach (field_info_instances('commerce_line_item', $line_item->type) as $info) {
      if (!empty($info['commerce_cart_settings']['field_access'])) {
        $comparison_properties[] = $info['field_name'];
      }
    }

    // Allow other modules to specify what properties should be compared when
    // determining whether or not to combine line items.
    
		//EMPIRE: PATCHED TO MAKE WORKABLE WITH PHP7
		//drupal_alter('commerce_cart_product_comparison_properties', $comparison_properties, clone($line_item));
		$line_item_clone = clone($line_item);
		drupal_alter('commerce_cart_product_comparison_properties', $comparison_properties, $line_item_clone);

    // Loop over each line item on the order.
    foreach ($order_wrapper->commerce_line_items as $delta => $matching_line_item_wrapper) {
      // Examine each of the comparison properties on the line item.
      foreach ($comparison_properties as $property) {
        // If the property is not present on either line item, bypass it.
        if (!isset($matching_line_item_wrapper->value()->{$property}) && !isset($line_item_wrapper->value()->{$property})) {
          continue;
        }

        // If any property does not match the same property on the incoming line
        // item or exists on one line item but not the other...
        if ((!isset($matching_line_item_wrapper->value()->{$property}) && isset($line_item_wrapper->value()->{$property})) ||
          (isset($matching_line_item_wrapper->value()->{$property}) && !isset($line_item_wrapper->value()->{$property})) ||
          $matching_line_item_wrapper->{$property}->raw() != $line_item_wrapper->{$property}->raw()) {
          // Continue the loop with the next line item.
          continue 2;
        }
      }

      // If every comparison line item matched, combine into this line item.
      $matching_line_item = $matching_line_item_wrapper->value();
      break;
    }
  }

  // If no matching line item was found...
  if (empty($matching_line_item)) {
    // Save the incoming line item now so we get its ID.
    commerce_line_item_save($line_item);

    // Add it to the order's line item reference value.
    $order_wrapper->commerce_line_items[] = $line_item;
  }
  else {
    // Increment the quantity of the matching line item, update the data array,
    // and save it.
    $matching_line_item->quantity += $quantity;
    $matching_line_item->data = array_merge($line_item->data, $matching_line_item->data);

    commerce_line_item_save($matching_line_item);

    // Update the line item variable for use in the invocation and return value.
    $line_item = $matching_line_item;
  }

  // Save the updated order.
  commerce_order_save($order);

  // Invoke the product add event with the newly saved or updated line item.
  rules_invoke_all('commerce_cart_product_add', $order, $product, $quantity, $line_item);

  // Return the line item.
  return $line_item;
}

/**
 * Adds the specified product to a customer's shopping cart by product ID.
 *
 * This function is merely a helper function that builds a line item for the
 * specified product ID and adds it to a shopping cart. It does not offer the
 * full support of product line item fields that commerce_cart_product_add()
 * does, so you may still need to use the full function, especially if you need
 * to specify display_path field values or interact with custom line item fields.
 *
 * @param $product_id
 *   ID of the product to add to the cart.
 * @param $quantity
 *   Quantity of the specified product to add to the cart; defaults to 1.
 * @param $combine
 *   Boolean indicating whether or not to combine like products on the same line
 *   item, incrementing an existing line item's quantity instead of adding a
 *   new line item to the cart order.
 * @param $uid
 *   User ID of the shopping cart owner the whose cart the product should be
 *   added to; defaults to the current user.
 *
 * @return
 *   A new or updated line item object representing the product in the cart or
 *   FALSE on failure.
 *
 * @see commerce_cart_product_add()
 */
function commerce_cart_product_add_by_id($product_id, $quantity = 1, $combine = TRUE, $uid = NULL) {
  global $user;

  // If the specified product exists...
  if ($product = commerce_product_load($product_id)) {
    // Create a new product line item for it.
    $line_item = commerce_product_line_item_new($product, $quantity);

    // Default to the current user if a uid was not passed in.
    if ($uid === NULL) {
      $uid = $user->uid;
    }

    return commerce_cart_product_add($uid, $line_item, $combine);
  }

  return FALSE;
}

/**
 * Deletes a product line item from a shopping cart order.
 *
 * @param $order
 *   The shopping cart order to delete from.
 * @param $line_item_id
 *   The ID of the product line item to delete from the order.
 * @param $skip_save
 *   TRUE to skip saving the order after deleting the line item; used when the
 *     order would otherwise be saved or to delete multiple product line items
 *     from the order and then save.
 *
 * @return
 *   The order with the matching product line item deleted from the line item
 *     reference field.
 */
function commerce_cart_order_product_line_item_delete($order, $line_item_id, $skip_save = FALSE) {
  $line_item = commerce_line_item_load($line_item_id);

  // Check to ensure the line item exists and is a product line item.
  if (!$line_item || !in_array($line_item->type, commerce_product_line_item_types())) {
    return $order;
  }

  // Remove the line item from the line item reference field.
  commerce_entity_reference_delete($order, 'commerce_line_items', 'line_item_id', $line_item_id);

  // Wrap the line item to be deleted and extract the product from it.
  $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
  $product = $wrapper->commerce_product->value();

  // Invoke the product removal event with the line item about to be deleted.
  rules_invoke_all('commerce_cart_product_remove', $order, $product, $line_item->quantity, $line_item);

  // Delete the actual line item.
  commerce_line_item_delete($line_item->line_item_id);

  if (!$skip_save) {
    commerce_order_save($order);
  }

  return $order;
}

/**
 * Deletes every product line item from a shopping cart order.
 *
 * @param $order
 *   The shopping cart order to empty.
 *
 * @return
 *   The order with the product line items all removed.
 */
function commerce_cart_order_empty($order) {
  $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

  // Build an array of product line item IDs.
  $line_item_ids = array();

  foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
    $line_item_ids[] = $line_item_wrapper->line_item_id->value();
  }

  // Delete each line item one by one from the order. This is done this way
  // instead of unsetting each as we find it to ensure that changing delta
  // values don't prevent an item from being removed from the order.
  foreach ($line_item_ids as $line_item_id) {
    $order = commerce_cart_order_product_line_item_delete($order, $line_item_id, TRUE);
  }

  // Allow other modules to update the order on empty prior to save.
  module_invoke_all('commerce_cart_order_empty', $order);

  // Save and return the order.
  commerce_order_save($order);

  return $order;
}

/**
 * Determines whether or not the given field is eligible to function as a
 * product attribute field on the Add to Cart form.
 *
 * @param $field
 *   The info array of the field whose eligibility you want to determine.
 *
 * @return
 *   TRUE or FALSE indicating the field's eligibility.
 */
function commerce_cart_field_attribute_eligible($field) {
  // Returns TRUE if the field is single value (i.e. has a cardinality of 1) and
  // is defined by a module implementing hook_options_list() to provide an array
  // of allowed values structured as human-readable option names keyed by value.
  return $field['cardinality'] == 1 && function_exists($field['module'] . '_options_list');
}

/**
 * Returns an array of attribute settings for a field instance.
 *
 * Fields attached to product types may be used as product attribute fields with
 * selection widgets on Add to Cart forms. This function returns the default
 * values for a given field instance.
 *
 * @param $instance
 *   The info array of the field instance whose attribute settings should be
 *   retrieved.
 *
 * @return
 *   An array of attribute settings including:
 *   - attribute_field: boolean indicating whether or not the instance should
 *     be used as a product attribute field on the Add to Cart form; defaults
 *     to FALSE
 *   - attribute_widget: string indicating the type of form element to use on
 *     the Add to Cart form for customers to select the attribute option;
 *     defaults to 'select', may also be 'radios'
 *   - attribute_widget_title: string used as the title of the attribute form
 *     element on the Add to Cart form.
 */
function commerce_cart_field_instance_attribute_settings($instance) {
  if (empty($instance['commerce_cart_settings']) || !is_array($instance['commerce_cart_settings'])) {
    $commerce_cart_settings = array();
  }
  else {
    $commerce_cart_settings = $instance['commerce_cart_settings'];
  }

  // Supply default values for the cart settings pertaining here to
  // product attribute fields.
  $commerce_cart_settings += array(
    'attribute_field' => FALSE,
    'attribute_widget' => 'select',
    'attribute_widget_title' => '',
  );

  return $commerce_cart_settings;
}

/**
 * Determines whether or not a field instance is fucntioning as a product
 * attribute field.
 *
 * @param $instance
 *   The instance info array for the field instance.
 *
 * @return
 *   Boolean indicating whether or not the field instance is an attribute field.
 */
function commerce_cart_field_instance_is_attribute($instance) {
  $commerce_cart_settings = commerce_cart_field_instance_attribute_settings($instance);
  return !empty($commerce_cart_settings['attribute_field']);
}

/**
 * Returns an array of cart form field access settings for a field instance.
 *
 * Fields attached to line item types can be included on the Add to Cart form so
 * customers can supply additional information for the line item when it is
 * added to the cart. Certain fields will not be exposed based on line item
 * field access integration, such as the total price field which is always
 * computationally generated on line item save.
 *
 * @param $instance
 *   The info array of the field instance whose field access settings should be
 *   retrieved.
 *
 * @return
 *   An array of field access settings including:
 *   - field_access: boolean indicating whether or not this field instance
 *     should appear on the Add to Cart form.
 */
function commerce_cart_field_instance_access_settings($instance) {
  if (empty($instance['commerce_cart_settings']) || !is_array($instance['commerce_cart_settings'])) {
    $commerce_cart_settings = array();
  }
  else {
    $commerce_cart_settings = $instance['commerce_cart_settings'];
  }

  // Supply default values for the cart settings pertaining here to field access
  // on the Add to Cart form.
  $commerce_cart_settings += array(
    'field_access' => FALSE,
  );

  return $commerce_cart_settings;
}

/**
 * Returns the title of an attribute widget for the Add to Cart form.
 *
 * @param $instance
 *   The attribute field instance info array.
 *
 * @return
 *   A sanitized string to use as the attribute widget title.
 */
function commerce_cart_attribute_widget_title($instance) {
  // Translate the entire field instance and find the default title.
  $translated_instance = commerce_i18n_object('field_instance', $instance);
  $title = $translated_instance['label'];

  // Use the customized attribute widget title if it exists.
  $commerce_cart_settings = commerce_cart_field_instance_attribute_settings($instance);

  if (!empty($commerce_cart_settings['attribute_widget_title'])) {
    $title = $commerce_cart_settings['attribute_widget_title'];

    // Use the translated customized title if it exists.
    if (!empty($translated_instance['attribute_widget_title'])) {
      $title = $translated_instance['attribute_widget_title'];
    }
  }

  return check_plain($title);
}

/**
 * Builds an appropriate cart form ID based on the products on the form.
 *
 * @see commerce_cart_forms().
 */
function commerce_cart_add_to_cart_form_id($product_ids) {
  // Make sure the length of the form id is limited.
  $data = implode('_', $product_ids);

  if (strlen($data) > 50) {
    $data = drupal_hash_base64($data);
  }

  return 'commerce_cart_add_to_cart_form_' . $data;
}

/**
 * Implements hook_forms().
 *
 * To provide distinct form IDs for add to cart forms, the product IDs
 * referenced by the form are appended to the base ID,
 * commerce_cart_add_to_cart_form. When such a form is built or submitted, this
 * function will return the proper callback function to use for the given form.
 */
function commerce_cart_forms($form_id, $args) {
  $forms = array();

  // Construct a valid cart form ID from the arguments.
  if (strpos($form_id, 'commerce_cart_add_to_cart_form_') === 0) {
    $forms[$form_id] = array(
      'callback' => 'commerce_cart_add_to_cart_form',
    );
  }

  return $forms;
}

/**
 * Builds an Add to Cart form for a set of products.
 *
 * @param $line_item
 *   A fully formed product line item whose data will be used in the following
 *   ways by the form:
 *   - $line_item->data['context']['product_ids']: an array of product IDs to
 *     include on the form or the string 'entity' if the context array includes
 *     an entity array with information for accessing the product IDs from an
 *     entity's product reference field.
 *   - $line_item->data['context']['entity']: if the product_ids value is the
 *     string 'entity', an associative array with the keys 'entity_type',
 *     'entity_id', and 'product_reference_field_name' that points to the
 *     location of the product IDs used to build the form.
 *   - $line_item->data['context']['add_to_cart_combine']: a boolean indicating
 *     whether or not to attempt to combine the product added to the cart with
 *     existing line items of matching fields.
 *   - $line_item->data['context']['show_single_product_attributes']: a boolean
 *     indicating whether or not product attribute fields with single options
 *     should be shown on the Add to Cart form.
 *   - $line_item->quantity: the default value for the quantity widget if
 *     included (determined by the $show_quantity parameter).
 *   - $line_item->commerce_product: the value of this field will be used as the
 *     default product ID when the form is built for multiple products.
 *   The line item's data array will be used on submit to set the data array of
 *   the product line item created by the form.
 * @param $show_quantity
 *   Boolean indicating whether or not to show the quantity widget; defaults to
 *   FALSE resulting in a hidden field holding the quantity.
 * @param $context
 *   Information on the context of the form's placement, allowing it to update
 *   product fields on the page based on the currently selected default product.
 *   Should be an associative array containing the following keys:
 *   - class_prefix: a prefix used to target HTML containers for replacement
 *     with rendered fields as the default product is updated. For example,
 *     nodes display product fields in their context wrapped in spans with the
 *     class node-#-product-field_name.  The class_prefix for the add to cart
 *     form displayed on a node would be node-# with this form's AJAX refresh
 *     adding the suffix -product-field_name.
 *   - view_mode: a product view mode that tells the AJAX refresh how to render
 *     the replacement fields.
 *   If no context is specified, AJAX replacement of rendered fields will not
 *   happen. This parameter only affects forms containing multiple products.
 *
 * @return
 *   The form array.
 */
function commerce_cart_add_to_cart_form($form, &$form_state, $line_item, $show_quantity = FALSE, $context = array()) {
  global $user;

  // Store the context in the form state for use during AJAX refreshes.
  $form_state['context'] = $context;

  // Store the line item passed to the form builder for reference on submit.
  $form_state['line_item'] = $line_item;
  $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
  $default_quantity = $line_item->quantity;

  // Retrieve the array of product IDs from the line item's context data array.
  $product_ids = commerce_cart_add_to_cart_form_product_ids($line_item);

  // If we don't have a list of products to load, just bail out early.
  // There is nothing we can or have to do in that case.
  if (empty($product_ids)) {
    return array();
  }

  // Add a generic class ID.
  $form['#attributes']['class'][] = drupal_html_class('commerce-add-to-cart');

  // Store the form ID as a class of the form to avoid the incrementing form ID
  // from causing the AJAX refresh not to work.
  $form['#attributes']['class'][] = drupal_html_class(commerce_cart_add_to_cart_form_id($product_ids));

  // Store the customer uid in the form so other modules can override with a
  // selection widget if necessary.
  $form['uid'] = array(
    '#type' => 'value',
    '#value' => $user->uid,
  );

  // Load all the active products intended for sale on this form.
  $products = commerce_product_load_multiple($product_ids, array('status' => 1));

  // If no products were returned...
  if (count($products) == 0) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Product not available'),
      '#weight' => 15,
      // Do not set #disabled in order not to prevent submission.
      '#attributes' => array('disabled' => 'disabled'),
      '#validate' => array('commerce_cart_add_to_cart_form_disabled_validate'),
    );
  }
  else {
    // If the form is for a single product and displaying attributes on a single
    // product Add to Cart form is disabled in the form context, store the
    // product_id in a hidden form field for use by the submit handler.
    if (count($products) == 1 && empty($line_item->data['context']['show_single_product_attributes'])) {
      $form_state['default_product'] = reset($products);

      $form['product_id'] = array(
        '#type' => 'hidden',
        '#value' => key($products),
      );
    }
    else {
      // However, if more than one products are represented on it, attempt to
      // use smart select boxes for the product selection. If the products are
      // all of the same type and there are qualifying fields on that product
      // type, display their options for customer selection.
      $qualifying_fields = array();
      $same_type = TRUE;
      $type = '';

      // Find the default product so we know how to set default options on the
      // various Add to Cart form widgets and an array of any matching product
      // based on attribute selections so we can add a selection widget.
      $matching_products = array();
      $default_product = NULL;
      $attribute_names = array();
      $unchanged_attributes = array();

      foreach ($products as $product_id => $product) {
        $product_wrapper = entity_metadata_wrapper('commerce_product', $product);

        // Store the first product type.
        if (empty($type)) {
          $type = $product->type;
        }

        // If the current product type is different from the first, we are not
        // dealing with a set of same typed products.
        if ($product->type != $type) {
          $same_type = FALSE;
        }

        // If the form state contains a set of attribute data, use it to try
        // and determine the default product.
        $changed_attribute = NULL;

        if (!empty($form_state['values']['attributes'])) {
          $match = TRUE;

          // Set an array of checked attributes for later comparison against the
          // default matching product.
          if (empty($attribute_names)) {
            $attribute_names = (array) array_diff_key($form_state['values']['attributes'], array('product_select' => ''));
            $unchanged_attributes = $form_state['values']['unchanged_attributes'];
          }

          foreach ($attribute_names as $key => $value) {
            // If this is the attribute widget that was changed...
            if ($value != $unchanged_attributes[$key]) {
              // Store the field name.
              $changed_attribute = $key;

              // Clear the input for the "Select a product" widget now if it
              // exists on the form since we know an attribute was changed.
              unset($form_state['input']['attributes']['product_select']);
            }

            // If a field name has been stored and we've moved past it to
            // compare the next attribute field...
            if (!empty($changed_attribute) && $changed_attribute != $key) {
              // Wipe subsequent values from the form state so the attribute
              // widgets can use the default values from the new default product.
              unset($form_state['input']['attributes'][$key]);

              // Don't accept this as a matching product.
              continue;
            }

            if ($product_wrapper->{$key}->raw() != $value) {
              $match = FALSE;
            }
          }

          // If the changed field name has already been stored, only accept the
          // first matching product by ignoring the rest that would match. An
          // exception is granted for additional matching products that share
          // the exact same attribute values as the first.
          if ($match && !empty($changed_attribute) && !empty($matching_products)) {
            reset($matching_products);
            $matching_product = $matching_products[key($matching_products)];
            $matching_product_wrapper = entity_metadata_wrapper('commerce_product', $matching_product);

            foreach ($attribute_names as $key => $value) {
              if ($product_wrapper->{$key}->raw() != $matching_product_wrapper->{$key}->raw()) {
                $match = FALSE;
              }
            }
          }

          if ($match) {
            $matching_products[$product_id] = $product;
          }
        }
      }

      // Set the default product now if it isn't already set.
      if (empty($matching_products)) {
        // If a product ID value was passed in, use that product if it exists.
        if (!empty($form_state['values']['product_id']) &&
          !empty($products[$form_state['values']['product_id']])) {
          $default_product = $products[$form_state['values']['product_id']];
        }
        elseif (empty($form_state['values']) &&
          !empty($line_item_wrapper->commerce_product) &&
          !empty($products[$line_item_wrapper->commerce_product->raw()])) {
          // If this is the first time the form is built, attempt to use the
          // product specified by the line item.
          $default_product = $products[$line_item_wrapper->commerce_product->raw()];
        }
        else {
          reset($products);
          $default_product = $products[key($products)];
        }
      }
      else {
        // If the product selector has a value, use that.
        if (!empty($form_state['values']['attributes']['product_select']) &&
            !empty($products[$form_state['values']['attributes']['product_select']]) &&
            in_array($products[$form_state['values']['attributes']['product_select']], $matching_products)) {
          $default_product = $products[$form_state['values']['attributes']['product_select']];
        }
        else {
          reset($matching_products);
          $default_product = $matching_products[key($matching_products)];
        }
      }

      // Wrap the default product for later use.
      $default_product_wrapper = entity_metadata_wrapper('commerce_product', $default_product);

      $form_state['default_product'] = $default_product;

      // If all the products are of the same type...
      if ($same_type) {
        // Loop through all the field instances on that product type.
        foreach (field_info_instances('commerce_product', $type) as $field_name => $instance) {
          // A field qualifies if it is single value, required and uses a widget
          // with a definite set of options. For the sake of simplicity, this is
          // currently restricted to fields defined by the options module.
          $field = field_info_field($field_name);

          // If the instance is of a field type that is eligible to function as
          // a product attribute field and if its attribute field settings
          // specify that this functionality is enabled...
          if (commerce_cart_field_attribute_eligible($field) && commerce_cart_field_instance_is_attribute($instance)) {
            // Get the options properties from the options module for the
            // attribute widget type selected for the field, defaulting to the
            // select list options properties.
            $commerce_cart_settings = commerce_cart_field_instance_attribute_settings($instance);

            switch ($commerce_cart_settings['attribute_widget']) {
              case 'checkbox':
                $widget_type = 'onoff';
                break;
              case 'radios':
                $widget_type = 'buttons';
                break;
              default:
                $widget_type = 'select';
            }

            $properties = _options_properties($widget_type, FALSE, TRUE, TRUE);

            // Try to fetch localized names.
            $allowed_values = NULL;

            // Prepare translated options if using the i18n_field module.
            if (module_exists('i18n_field')) {
              if (($translate = i18n_field_type_info($field['type'], 'translate_options'))) {
                $allowed_values = $translate($field);
                _options_prepare_options($allowed_values, $properties);
              }
            }

            // Otherwise just use the base language values.
            if (empty($allowed_values)) {
              $allowed_values = _options_get_options($field, $instance, $properties, 'commerce_product', $default_product);
            }

            // Only consider this field a qualifying attribute field if we could
            // derive a set of options for it.
            if (!empty($allowed_values)) {
              $qualifying_fields[$field_name] = array(
                'field' => $field,
                'instance' => $instance,
                'commerce_cart_settings' => $commerce_cart_settings,
                'options' => $allowed_values,
                'weight' => $instance['widget']['weight'],
                'required' => $instance['required'],
              );
            }
          }
        }
      }

      // Otherwise for products of varying types, display a simple select list
      // by product title.
      if (!empty($qualifying_fields)) {
        $used_options = array();
        $field_has_options = array();

        // Sort the fields by weight.
        uasort($qualifying_fields, 'drupal_sort_weight');

        foreach ($qualifying_fields as $field_name => $data) {
          // Build an options array of widget options used by referenced products.
          foreach ($products as $product_id => $product) {
            $product_wrapper = entity_metadata_wrapper('commerce_product', $product);

            // Only add options to the present array that appear on products that
            // match the default value of the previously added attribute widgets.
            foreach ($used_options as $used_field_name => $unused) {
              // Don't apply this check for the current field being evaluated.
              if ($used_field_name == $field_name) {
                continue;
              }

              if (isset($form['attributes'][$used_field_name]['#default_value'])) {
                if ($product_wrapper->{$used_field_name}->raw() != $form['attributes'][$used_field_name]['#default_value']) {
                  continue 2;
                }
              }
            }

            // With our hard dependency on widgets provided by the Options
            // module, we can make assumptions about where the data is stored.
            if ($product_wrapper->{$field_name}->raw() != NULL) {
              $field_has_options[$field_name] = TRUE;
            }
            $used_options[$field_name][] = $product_wrapper->{$field_name}->raw();
          }

          // If for some reason no options for this field are used, remove it
          // from the qualifying fields array.
          if (empty($field_has_options[$field_name]) || empty($used_options[$field_name])) {
            unset($qualifying_fields[$field_name]);
          }
          else {
            $form['attributes'][$field_name] = array(
              '#type' => $data['commerce_cart_settings']['attribute_widget'],
              '#title' => commerce_cart_attribute_widget_title($data['instance']),
              '#options' => array_intersect_key($data['options'], drupal_map_assoc($used_options[$field_name])),
              '#default_value' => $default_product_wrapper->{$field_name}->raw(),
              '#weight' => $data['instance']['widget']['weight'],
              '#ajax' => array(
                'callback' => 'commerce_cart_add_to_cart_form_attributes_refresh',
              ),
            );

            // Add the empty value if the field is not required and products on
            // the form include the empty value.
            if (!$data['required'] && in_array('', $used_options[$field_name])) {
              $form['attributes'][$field_name]['#empty_value'] = '';
            }

            $form['unchanged_attributes'][$field_name] = array(
              '#type' => 'value',
              '#value' => $default_product_wrapper->{$field_name}->raw(),
            );
          }
        }

        if (!empty($form['attributes'])) {
          $form['attributes'] += array(
            '#tree' => 'TRUE',
            '#prefix' => '<div class="attribute-widgets">',
            '#suffix' => '</div>',
            '#weight' => 0,
          );
          $form['unchanged_attributes'] += array(
            '#tree' => 'TRUE',
          );

          // If the matching products array is empty, it means this is the first
          // time the form is being built. We should populate it now with
          // products that match the default attribute options.
          if (empty($matching_products)) {
            foreach ($products as $product_id => $product) {
              $product_wrapper = entity_metadata_wrapper('commerce_product', $product);
              $match = TRUE;

              foreach (element_children($form['attributes']) as $field_name) {
                if ($product_wrapper->{$field_name}->raw() != $form['attributes'][$field_name]['#default_value']) {
                  $match = FALSE;
                }
              }

              if ($match) {
                $matching_products[$product_id] = $product;
              }
            }
          }

          // If there were more than one matching products for the current
          // attribute selection, add a product selection widget.
          if (count($matching_products) > 1) {
            $options = array();

            foreach ($matching_products as $product_id => $product) {
              $options[$product_id] = $product->title;
            }

            // Note that this element by default is a select list, so its
            // #options are not sanitized here. Sanitization will occur in a
            // check_plain() in the function form_select_options(). If you alter
            // this element to another #type, such as 'radios', you are also
            // responsible for looping over its #options array and sanitizing
            // the values.
            $form['attributes']['product_select'] = array(
              '#type' => 'select',
              '#title' => t('Select a product'),
              '#options' => $options,
              '#default_value' => $default_product->product_id,
              '#weight' => 40,
              '#ajax' => array(
                'callback' => 'commerce_cart_add_to_cart_form_attributes_refresh',
              ),
            );
          }

          $form['product_id'] = array(
            '#type' => 'hidden',
            '#value' => $default_product->product_id,
          );
        }
      }

      // If the products referenced were of different types or did not posess
      // any qualifying attribute fields...
      if (!$same_type || empty($qualifying_fields)) {
        // For a single product form, just add the hidden product_id field.
        if (count($products) == 1) {
          $form['product_id'] = array(
            '#type' => 'hidden',
            '#value' => $default_product->product_id,
          );
        }
        else {
          // Otherwise add a product selection widget.
          $options = array();

          foreach ($products as $product_id => $product) {
            $options[$product_id] = $product->title;
          }

          // Note that this element by default is a select list, so its #options
          // are not sanitized here. Sanitization will occur in a check_plain() in
          // the function form_select_options(). If you alter this element to
          // another #type, such as 'radios', you are also responsible for looping
          // over its #options array and sanitizing the values.
          $form['product_id'] = array(
            '#type' => 'select',
            '#options' => $options,
            '#default_value' => $default_product->product_id,
            '#weight' => 0,
            '#ajax' => array(
              'callback' => 'commerce_cart_add_to_cart_form_attributes_refresh',
            ),
          );
        }
      }
    }

    // Render the quantity field as either a textfield if shown or a hidden
    // field if not.
    if ($show_quantity) {
      $form['quantity'] = array(
        '#type' => 'textfield',
        '#title' => t('Quantity'),
        '#default_value' => $default_quantity,
        '#datatype' => 'integer',
        '#size' => 5,
        '#weight' => 45,
      );
    }
    else {
      $form['quantity'] = array(
        '#type' => 'hidden',
        '#value' => $default_quantity,
        '#datatype' => 'integer',
        '#weight' => 45,
      );
    }

    // Add the line item's fields to a container on the form.
    $form['line_item_fields'] = array(
      '#type' => 'container',
      '#parents' => array('line_item_fields'),
      '#weight' => 10,
      '#tree' => TRUE,
    );

    field_attach_form('commerce_line_item', $form_state['line_item'], $form['line_item_fields'], $form_state);

    // Loop over the fields we just added and remove any that haven't been
    // marked for inclusion on this form.
    foreach (element_children($form['line_item_fields']) as $field_name) {
      $info = field_info_instance('commerce_line_item', $field_name, $form_state['line_item']->type);
      $form['line_item_fields'][$field_name]['#commerce_cart_settings'] = commerce_cart_field_instance_access_settings($info);

      if (empty($form['line_item_fields'][$field_name]['#commerce_cart_settings']['field_access'])) {
        $form['line_item_fields'][$field_name]['#access'] = FALSE;
      }
			//EMPIRE
			else {
				// Append an AJAX callback to each of the line item field widgets
				// so that we can dynamically calculate pricing on change events.
				$field_lang = $form['line_item_fields'][$field_name]['#language'];
				if (count(element_children($form['line_item_fields'][$field_name][$field_lang])) > 0) {
					foreach (element_children($form['line_item_fields'][$field_name][$field_lang]) as $delta) {
						foreach (element_children($form['line_item_fields'][$field_name][$field_lang][$delta]) as $sub_field) {
							$form['line_item_fields'][$field_name][$field_lang][$delta][$sub_field]['#ajax'] = array(
							'callback' => 'commerce_cart_add_to_cart_form_line_item_field_refresh',
							);
						}
					}
					//EMPIRE: ADD ADDITONAL AJAX CALLBACK FOR MATERIALS FIELD
					$form['line_item_fields']['field_tbd']['und']['hierarchical_select']['selects'][2]['#ajax'] = array(
							'callback' => 'commerce_cart_add_to_cart_form_line_item_field_refresh',
							);
					//END OF EMPIRE
				} else {
					$form['line_item_fields'][$field_name][$field_lang]['#ajax'] = array(
						'callback' => 'commerce_cart_add_to_cart_form_line_item_field_refresh',
					);
				}
			}
			//END EMPIRE
    }

    // Do not allow products without a price to be purchased.
    $values = commerce_product_calculate_sell_price($form_state['default_product']);

    if (is_null($values) || is_null($values['amount']) || is_null($values['currency_code'])) {
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Product not available'),
        '#weight' => 50,
        // Do not set #disabled in order not to prevent submission.
        '#attributes' => array('disabled' => 'disabled'),
        '#validate' => array('commerce_cart_add_to_cart_form_disabled_validate'),
      );
    }
    else {
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Add to cart'),
        '#weight' => 50,
      );
    }
  }

  // Add the handlers manually since we're using hook_forms() to associate this
  // form with form IDs based on the $product_ids.
  $form['#validate'][] = 'commerce_cart_add_to_cart_form_validate';
  $form['#submit'][] = 'commerce_cart_add_to_cart_form_submit';
  $form['#after_build'][] = 'commerce_cart_add_to_cart_form_after_build';

  return $form;
}

/**
 * Validation callback that prevents submission if the product is not available.
 */
function commerce_cart_add_to_cart_form_disabled_validate($form, &$form_state) {
  form_set_error('submit', t('This product is no longer available.'));
}

/**
 * Form validate handler: validate the product and quantity to add to the cart.
 */
function commerce_cart_add_to_cart_form_validate($form, &$form_state) {
  // Check to ensure the quantity is valid.
  if (!is_numeric($form_state['values']['quantity']) || $form_state['values']['quantity'] <= 0) {
    form_set_error('quantity', t('You must specify a valid quantity to add to the cart.'));
  }

  // If the custom data type attribute of the quantity element is integer,
  // ensure we only accept whole number values.
  if ($form['quantity']['#datatype'] == 'integer' &&
    (int) $form_state['values']['quantity'] != $form_state['values']['quantity']) {
    form_set_error('quantity', t('You must specify a whole number for the quantity.'));
  }

  // If the attributes matching product selector was used, set the value of the
  // product_id field to match; this will be fixed on rebuild when the actual
  // default product will be selected based on the product selector value.
  if (!empty($form_state['values']['attributes']['product_select'])) {
    form_set_value($form['product_id'], $form_state['values']['attributes']['product_select'], $form_state);
  }

  // Validate any line item fields that may have been included on the form.
  field_attach_form_validate('commerce_line_item', $form_state['line_item'], $form['line_item_fields'], $form_state);
}

/**
 * Ajax callback: returns AJAX commands when an attribute widget is changed.
 */
function commerce_cart_add_to_cart_form_attributes_refresh($form, $form_state) {
  $commands = array();

  // Render the form afresh to capture any changes to the available widgets
  // based on the latest selection.
  $commands[] = ajax_command_replace('.' . drupal_html_class($form['#form_id']), drupal_render($form));

  // Then render and return the various product fields that might need to be
  // updated on the page.
  if (!empty($form_state['context'])) {
    $product = commerce_product_load($form_state['default_product_id']);
    $form_state['default_product'] = $product;
    $product->display_context = $form_state['context'];

    // First render the actual fields attached to the referenced product.
    foreach (field_info_instances('commerce_product', $product->type) as $product_field_name => $product_field) {
      // Rebuild the same array of classes used when the field was first rendered.
      $replacement_class = drupal_html_class(implode('-', array($form_state['context']['class_prefix'], 'product', $product_field_name)));

      $classes = array(
        'commerce-product-field',
        drupal_html_class('commerce-product-field-' . $product_field_name),
        drupal_html_class('field-' . $product_field_name),
        $replacement_class,
      );

      $element = field_view_field('commerce_product', $product, $product_field_name, $form_state['context']['view_mode']);

      // Add an extra class to distinguish empty product fields.
      if (empty($element)) {
        $classes[] = 'commerce-product-field-empty';
      }

      // Append the prefix and suffix around existing values if necessary.
      $element += array('#prefix' => '', '#suffix' => '');
      $element['#prefix'] = '<div class="' . implode(' ', $classes) . '">' . $element['#prefix'];
      $element['#suffix'] .= '</div>';

      $commands[] = ajax_command_replace('.' . $replacement_class, drupal_render($element));
    }

    // Then render the extra fields defined for the referenced product.
    foreach (field_info_extra_fields('commerce_product', $product->type, 'display') as $product_extra_field_name => $product_extra_field) {
      $display = field_extra_fields_get_display('commerce_product', $product->type, $form_state['context']['view_mode']);

      // Only include extra fields that specify a theme function and that
      // are visible on the current view mode.
      if (!empty($product_extra_field['theme']) &&
        !empty($display[$product_extra_field_name]['visible'])) {
        // Rebuild the same array of classes used when the field was first rendered.
        $replacement_class = drupal_html_class(implode('-', array($form_state['context']['class_prefix'], 'product', $product_extra_field_name)));

        $classes = array(
          'commerce-product-extra-field',
          drupal_html_class('commerce-product-extra-field-' . $product_extra_field_name),
          $replacement_class,
        );

        // Build the product extra field to $element.
        $element = array(
          '#theme' => $product_extra_field['theme'],
          '#' . $product_extra_field_name => $product->{$product_extra_field_name},
          '#label' => $product_extra_field['label'] . ':',
          '#product' => $product,
          '#attached' => array(
            'css' => array(drupal_get_path('module', 'commerce_product') . '/theme/commerce_product.theme.css'),
          ),
          '#prefix' => '<div class="' . implode(' ', $classes) . '">',
          '#suffix' => '</div>',
        );

        // Add an extra class to distinguish empty fields.
        if (empty($element['#markup'])) {
          $classes[] = 'commerce-product-extra-field-empty';
        }

        $commands[] = ajax_command_replace('.' . $replacement_class, drupal_render($element));
      }
    }
  }

  // Allow other modules to add arbitrary AJAX commands on the refresh.
  drupal_alter('commerce_cart_attributes_refresh', $commands, $form, $form_state);

  return array('#type' => 'ajax', '#commands' => $commands);
}

//EMPIRE
/**
+ * Ajax callback: returns AJAX commands when line items fields change.
+ */
function commerce_cart_add_to_cart_form_line_item_field_refresh($form, &$form_state) {
	$line_item = $form_state['line_item'];
	$line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
	
	// Pass the line item object to rules to calculate the sale price.
	rules_invoke_event('commerce_product_calculate_sell_price', $line_item);
	
	// Load a product wrapper and pseudo replace the commerce_price value
	// so that we can generate a new render array.
	$product = commerce_product_load($form_state['default_product_id']);
	$product_wrapper = entity_metadata_wrapper('commerce_product', $product);
	try {
		$product_wrapper->commerce_price->set($line_item_wrapper->commerce_unit_price->value());
	}
	catch (Exception $ex) {
		// Leave the commerce_price field untouched if there were issues setting
		// the value.
	}
	
	// Determine the appropriate class wrappers to use when replacing the price.
	$replacement_class = drupal_html_class(implode('-', array($form_state['context']['class_prefix'], 'product', 'commerce_price')));
	
	$classes = array(
		'commerce-product-field',
		drupal_html_class('commerce-product-field-' . 'commerce_price'),
		drupal_html_class('field-' . 'commerce_price'),
		$replacement_class,
	);
	
	$element = field_view_field('commerce_product', $product, 'commerce_price', $form_state['context']['view_mode']);
	
	// Append the prefix and suffix around existing values if necessary.
	$element += array('#prefix' => '', '#suffix' => '');
	$element['#prefix'] = '<div class="' . implode(' ', $classes) . '">' . $element['#prefix'];
	$element['#suffix'] .= '</div>';
	
	// Replace the rendered commerce product price field via AJAX.
	$commands[] = ajax_command_replace('.' . $form_state['context']['class_prefix'] . '-product-commerce-price', drupal_render($element));
	return array('#type' => 'ajax', '#commands' => $commands);
}
//END EMPIRE

/**
 * Ajax callback: returns AJAX commands when an attribute widget is changed on
 * the Views powered shopping cart form.
 */
function commerce_cart_add_to_cart_views_form_refresh($form, $form_state) {
  $commands[] = array();

  // Extract the View from the form's arguments and derive its DOM ID class.
  $view = $form_state['build_info']['args'][0];
  $dom_id_class = drupal_html_class('view-dom-id-' . $view->dom_id);

  // Unset the form related variables from the $_POST array. Otherwise, when we
  // rebuild the View, the Views form will fetch these values to rebuild the
  // form state and resubmit the form.
  foreach (array('form_build_id', 'form_token', 'form_id') as $key) {
    unset($_POST[$key]);
  }

  // Render afresh the the output of the View and return it for replacement.
  $output = commerce_embed_view($view->name, $view->current_display, $view->args, $view->override_url);
  $commands[] = ajax_command_replace('.' . $dom_id_class, $output);

  return array('#type' => 'ajax', '#commands' => $commands);
}

/**
 * Form submit handler: add the selected product to the cart.
 */
function commerce_cart_add_to_cart_form_submit($form, &$form_state) {
  $product_id = $form_state['values']['product_id'];
  $product = commerce_product_load($product_id);

  // If the line item passed to the function is new...
  if (empty($form_state['line_item']->line_item_id)) {
    // Create the new product line item of the same type.
    $line_item = commerce_product_line_item_new($product, $form_state['values']['quantity'], 0, $form_state['line_item']->data, $form_state['line_item']->type);

    // Allow modules to prepare this as necessary. This hook is defined by the
    // Product Pricing module.
    drupal_alter('commerce_product_calculate_sell_price_line_item', $line_item);

    // Remove line item field values the user didn't have access to modify.
    foreach ($form_state['values']['line_item_fields'] as $field_name => $value) {
      // Note that we're checking the Commerce Cart settings that we inserted
      // into this form element array back when we built the form. This means a
      // module wanting to alter a line item field widget to be available must
      // update both its form element's #access value and the field_access value
      // of the #commerce_cart_settings array.
      if (empty($form['line_item_fields'][$field_name]['#commerce_cart_settings']['field_access'])) {
        unset($form_state['values']['line_item_fields'][$field_name]);
      }
    }

    // Unset the line item field values array if it is now empty.
    if (empty($form_state['values']['line_item_fields'])) {
      unset($form_state['values']['line_item_fields']);
    }

    // Add field data to the line item.
    field_attach_submit('commerce_line_item', $line_item, $form['line_item_fields'], $form_state);

    // Process the unit price through Rules so it reflects the user's actual
    // purchase price.
    rules_invoke_event('commerce_product_calculate_sell_price', $line_item);

    // Only attempt an Add to Cart if the line item has a valid unit price.
    $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    if (!is_null($line_item_wrapper->commerce_unit_price->value())) {
      // Add the product to the specified shopping cart.
      $form_state['line_item'] = commerce_cart_product_add(
        $form_state['values']['uid'],
        $line_item,
        isset($line_item->data['context']['add_to_cart_combine']) ? $line_item->data['context']['add_to_cart_combine'] : TRUE
      );
    }
    else {
      drupal_set_message(t('%title could not be added to your cart.', array('%title' => $product->title)), 'error');
    }
  }
}

/**
 * After build callback for the Add to Cart form.
 */
function commerce_cart_add_to_cart_form_after_build(&$form, &$form_state) {
  // Remove the default_product entity to mitigate cache_form bloat and performance issues.
  if (isset($form_state['default_product'])) {
    $form_state['default_product_id'] = $form_state['default_product']->product_id;
    unset($form_state['default_product']);
  }
  return $form;
}

/**
 * Implements hook_field_info_alter().
 */
function commerce_cart_field_info_alter(&$info) {
  // Set the default display formatter for product reference fields to the Add
  // to Cart form.
  $info['commerce_product_reference']['default_formatter'] = 'commerce_cart_add_to_cart_form';
}

/**
 * Implements hook_field_formatter_info().
 */
function commerce_cart_field_formatter_info() {
  return array(
    'commerce_cart_add_to_cart_form' => array(
      'label' => t('Add to Cart form'),
      'description' => t('Display an Add to Cart form for the referenced product.'),
      'field types' => array('commerce_product_reference', 'entityreference'),
      'settings' => array(
        'show_quantity' => FALSE,
        'default_quantity' => 1,
        'combine' => TRUE,
        'show_single_product_attributes' => FALSE,
        'line_item_type' => 'product',
      ),
    ),
  );
}

/**
 * Implements hook_field_formatter_settings_form().
 */
function commerce_cart_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  $display = $instance['display'][$view_mode];
  $settings = array_merge(field_info_formatter_settings($display['type']), $display['settings']);

  $element = array();

  if ($display['type'] == 'commerce_cart_add_to_cart_form') {
    $element['show_quantity'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display a textfield quantity widget on the add to cart form.'),
      '#default_value' => $settings['show_quantity'],
    );

    $element['default_quantity'] = array(
      '#type' => 'textfield',
      '#title' => t('Default quantity'),
      '#default_value' => $settings['default_quantity'] <= 0 ? 1 : $settings['default_quantity'],
      '#element_validate' => array('commerce_cart_field_formatter_settings_form_quantity_validate'),
      '#size' => 16,
    );

    $element['combine'] = array(
      '#type' => 'checkbox',
      '#title' => t('Attempt to combine like products on the same line item in the cart.'),
      '#description' => t('The line item type, referenced product, and data from fields exposed on the Add to Cart form must all match to combine.'),
      '#default_value' => $settings['combine'],
    );

    $element['show_single_product_attributes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show attribute widgets even if the Add to Cart form only represents one product.'),
      '#description' => t('If enabled, attribute widgets will be shown on the form with the only available options selected.'),
      '#default_value' => $settings['show_single_product_attributes'],
    );

    // Add a conditionally visible line item type element.
    $types = commerce_product_line_item_types();

    if (count($types) > 1) {
      $element['line_item_type'] = array(
        '#type' => 'select',
        '#title' => t('Add to Cart line item type'),
        '#options' => array_intersect_key(commerce_line_item_type_get_name(), drupal_map_assoc($types)),
        '#default_value' => $settings['line_item_type'],
      );
    }
    else {
      $element['line_item_type'] = array(
        '#type' => 'hidden',
        '#value' => reset($types),
      );
    }
  }

  return $element;
}

/**
 * Element validate callback: ensure a valid quantity is entered.
 */
function commerce_cart_field_formatter_settings_form_quantity_validate($element, &$form_state, $form) {
  if (!is_numeric($element['#value']) || $element['#value'] <= 0) {
    form_set_error(implode('][', $element['#parents']), t('You must enter a positive numeric default quantity value.'));
  }
}

/**
 * Implements hook_field_formatter_settings_summary().
 */
function commerce_cart_field_formatter_settings_summary($field, $instance, $view_mode) {
  $display = $instance['display'][$view_mode];
  $settings = array_merge(field_info_formatter_settings($display['type']), $display['settings']);

  $summary = array();

  if ($display['type'] == 'commerce_cart_add_to_cart_form') {
    $summary = array(
      t('Quantity widget: !status', array('!status' => !empty($settings['show_quantity']) ? t('Enabled') : t('Disabled'))),
      t('Default quantity: @quantity', array('@quantity' => $settings['default_quantity'])),
      t('Combine like items: !status', array('!status' => !empty($settings['combine']) ? t('Enabled') : t('Disabled'))),
      t('!visibility attributes on single product forms.', array('!visibility' => !empty($settings['show_single_product_attributes']) ? t('Showing') : t('Hiding'))),
    );

    if (count(commerce_product_line_item_types()) > 1) {
      $type = !empty($settings['line_item_type']) ? $settings['line_item_type'] : 'product';
      $summary[] = t('Add to Cart line item type: @type', array('@type' => commerce_line_item_type_get_name($type)));
    }
  }

  return implode('<br />', $summary);
}

/**
 * Implements hook_field_formatter_view().
 */
function commerce_cart_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $settings = array_merge(field_info_formatter_settings($display['type']), $display['settings']);
  $result = array();

  // Collect the list of product IDs.
  $product_ids = array();

  foreach ($items as $delta => $item) {
    if (isset($item['product_id'])) {
      $product_ids[] = $item['product_id'];
    }
    elseif (module_exists('entityreference') && isset($item['target_id'])) {
      $product_ids[] = $item['target_id'];
    }
  }

  if ($display['type'] == 'commerce_cart_add_to_cart_form') {
    // Load the referenced products.
    $products = commerce_product_load_multiple($product_ids);

    // Check to ensure products are referenced, before returning results.
    if (!empty($products)) {
      $type = !empty($settings['line_item_type']) ? $settings['line_item_type'] : 'product';
      $line_item = commerce_product_line_item_new(commerce_product_reference_default_product($products), $settings['default_quantity'], 0, array(), $type);
      $line_item->data['context']['product_ids'] = array_keys($products);
      $line_item->data['context']['add_to_cart_combine'] = !empty($settings['combine']);
      $line_item->data['context']['show_single_product_attributes'] = !empty($settings['show_single_product_attributes']);

      $result[] = array(
        '#arguments' => array(
          'form_id' => commerce_cart_add_to_cart_form_id($product_ids),
          'line_item' => $line_item,
          'show_quantity' => $settings['show_quantity'],
        ),
      );
    }
  }

  return $result;
}

/**
 * Implements hook_field_attach_view_alter().
 *
 * When a field is formatted for display, the display formatter does not know
 * what view mode it is being displayed for. Unfortunately, the Add to Cart form
 * display formatter needs this information when displaying product reference
 * fields on nodes to provide adequate context for product field replacement on
 * multi-value product reference fields. This hook is used to transform a set of
 * arguments into a form using the arguments and the extra context information
 * gleaned from the parameters passed into this function.
 */
function commerce_cart_field_attach_view_alter(&$output, $context) {
  // Loop through the fields passed in looking for any product reference fields
  // formatted with the Add to Cart form display formatter.
  foreach ($output as $field_name => $element) {
    if (!empty($element['#formatter']) && $element['#formatter'] == 'commerce_cart_add_to_cart_form') {
      // Prepare the context information needed by the cart form.
      $cart_context = $context;

      // Remove the full entity from the context array and put the ID in instead.
      list($entity_id, $vid, $bundle) = entity_extract_ids($context['entity_type'], $context['entity']);
      $cart_context['entity_id'] = $entity_id;
      unset($cart_context['entity']);

      // Remove any Views data added to the context by views_handler_field_field.
      // It unnecessarily increases the size of rows in the cache_form table for
      // Add to Cart form state data.
      if (!empty($cart_context['display']) && is_array($cart_context['display'])) {
        unset($cart_context['display']['views_view']);
        unset($cart_context['display']['views_field']);
        unset($cart_context['display']['views_row_id']);
      }

      // Add the context for displaying product fields in the context of an entity
      // that references the product by looking at the entity this product
      // reference field is attached to.
      $cart_context['class_prefix'] = $context['entity_type'] . '-' . $entity_id;
      $cart_context['view_mode'] = $context['entity_type'] . '_' . $element['#view_mode'];

      $entity_uri = entity_uri($context['entity_type'], $element['#object']);

      foreach (element_children($element) as $key) {
        // Extract the drupal_get_form() arguments array from the element.
        $arguments = $element[$key]['#arguments'];

        // Add the display path and referencing entity data to the line item.
        if (!empty($entity_uri['path'])) {
          $arguments['line_item']->data['context']['display_path'] = $entity_uri['path'];
        }

        $arguments['line_item']->data['context']['entity'] = array(
          'entity_type' => $context['entity_type'],
          'entity_id' => $entity_id,
          'product_reference_field_name' => $field_name,
        );

        // Update the product_ids variable to point to the entity data if we're
        // referencing multiple products.
        if (count($arguments['line_item']->data['context']['product_ids']) > 1) {
          $arguments['line_item']->data['context']['product_ids'] = 'entity';
        }

        // Replace the array containing the arguments with the return value of
        // drupal_get_form(). It will be rendered when the rest of the object is
        // rendered for display.
        $output[$field_name][$key] = drupal_get_form($arguments['form_id'], $arguments['line_item'], $arguments['show_quantity'], $cart_context);
      }
    }
  }
}

/**
 * Returns an array of product IDs used for building an Add to Cart form from
 * the context information in a line item's data array.
 *
 * @param $line_item
 *   The line item whose data array includes a context array used for building
 *   an Add to Cart form.
 *
 * @return
 *   The array of product IDs extracted from the line item.
 *
 * @see commerce_cart_add_to_cart_form()
 */
function commerce_cart_add_to_cart_form_product_ids($line_item) {
  $product_ids = array();

  if (empty($line_item->data['context']) ||
    empty($line_item->data['context']['product_ids']) ||
    ($line_item->data['context']['product_ids'] == 'entity' && empty($line_item->data['context']['entity']))) {
    return $product_ids;
  }

  // If the product IDs setting tells us to use entity values...
  if ($line_item->data['context']['product_ids'] == 'entity' &&
    is_array($line_item->data['context']['entity'])) {
    $entity_data = $line_item->data['context']['entity'];

    // Load the specified entity.
    $entity = entity_load_single($entity_data['entity_type'], $entity_data['entity_id']);

    // Extract the product IDs from the specified product reference field.
    if (!empty($entity->{$entity_data['product_reference_field_name']})) {
      $product_ids = entity_metadata_wrapper($entity_data['entity_type'], $entity)->{$entity_data['product_reference_field_name']}->raw();
    }
  }
  elseif (is_array($line_item->data['context']['product_ids'])) {
    $product_ids = $line_item->data['context']['product_ids'];
  }

  return $product_ids;
}

/**
 * Implements hook_preprocess_views_view().
 */
function commerce_cart_preprocess_views_view(&$vars) {
  $view = $vars['view'];

  // Add the shopping cart stylesheet to the cart or form if they are not empty.
  if ($view->name == 'commerce_cart_block' || $view->name == 'commerce_cart_form') {
    drupal_add_css(drupal_get_path('module', 'commerce_cart') . '/theme/commerce_cart.theme.css');
  }
}

/**
 * Implements hook_i18n_string_list_TEXTGROUP_alter().
 */
function commerce_cart_i18n_string_list_field_alter(&$strings, $type = NULL, $object = NULL) {
  if (!isset($strings['field']) || !is_array($object) || !commerce_cart_field_instance_is_attribute($object)) {
    return;
  }
  if (!empty($object['commerce_cart_settings']['attribute_widget_title'])) {
    $strings['field'][$object['field_name']][$object['bundle']]['attribute_widget_title'] = array(
      'string' => $object['commerce_cart_settings']['attribute_widget_title'],
      'title' => t('Attribute widget title'),
    );
  }
}
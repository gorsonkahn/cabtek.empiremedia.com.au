<?php
/**
 * @file
 * empire_config.features.menu_links.inc
 */

/**
 * Implements hook_menu_default_menu_links().
 */
function empire_config_menu_default_menu_links() {
  $menu_links = array();

  // Exported menu link: main-menu_base:product_category/1.
  $menu_links['main-menu_base:product_category/1'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'product_category/1',
    'router_path' => 'product_category/%',
    'link_title' => 'Base',
    'options' => array(
      'attributes' => array(
        'title' => 'Base',
      ),
      'identifier' => 'main-menu_base:product_category/1',
    ),
    'module' => 'taxonomy_menu',
    'hidden' => 0,
    'external' => 0,
    'has_children' => 0,
    'expanded' => 1,
    'weight' => 0,
    'customized' => 0,
  );
  // Exported menu link: main-menu_doors:product_category/5.
  $menu_links['main-menu_doors:product_category/5'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'product_category/5',
    'router_path' => 'product_category/%',
    'link_title' => 'Doors',
    'options' => array(
      'attributes' => array(
        'title' => 'Doors',
      ),
      'identifier' => 'main-menu_doors:product_category/5',
    ),
    'module' => 'taxonomy_menu',
    'hidden' => 0,
    'external' => 0,
    'has_children' => 0,
    'expanded' => 1,
    'weight' => 3,
    'customized' => 0,
  );
  // Exported menu link: main-menu_extras:product_category/9.
  $menu_links['main-menu_extras:product_category/9'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'product_category/9',
    'router_path' => 'product_category/%',
    'link_title' => 'Extras',
    'options' => array(
      'attributes' => array(
        'title' => 'Extras',
      ),
      'identifier' => 'main-menu_extras:product_category/9',
    ),
    'module' => 'taxonomy_menu',
    'hidden' => 0,
    'external' => 0,
    'has_children' => 0,
    'expanded' => 1,
    'weight' => 5,
    'customized' => 0,
  );
  // Exported menu link: main-menu_hardware:product_category/2.
  $menu_links['main-menu_hardware:product_category/2'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'product_category/2',
    'router_path' => 'product_category/%',
    'link_title' => 'Hardware',
    'options' => array(
      'attributes' => array(
        'title' => 'Hardware',
      ),
      'identifier' => 'main-menu_hardware:product_category/2',
    ),
    'module' => 'taxonomy_menu',
    'hidden' => 0,
    'external' => 0,
    'has_children' => 0,
    'expanded' => 1,
    'weight' => 6,
    'customized' => 0,
  );
  // Exported menu link: main-menu_panels:product_category/6.
  $menu_links['main-menu_panels:product_category/6'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'product_category/6',
    'router_path' => 'product_category/%',
    'link_title' => 'Panels',
    'options' => array(
      'attributes' => array(
        'title' => 'Panels',
      ),
      'identifier' => 'main-menu_panels:product_category/6',
    ),
    'module' => 'taxonomy_menu',
    'hidden' => 0,
    'external' => 0,
    'has_children' => 0,
    'expanded' => 1,
    'weight' => 4,
    'customized' => 0,
  );
  // Exported menu link: main-menu_pantry:product_category/7.
  $menu_links['main-menu_pantry:product_category/7'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'product_category/7',
    'router_path' => 'product_category/%',
    'link_title' => 'Pantry',
    'options' => array(
      'attributes' => array(
        'title' => 'Pantry',
      ),
      'identifier' => 'main-menu_pantry:product_category/7',
    ),
    'module' => 'taxonomy_menu',
    'hidden' => 0,
    'external' => 0,
    'has_children' => 0,
    'expanded' => 1,
    'weight' => 2,
    'customized' => 0,
  );
  // Exported menu link: main-menu_upper:product_category/8.
  $menu_links['main-menu_upper:product_category/8'] = array(
    'menu_name' => 'main-menu',
    'link_path' => 'product_category/8',
    'router_path' => 'product_category/%',
    'link_title' => 'Upper',
    'options' => array(
      'attributes' => array(
        'title' => 'Upper',
      ),
      'identifier' => 'main-menu_upper:product_category/8',
    ),
    'module' => 'taxonomy_menu',
    'hidden' => 0,
    'external' => 0,
    'has_children' => 0,
    'expanded' => 1,
    'weight' => 1,
    'customized' => 0,
  );

  // Translatables
  // Included for use with string extractors like potx.
  t('Base');
  t('Doors');
  t('Extras');
  t('Hardware');
  t('Panels');
  t('Pantry');
  t('Upper');

  return $menu_links;
}

<?php
/**
 * @file
 * empire_config.features.taxonomy.inc
 */

/**
 * Implements hook_taxonomy_default_vocabularies().
 */
function empire_config_taxonomy_default_vocabularies() {
  return array(
    'depth' => array(
      'name' => 'Depth',
      'machine_name' => 'depth',
      'description' => '',
      'hierarchy' => 0,
      'module' => 'taxonomy',
      'weight' => -7,
    ),
    'height' => array(
      'name' => 'Height',
      'machine_name' => 'height',
      'description' => '',
      'hierarchy' => 0,
      'module' => 'taxonomy',
      'weight' => -9,
    ),
    'material_finish' => array(
      'name' => 'Material / finish',
      'machine_name' => 'material_finish',
      'description' => '',
      'hierarchy' => 0,
      'module' => 'taxonomy',
      'weight' => 0,
    ),
    'product_category' => array(
      'name' => 'Product category',
      'machine_name' => 'product_category',
      'description' => '',
      'hierarchy' => 0,
      'module' => 'taxonomy',
      'weight' => -10,
    ),
    'width' => array(
      'name' => 'Width',
      'machine_name' => 'width',
      'description' => '',
      'hierarchy' => 0,
      'module' => 'taxonomy',
      'weight' => -8,
    ),
  );
}

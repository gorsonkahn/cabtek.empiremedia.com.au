<?php

/**
 * Export Drupal Commerce orders to Views.
 */
 
 
//EMPIRE PATCHED AS PER https://www.drupal.org/node/1696656#comment-6581036
//TO EXPOSE OPTIONSET RELATIONSHIP TOP VIEWS
function commerce_option_views_data_alter(&$data){
	
	$data['commerce_product']['commerce_option'] = array(
		'title' => t('Related option sets'),
		'relationship' => array(
			'base' => 'commerce_option',
			'base field' => 'product_id',
			'field' => 'product_id',
			'handler' => 'views_handler_relationship',
			'label' => t('Commerce option'),
			'title' => t('Commerce options referenced by this product'),
			'help' => t('Relate a product to commerce options it references.'),
		)
	);
	
	$data['commerce_line_item']['commerce_option'] = array(
		'title' => t('Related option sets'),
		'relationship' => array(
			'base' => 'commerce_option',
			'base field' => 'line_item_id',
			'field' => 'line_item_id',
			'handler' => 'views_handler_relationship',
			'label' => t('Commerce option'),
			'title' => t('Commerce options referenced by this line item'),
			'help' => t('Relate a line item to commerce options it references.'),
		)
	);
	
	$data['commerce_option']['product_id']['relationship'] = array(
		'base' => 'commerce_product',
		'base field' => 'product_id',
		'handler' => 'views_handler_relationship',
		'handler' => 'views_handler_relationship',
		'label' => t('Product'),
		'title' => t('Products that reference this option'),
		'help' => t('Relate an option to the products referencing it.')
	);
	
	$data['commerce_option']['line_item_id']['relationship'] = array(
		'base' => 'commerce_line_item',
		'base field' => 'line_item_id',
		'handler' => 'views_handler_relationship',
		'label' => t('Line item'),
		'title' => t('Line items that reference this option'),
		'help' => t('Relate an option to the line items referencing it.')
	);
}

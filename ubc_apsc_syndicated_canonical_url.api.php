<?php

/**
 * @file
 *
 * The contents of this file are never loaded, or executed, it is purely for
 * documentation purposes.
 *
 * @link https://www.drupal.org/docs/develop/coding-standards/api-documentation-and-comment-standards#hooks
 * Read the standards for documenting hooks. @endlink
 *
 */

/**
 * @file
 * Implement and invoke hooks HOOK_page_attachments_alter(array &$page) as ubc_apsc_syndicated_canonical_url_page_attachments_alter(array &$page)
 * Replaces the canonical URL metatag of the local domain name with the one defined in the module's configuration
 * Configuration allows to enter the source domain and select which content types to activate the functionality on.
 * Note: this creates a Drupal config file to store the settings.
 */

function ubc_apsc_syndicated_canonical_url_page_attachments_alter(array &$page) {
	
	// check not an admin page
	if(!(\Drupal::service('router.admin_context')->isAdminRoute())) {
		
		// get module config settings
		$config = \Drupal::config('ubc_apsc_syndicated_canonical_url.settings');
		$canonical_domain = $config->get('ubc_apsc_syndicated_canonical_url.origin_domain');
		
		// check the origin domain set
		if(!empty($canonical_domain)) {
			
			// get current node type, list configured syndicated content types
			$node = \Drupal::routeMatch()->getParameter('node');
			$content_type = $node->getType();
			$syndicated_types = array_values($config->get('ubc_apsc_syndicated_canonical_url.content_types') ?: []);
			
			// check node is part of syndicated content types and node has been syndicated
			if(in_array($content_type, $syndicated_types, true) && $node->field_syndicate_to_eng->value) {				

				//replace local domain with syndicated origin
				$local_canonical = parse_url($page['#attached']['html_head'][2][0]['#attributes']['href']);
				$page['#attached']['html_head'][2][0]['#attributes']['href'] = "$local_canonical[scheme]://$canonical_domain$local_canonical[path]" . (isset($local_canonical["query"]) ? "?$local_canonical[query]" : "");
				
			}
		}
	}
}

/**
 * @file
 * Implement and invoke hooks HOOK_preprocess_node(&$variables)
 * Provide variable defined in the module's configuration in templates for syndication source label
 */
function ubc_apsc_syndicated_canonical_url_preprocess_node(&$variables) {
	
	// get module config settings
	$config = \Drupal::config('ubc_apsc_syndicated_canonical_url.settings');
	
	$variables['local_news_source_label'] = $config->get('ubc_apsc_syndicated_canonical_url.local_label');
	
	// check not an admin page
	$route_match = \Drupal::routeMatch();

	if ($route_match->getRouteName() == 'entity.node.canonical') {
		
		$canonical_domain = $config->get('ubc_apsc_syndicated_canonical_url.origin_domain');
		
		// check the origin domain set
		if(!empty($canonical_domain)) {
			
			// get current node type, list configured syndicated content types
			$node = \Drupal::routeMatch()->getParameter('node');
			$content_type = $variables['node']->getType();
			$syndicated_types = array_values($config->get('ubc_apsc_syndicated_canonical_url.content_types') ?: []);
			
			// check node is part of syndicated content types and node has been syndicated, make label available as variable for templates
			if(in_array($content_type, $syndicated_types, true) && $variables['node']->field_syndicate_to_eng->value) {
				$variables['syndicated_news_source_label'] = $config->get('ubc_apsc_syndicated_canonical_url.origin_label');
			}
		}
	}
}

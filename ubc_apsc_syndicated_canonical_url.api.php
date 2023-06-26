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

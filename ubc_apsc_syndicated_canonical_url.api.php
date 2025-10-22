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

/**
* Implements hook_simple_sitemap_links_alter().
*
* Remove the sitemap URLs for syndicated content, before they are transformed to XML.
*/
function ubc_apsc_syndicated_canonical_url_simple_sitemap_links_alter(array &$links, $sitemap) {

	// get module config settings
	$config = \Drupal::config('ubc_apsc_syndicated_canonical_url.settings');
	$sitemap_exclusion = $config->get('ubc_apsc_syndicated_canonical_url.sitemap_exclusion');
	$syndicated_types = array_values($config->get('ubc_apsc_syndicated_canonical_url.content_types') ?: []);

	// check the origin domain set
	if($sitemap_exclusion) {

		foreach ($links as $key => &$link) {

			// Remove the URL from the sitemap for a content types with nodes marked for syndication.
			if (in_array($link['meta']['entity_info']['bundle'], $syndicated_types, true) && $link['meta']['entity_info']['entity_type'] === 'node' && isset($link['meta']['entity_info']['id'])) {
				$node = \Drupal\node\Entity\Node::load($link['meta']['entity_info']['id']);
				if ($node->field_syndicate_to_eng->value)
					unset($links[$key]);
			}
		}
	}
}

/**
 * Implements hook_form_alter().
 * Alters node edit forms to disable simple_sitemap field when content is marked for syndication.
 */
function ubc_apsc_syndicated_canonical_url_form_node_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
	// Get syndicated content types that have a canonical URL to the original site.
	$config = \Drupal::config('ubc_apsc_syndicated_canonical_url.settings');
	$syndicated_content_types = $config->get('ubc_apsc_syndicated_canonical_url.content_types');
	$edit_form = substr($form_id, 5, -10);

	// Target node edit forms that are part of syndicated content types.
	if (is_array($syndicated_content_types) && in_array($edit_form, $syndicated_content_types)) {

		// Disable the simple_sitemap field if 'field_syndicate_to_eng' is checked.
		if (isset($form['field_syndicate_to_eng'])) {
			$form['simple_sitemap']['#states'] = [
				'disabled' => [
					':input[name="field_syndicate_to_eng[value]"]' => ['checked' => TRUE],
				],
			];

			// Use #states to update the correct radio based on field_syndicate_to_eng.
			if (isset($form['simple_sitemap']['default']['index'])) {
				$form['simple_sitemap']['default']['index'][0]['#states'] = [
					'checked' => [
						':input[name="field_syndicate_to_eng[value]"]' => ['checked' => TRUE],
					],
				];
				$form['simple_sitemap']['default']['index'][1]['#states'] = [
					'checked' => [
						':input[name="field_syndicate_to_eng[value]"]' => ['checked' => FALSE],
					],
				];
			}

			// Include a note for users to understand that syndicated content will not be included in the sitemap.
			$form['simple_sitemap']['syndicated_note_container'] = [
				'#type' => 'container',
				'#states' => [
					'visible' => [
						':input[name="field_syndicate_to_eng[value]"]' => ['checked' => TRUE],
					],
				],
			];
			$form['simple_sitemap']['syndicated_note_container']['syndicated_note'] = [
				'#type' => 'markup',
				'#markup' => t('Note: Syndicated content will not be included in sitemaps.'),
			];
		}
	}
}

/**
 * Implements hook_update_projects_alter(&$projects).
 * Alter the list of projects before fetching data and comparing versions.
 *
 * Hide projects from the list to avoid "No available releases found" warnings on the available updates report
 *
 * @see \Drupal\update\UpdateManager::getProjects()
 * @see \Drupal\Core\Utility\ProjectInfo::processInfoList()
 */
function ubc_apsc_syndicated_canonical_url_update_projects_alter(&$projects) {
  // Hide a site-specific module from the list.
  unset($projects['ubc_apsc_syndicated_canonical_url']);
}

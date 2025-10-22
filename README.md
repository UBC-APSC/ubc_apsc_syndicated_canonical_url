ubc_apsc_syndicated_canonical_url
===========

ubc_apsc_syndicated_canonical_url for Drupal. Alters the canonical URL with origin domain name in the metatag, provides a label variable for content that has been syndicated to output in templates. Option to exclude syndicated content from sitemap.

Instructions
------------

Activate the module and configure the settings to specify the origin domain for syndicated content.
Configuration form found under /admin/config/search/ubc_apsc_syndicated_canonical_url.
Set orignal domain and label, and select content types where the canonical URL should be updated to point to original content (this creates a stored config entry for Drupal).
Optionally, activate the functionality to remove URLs for syndicated content from sitemaps (requires simple_sitemap module to be installed).

Important: operates on content that has 'syndicate' checkbox activated locally.
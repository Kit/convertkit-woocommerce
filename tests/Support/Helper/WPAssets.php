<?php
namespace Tests\Support\Helper;

/**
 * Helper methods and actions related to WordPress' Bulk Edit functionality,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.6.4
 */
class WPAssets extends \Codeception\Module
{
	/**
	 * Helper method to assert that the given script has been enqueued in WordPress.
	 *
	 * @since   1.6.4
	 *
	 * @param   AcceptanceHelper $I         Acceptance Helper.
	 * @param   string           $url       Script URL, relative to Plugin root folder.
	 */
	public function seeJSEnqueued($I, $url)
	{
		$I->seeInSource('<script src="' . $_ENV['WORDPRESS_URL'] . '/wp-content/plugins/' . $url );
	}

	/**
	 * Helper method to assert that the given stylesheet has been enqueued in WordPress.
	 *
	 * @since   1.6.4
	 *
	 * @param   AcceptanceHelper $I         Acceptance Helper.
	 * @param   string           $url       CSS URL, relative to Plugin root folder.
	 * @param   string           $id        CSS ID.
	 */
	public function seeCSSEnqueued($I, $url, $id)
	{
		$I->seeInSource('<link rel="stylesheet" id="' . $id . '" href="' . $_ENV['WORDPRESS_URL'] . '/wp-content/plugins/' . $url );
	}
}

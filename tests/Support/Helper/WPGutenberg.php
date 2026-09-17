<?php
namespace Tests\Support\Helper;

/**
 * Helper methods and actions related to WordPress' Gutenberg / Block editor,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.9.6
 */
class WPGutenberg extends \Codeception\Module
{
	/**
	 * Helper method to switch to the Gutenberg editor Iframe
	 * when all blocks use the Block API v3:
	 * https://developer.wordpress.org/block-editor/reference-guides/block-api/block-api-versions/
	 *
	 * @since   2.7.7
	 *
	 * @param   EndToEndTester $I  EndToEnd Tester.
	 */
	public function switchToGutenbergIFrameEditor($I)
	{
		// Wait for the iframe to mount. In WordPress 7.1+ the editor iframe
		// can take longer to appear in the DOM than a tryToSeeElement()
		// check, causing the switch to be silently skipped. Catch the timeout
		// so that environments which intentionally have no iframe (e.g. Divi
		// is active, which prevents the iframe block editor from being used)
		// still fall through to the non-iframe path.
		try {
			$I->waitForElement('iframe[name="editor-canvas"]', 10);
		} catch (\Facebook\WebDriver\Exception\TimeoutException $e) {
			return;
		}

		$I->switchToIFrame('iframe[name="editor-canvas"]');
	}
}

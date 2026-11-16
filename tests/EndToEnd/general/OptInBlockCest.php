<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests the Opt In block in the WooCommerce Checkout block editor.
 *
 * @since   2.2.5
 */
class OptInBlockCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   2.2.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugins.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup WooCommerce Plugin, using the Checkout block.
		$I->setupWooCommercePlugin($I);
		$I->setupWooCommerceCheckoutBlock($I);

		// Enable Integration, displaying the opt in checkbox.
		$I->setupConvertKitPlugin(
			$I,
			displayOptIn: true
		);
	}

	/**
	 * Test that the Opt In block uses API version 3 and displays the opt in checkbox
	 * when editing the Checkout page.
	 *
	 * @since   2.2.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInBlockInCheckoutEditor(EndToEndTester $I)
	{
		// Edit the Checkout page.
		$pageID = $I->grabFromDatabase(
			'wp_posts',
			'ID',
			[
				'post_name' => 'checkout',
			]
		);
		$I->amOnAdminPage('post.php?post=' . $pageID . '&action=edit');
		$I->maybeCloseGutenbergWelcomeModal($I);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the block is registered with API version 3.
		$I->waitForJS('return typeof wp !== "undefined" && wp.blocks && !! wp.blocks.getBlockType("ckwc/opt-in");', 10);
		$I->assertEquals(3, $I->executeJS('return wp.blocks.getBlockType("ckwc/opt-in").apiVersion;'));

		// Confirm the opt in checkbox displays in the editor.
		$I->switchToGutenbergIFrameEditor($I);
		$I->waitForElementVisible('.wp-block-ckwc-opt-in #ckwc_opt_in');
		$I->seeCheckboxIsChecked('#ckwc_opt_in');
		$I->see('I want to subscribe to the newsletter', '.wp-block-ckwc-opt-in');
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   2.2.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->switchToIFrame();
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}

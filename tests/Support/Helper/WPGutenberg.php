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
	 * Helper method to close the Gutenberg "Welcome to the block editor" dialog, which
	 * might show for each Page/Post test performed due to there being no persistence
	 * remembering that the user dismissed the dialog.
	 *
	 * @since   1.9.6
	 *
	 * @param   EndToEndTester $I Acceptance Tester.
	 */
	public function maybeCloseGutenbergWelcomeModal($I)
	{
		try {
			$I->performOn(
				'.components-modal__screen-overlay',
				[
					'click' => '.components-modal__screen-overlay .components-modal__header button.components-button',
				],
				3
			);
		} catch ( \Facebook\WebDriver\Exception\TimeoutException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// No modal exists, so nothing to dismiss.
		}
	}
}

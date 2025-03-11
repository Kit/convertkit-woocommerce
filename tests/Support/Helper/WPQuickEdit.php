<?php
namespace Tests\Support\Helper;

/**
 * Helper methods and actions related to WordPress' Quick Edit functionality,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.9.6
 */
class WPQuickEdit extends \Codeception\Module
{
	/**
	 * Quick Edits the given Post ID, changing form field values and saving.
	 *
	 * @since   1.9.8.0
	 *
	 * @param   AcceptanceHelper $I              Acceptance Helper.
	 * @param   string           $postType       Programmatic Post Type.
	 * @param   int              $postID         Post ID.
	 * @param   array            $configuration  Configuration (field => value key/value array).
	 */
	public function quickEdit($I, $postType, $postID, $configuration)
	{
		// Open Quick Edit form for the Post.
		$I->openQuickEdit($I, $postType, $postID);

		// Apply configuration.
		foreach ($configuration as $field => $attributes) {
			// Check that the field exists.
			$I->seeElementInDOM('#ckwc-quick-edit #' . $field);

			// Depending on the field's type, define its value.
			switch ($attributes[0]) {
				case 'select':
					$I->selectOption('#ckwc-quick-edit #' . $field, $attributes[1]);
					break;
				default:
					$I->fillField('#ckwc-quick-edit #' . $field, $attributes[1]);
					break;
			}
		}

		// Click Update.
		$I->click('Update');
	}

	/**
	 * Opens the Quick Edit form for the given Post ID.
	 *
	 * @since   1.9.8.1
	 *
	 * @param   AcceptanceHelper $I              Acceptance Helper.
	 * @param   string           $postType       Programmatic Post Type.
	 * @param   int              $postID         Post ID.
	 */
	public function openQuickEdit($I, $postType, $postID)
	{
		// Navigate to Post Type's WP_List_Table.
		$I->amOnAdminPage('edit.php?post_type=' . $postType);

		// Hover mouse over Post's table row.
		$I->moveMouseOver('tr#post-' . $postID);

		// Wait for Quick edit link to be visible.
		$I->waitForElementVisible('tr#post-' . $postID . ' button.editinline');

		// Click Quick Edit link.
		$I->click('tr#post-' . $postID . ' button.editinline');
	}
}

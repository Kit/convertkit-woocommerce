<?php
namespace Tests\Support\Helper;

/**
 * Helper methods and actions related to WordPress' Bulk Edit functionality,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.9.6
 */
class WPBulkEdit extends \Codeception\Module
{
	/**
	 * Bulk Edits the given Post IDs, changing form field values and saving.
	 *
	 * @since   1.9.8.0
	 *
	 * @param   AcceptanceHelper $I              Acceptance Helper.
	 * @param   string           $postType       Programmatic Post Type.
	 * @param   array            $postIDs        Post IDs.
	 * @param   array            $configuration  Configuration (field => value key/value array).
	 * @param   bool             $noticePostType The post type name expected in the notice (false = use $postType).
	 */
	public function bulkEdit($I, $postType, $postIDs, $configuration, $noticePostType = false)
	{
		// Open Bulk Edit form for the Posts.
		$I->openBulkEdit($I, $postType, $postIDs);

		// Apply configuration.
		foreach ($configuration as $field => $attributes) {
			// Check that the field exists.
			$I->seeElementInDOM('#ckwc-bulk-edit #' . $field);

			// Depending on the field's type, define its value.
			switch ($attributes[0]) {
				case 'select':
					$I->selectOption('#ckwc-bulk-edit #' . $field, $attributes[1]);
					break;
				default:
					$I->fillField('#ckwc-bulk-edit #' . $field, $attributes[1]);
					break;
			}
		}

		switch ( $noticePostType ) {
			case 'coupon':
				// Scroll to Bulk Edit label.
				$I->scrollTo('#bulk-edit-legend');
				break;
			default:
				// Scroll to Stock Quantity.
				$I->scrollTo('select.change_stock');
				break;
		}

		// Click Update.
		$I->click('Update');

		// Wait for a notification to display.
		$I->waitForElementVisible('div.updated.notice.is-dismissible');

		// Confirm that Bulk Editing saved with no errors.
		$I->seeInSource(count($postIDs) . ' ' . ( $noticePostType ? $noticePostType : $postType ) . 's updated');
	}

	/**
	 * Opens the Bulk Edit form for the given Post ID.
	 *
	 * @since   1.9.8.1
	 *
	 * @param   AcceptanceHelper $I              Acceptance Helper.
	 * @param   string           $postType       Programmatic Post Type.
	 * @param   array            $postIDs        Post IDs.
	 */
	public function openBulkEdit($I, $postType, $postIDs)
	{
		// Navigate to Post Type's WP_List_Table.
		$I->amOnAdminPage('edit.php?post_type=' . $postType);

		// Check boxes for Post IDs.
		foreach ($postIDs as $postID) {
			$I->checkOption('#cb-select-' . $postID);
		}

		// Select Edit from the Bulk actions dropdown.
		$I->selectOption('#bulk-action-selector-top', 'Edit');

		// Click Apply button.
		$I->click('#doaction');
	}
}

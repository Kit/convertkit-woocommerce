<?php
namespace Tests\Support\Helper;

/**
 * Helper methods and actions related to the Select2 component,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.9.6
 */
class Select2 extends \Codeception\Module
{
	/**
	 * Helper method to enter text into a jQuery Select2 Field, selecting the option that appears.
	 *
	 * @since   1.9.6.4
	 *
	 * @param   EndToEndTester $I          Acceptance Tester.
	 * @param   string         $container  Field CSS Class / ID.
	 * @param   string         $value      Field Value.
	 * @param   string         $ariaAttributeName  Aria Attribute Name (aria-controls|aria-owns).
	 *
	 * @throws  \Exception If the dropdown does not open.
	 */
	public function fillSelect2Field($I, $container, $value, $ariaAttributeName = 'aria-controls')
	{
		$I->waitForElementVisible($container);
		$fieldID     = $I->grabAttributeFrom($container, 'id');
		$fieldName   = str_replace('-container', '', str_replace('select2-', '', $fieldID));
		$searchField = '.select2-search__field[' . $ariaAttributeName . '="select2-' . $fieldName . '-results"]';

		// Click until the dropdown opens, as clicks before Select2 initializes (e.g. in a modal) are ignored.
		for ($attempt = 1; $attempt <= 3; $attempt++) {
			$I->click('#' . $fieldID);
			try {
				$I->waitForElementVisible($searchField, 3);
				break;
			} catch (\Exception $e) {
				if ($attempt === 3) {
					throw $e;
				}
			}
		}

		$I->fillField($searchField, $value);
		$I->waitForElementVisible('ul#select2-' . $fieldName . '-results li.select2-results__option--highlighted');
		$I->pressKey($searchField, \Facebook\WebDriver\WebDriverKeys::ENTER);
	}
}

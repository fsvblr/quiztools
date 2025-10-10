<?php

/**
 * @package     QuizTools.Administrator
 * @subpackage  com_quiztools
 *
 * @copyright   (C) 2025 https://github.com/fsvblr/quiztools
 */

namespace Qt\Component\Quiztools\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Transforms an input field into a Tags component.
 *
 * https://yaireo.github.io/tagify/
 * https://github.com/yairEO/tagify
 *
 * Data is received in the request as
 * '[{"value":"tag1"},{"value":"tag2"},{"value":"tag3"}]' .
 *
 * @since  5.3
 */
class TagifyField extends FormField
{
    public $type = 'Tagify';

    /**
     * Method to get the field input for a Tagify field.
     *
     * @return  string  The field input.
     *
     * @since   3.1
     */
    protected function getInput()
    {
        $options = $this->getOptions();
        $value = !empty($options) ? implode(', ', $options) : '';

        $attr = '';
		$attr .= ' id="' . $this->id . '"';
		$attr .= ' class="' . ($this->class ?: '') . ' tagify-select"';
		$attr .= ' placeholder="' . ($this->hint ? Text::_(htmlspecialchars($this->hint, ENT_QUOTES, 'UTF-8')) : Text::_('COM_QUIZTOOLS_FIELD_TAGIFY_TYPE_SOME_ITEMS')) . '" ';

		if ((bool) $this->required) {
			$attr  .= ' required class="required"';
		}

	    /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
	    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
	    $wa->useStyle('com_quiztools.tagify')
            ->useScript('com_quiztools.tagify');

        $wa->addInlineStyle('
            .tagify-field > tags {
                width: 100%;
            }
        ');

        $wa->addInlineScript('
            window.Quiztools = window.Quiztools || {};
            window.Quiztools.TagifyField = window.Quiztools.TagifyField || [];
            (function() {
                window.addEventListener("load", () => {
                    let tagInput = document.querySelector("#' . $this->id . '");
                    initTagify(tagInput);
                });
                
                document.addEventListener("subform-row-add", (event) => {
                    let tagInput = event.detail.row.querySelector(".tagify-select");
                    if (tagInput) {
                        initTagify(tagInput);
                    }
                });
                
                function initTagify(tagInput) {
                    if (tagInput && tagInput.id && !window.Quiztools.TagifyField.includes(tagInput.id)) {
                        window.Quiztools.TagifyField.push(tagInput.id);
                        new Tagify(tagInput);
                    }
                }
            })();
        ');

        return '<div class="tagify-field">
					<input type="text" name="' . $this->name . '" value="' . $value . '" ' . $attr . ' />
				</div>';
    }

    /**
     * Method to get a list of values
     *
     * @return  array[]
     */
    protected function getOptions()
    {
        $table = $this->getAttribute('table');
        $key = $this->getAttribute('key');
        $field = $this->getAttribute('field');

        $app = Factory::getApplication();
		$id = $app->getInput()->getInt('id');

        if (empty($id) || empty($table) || empty($key) || empty($field)) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery();

	    $query->select($db->qn($field, 'values'))
		    ->from($db->qn($table))
		    ->where($db->qn($key) . ' = ' . $db->q($id));
	    $db->setQuery($query);

	    try {
		    $values = $db->loadResult();
	    } catch (\RuntimeException $e) {
		    return [];
	    }

        $registry = new Registry($values);
        $values = $registry->toArray();

        return $values;
    }
}

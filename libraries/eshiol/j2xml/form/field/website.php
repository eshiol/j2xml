<?php
/**
 * @version		14.10.244 libraries/eshiol/j2xml/sender.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		14.10.244
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2014 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Platform.
 * Supports an HTML select list of categories
 *
 * @package     Joomla.Legacy
 * @subpackage  Form
 * @since       11.1
 */
class JFormFieldWebsite extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  14.10
	 */
	public $type = 'Website';

	/**
	 * Flag to tell the field to always be in multiple values mode.
	 *
	 * @var    boolean
	 * @since  14.10
	 */
	protected $forceMultiple = true;
	
	/**
	 * Method to get the custom field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  14.10
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();
	
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
	
		$query->select('id as value, title as text');
		$query->from('#__j2xml_websites');
	
		// Get the options.
		$db->setQuery($query);
	
		$options = $db->loadObjectList();
	
		// Check for a database error.
		if ($db->getErrorNum()) {
			JError::raiseWarning(500, $db->getErrorMsg());
		}
		 
		$options = array_merge(parent::getOptions(), $options);
		return $options;
	}
}

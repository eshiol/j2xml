<?php
/**
 * @version		3.2.126 administrator/com_j2xml/models/website.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.3
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
 
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Website model
 */
class J2XMLModelWebsiteBase extends JModelAdmin
{
	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param	object	A record object.
	 * @return	boolean	True if allowed to delete the record. Defaults to the permission set in the component.
	 * @since	1.6
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id)) {
			if ($record->state != -2) {
				return ;
			}
			$user = JFactory::getUser();

			return $user->authorise('core.delete', 'com_j2xml');
		}
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param	object	A record object.
	 * @return	boolean	True if allowed to change the state of the record. Defaults to the permission set in the component.
	 * @since	1.6
	 */
	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		return $user->authorise('core.edit.state', 'com_j2xml');
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Website', $prefix = 'J2XMLTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_j2xml.website', 'website', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_j2xml.edit.website.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}
}

if (version_compare(JPlatform::RELEASE, '12', 'ge'))
{
	class J2XMLModelWebsite extends J2XMLModelWebsiteBase
	{
		/**
		 * Prepare and sanitise the table data prior to saving.
		 *
		 * @param	JTable	A JTable object.
		 * @since	1.6
		 */
		protected function prepareTable($table)
		{
			$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		}
	}
}
else 
{
	class J2XMLModelWebsite extends J2XMLModelWebsiteBase 	
	{
		/**
		 * Prepare and sanitise the table data prior to saving.
		 *
		 * @param	JTable	A JTable object.
		 * @since	1.6
		 */
		protected function prepareTable(&$table)
		{
			$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		}
	}
}
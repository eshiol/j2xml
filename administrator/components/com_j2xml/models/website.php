<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
 * 
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die();

jimport('joomla.application.component.modeladmin');

/**
 * Website model
 *
 * @version 3.7.192
 * @since 1.5.3
 */
class J2XMLModelWebsite extends JModelAdmin
{

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param
	 *        	object A record object.
	 * @return boolean True if allowed to delete the record. Defaults to the
	 *         permission set in the component.
	 * @since 1.6
	 */
	protected function canDelete ($record)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		if (! empty($record->id))
		{
			if ($record->state != - 2)
			{
				return;
			}
			$user = JFactory::getUser();

			return $user->authorise('core.delete', 'com_j2xml');
		}
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param
	 *        	object A record object.
	 * @return boolean True if allowed to change the state of the record.
	 *         Defaults to the permission set in the component.
	 * @since 1.6
	 */
	protected function canEditState ($record)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$user = JFactory::getUser();

		return $user->authorise('core.edit.state', 'com_j2xml');
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param
	 *        	type The table type to instantiate
	 * @param
	 *        	string A prefix for the table class name. Optional.
	 * @param
	 *        	array Configuration array for model. Optional.
	 * @return JTable A database object
	 * @since 1.6
	 */
	public function getTable ($type = 'Website', $prefix = 'J2XMLTable', $config = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param array $data
	 *        	Data for the form.
	 * @param boolean $loadData
	 *        	True if the form is to load its own data (default case), false
	 *        	if not.
	 * @return mixed A JForm object on success, false on failure
	 * @since 1.6
	 */
	public function getForm ($data = array(), $loadData = true)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		// Get the form.
		$form = $this->loadForm('com_j2xml.website', 'website', array(
				'control' => 'jform',
				'load_data' => $loadData
		));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed The data for the form.
	 * @since 1.6
	 */
	protected function loadFormData ()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_j2xml.edit.website.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param
	 *        	JTable A JTable object.
	 * @since 1.6
	 */
	protected function prepareTable ($table)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param array $data
	 *        	The form data.
	 *        
	 * @return boolean True on success.
	 *        
	 * @since 3.7
	 */
	public function save ($data)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		if ($data['type'] == 1)
		{
			$data['username'] = $data['client_id'];
			$data['password'] = $data['client_secret'];
		}

		return parent::save($data);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param integer $pk
	 *        	The id of the primary key.
	 *        
	 * @return mixed Object on success, false on failure.
	 *        
	 * @since 3.7
	 */
	public function getItem ($pk = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		if ($item = parent::getItem($pk))
		{
			if ($item->type == 1)
			{
				$item->client_id = $item->username;
				$item->client_secret = $item->password;
			}
		}

		return $item;
	}
}

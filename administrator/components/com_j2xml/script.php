<?php
/**
 * @version		3.3.147 administrator/components/com_j2xml/script.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		3.3.147
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

use Joomla\Registry\Registry;

/**
 * Installation class to perform additional changes during install/uninstall/update
 */
class Com_J2XMLInstallerScript
{
	/**
	 * Method to run after the install routine.
	 *
	 * @param   string                      $type    The action being performed
	 * @param   JInstallerAdapterComponent  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   3.4.1
	 */
	public function postflight($type, $parent)
	{
		// Only execute database changes on MySQL databases
		$dbName = JFactory::getDbo()->name;
	
		if (strpos($dbName, 'mysql') !== false)
		{
			// Add Missing Table Colums if needed
			$this->addColumnsIfNeeded();
		}
	}
	
	/**
	 * Method to add colums from #__buttons_extra if they are missing.
	 *
	 * @return  void
	 *
	 * @since   3.4.1
	 */
	private function addColumnsIfNeeded()
	{
		$db    = JFactory::getDbo();
		$table = $db->getTableColumns('#__j2xml_websites');
	
		if (!array_key_exists('alias', $table))
		{
			$sql = 'ALTER TABLE ' . $db->qn('#__j2xml_websites') . ' ADD COLUMN ' . $db->qn('alias') . " varchar(255) NOT NULL DEFAULT ''";
			$db->setQuery($sql);
			$db->execute();
		}
	}	
}

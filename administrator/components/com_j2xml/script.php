<?php
/**
 * @version		3.7.177 administrator/components/com_j2xml/script.php
 *
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		3.7
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2018 Helios Ciancio. All Rights Reserved
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
class Com_J2xmlInstallerScript
{
	/**
	 * This method is called after a component is installed.
	 *
	 * @param  \stdClass $parent - Parent object calling this method.
	 *
	 * @return void
	 */
	public function install($parent)
	{
	}
	
	/**
	 * This method is called after a component is uninstalled.
	 *
	 * @param  \stdClass $parent - Parent object calling this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
	}
	
	/**
	 * This method is called after a component is updated.
	 *
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	public function update($parent)
	{
	}
	
	/**
	 * Runs just before any installation action is preformed on the component.
	 * Verifications and pre-requisites should run in this function.
	 *
	 * @param  string    $type   - Type of PreFlight action. Possible values are:
	 *                           - * install
	 *                           - * update
	 *                           - * discover_install
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}
	
	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @param  string    $type   - Type of PostFlight action. Possible values are:
	 *                           - * install
	 *                           - * update
	 *                           - * discover_install
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	function postflight($type, $parent)
	{
		$db = JFactory::getDbo();
		$db->setQuery(
			$db->getQuery(true)
				->update('#__content')
				->set($db->qn('id') . ' = 99999')
				->where($db->qn('id') . ' = 0')
		)->execute();
		$db->setQuery(
			$db->getQuery(true)
				->update('#__assets')
				->set($db->qn('name') . ' = ' . $db->q('com_content.article.99999'))
				->where($db->qn('name') . ' = ' . $db->q('com_content.article.0'))
		)->execute();
	}
}
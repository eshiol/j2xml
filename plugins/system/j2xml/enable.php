<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  System.J2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       3.9
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2022 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
defined('_JEXEC') or die();

/**
 *
 */
class PlgSystemJ2xmlInstallerScript
{

	public function install ($parent)
	{
		// Enable plugin
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update('#__extensions')
			->set($db->quoteName('enabled') . ' = 1')
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('element') . ' = ' . $db->quote('j2xml'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
		$db->setQuery($query);
		$db->execute();
	}
}
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

namespace eshiol\J2xml\Helper;

use Joomla\CMS\Factory as JFactory;

class Joomla {
	// Create alias class for original call in $filepath, then overload the class
	public static function makeAlias($filepath, $originClassName, $aliasClassName)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'plg_system_j2xml'));
		\JLog::add(new \JLogEntry($filepath, \JLog::DEBUG, 'plg_system_j2xml'));
		\JLog::add(new \JLogEntry($originClassName, \JLog::DEBUG, 'plg_system_j2xml'));
		\JLog::add(new \JLogEntry($aliasClassName, \JLog::DEBUG, 'plg_system_j2xml'));

		if (!is_file($filepath)) return false;

		$code = file_get_contents($filepath);
		$code = str_replace('class ' . $originClassName, 'class ' . $aliasClassName, $code);
		eval('?>'. $code);
		return true;
	}
}

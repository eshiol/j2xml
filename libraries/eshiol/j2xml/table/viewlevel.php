<?php
/**
 * @version		15.9.269 libraries/eshiol/j2xml/table/viewlevel.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		15.3.248
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
defined('_JEXEC') or die('Restricted access');

/**
* Viewlevel Table class
*/
class eshTableViewlevel extends eshTable
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(& $db) {
		parent::__construct('#__viewlevels', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML($mapKeysToText = false)
	{
		JLog::add(new JLogEntry(__METHOD__,JLOG::DEBUG,'lib_j2xml'));
		$this->_excluded = array_merge($this->_excluded, array('rules'));
		$this->_aliases['rule']='SELECT g.title FROM #__j2xml_usergroups g WHERE g.id IN '.str_replace(array("[","]"),array("(",")"),$this->rules);

		return parent::_serialize();
	}
}

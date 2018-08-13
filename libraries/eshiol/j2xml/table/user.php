<?php
/**
 * @version		18.8.308 libraries/eshiol/j2xml/table/user.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.5.3beta4.39
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
* User Table class
* @since 		1.5.3beta4.39
*/
class eshTableUser extends eshTable
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.5.3beta4.39
	 */
	function __construct(& $db) {
		parent::__construct('#__users', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML($mapKeysToText = false)
	{
		$this->_aliases['group'] = 'SELECT g.title FROM #__j2xml_usergroups g, #__user_usergroup_map m WHERE g.id = m.group_id AND m.user_id = '.(int)$this->id;
		$this->_aliases['field'] = 'SELECT f.name, v.value FROM #__fields_values v, #__fields f WHERE f.id = v.field_id AND v.item_id = '. (int)$this->id;

		return parent::_serialize();
	}
}

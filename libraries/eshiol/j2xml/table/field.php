<?php
/**
 * @version		17.6.299 libraries/eshiol/j2xml/table/field.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		17.6.299
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
* Field Table class
* 
* @since 17.6.299
*/
class eshTableField extends eshTable
{
	/**
	 * Constructor
	 * 
	 * @param object $db	Database connector
	 * 
	 * @since 17.6.299
	 */
	function __construct(&$db) {
		parent::__construct('#__fields', 'id', $db);
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see eshTable::toXML()
	 */
	function toXML($mapKeysToText = false)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		$this->_excluded = array_merge($this->_excluded, array('group_id'));
		$this->_aliases['category'] = 'SELECT c.path FROM #__categories c, #__fields_categories fc WHERE c.id = fc.category_id AND fc.field_id ='.(int)$this->id;  

		return parent::_serialize();
	}
}

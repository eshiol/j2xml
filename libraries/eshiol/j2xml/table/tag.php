<?php
/**
 * @version		15.3.248 libraries/eshiol/j2xml/table/tag.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		14.8.240
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2015 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
* Tag Table class
*/
class eshTableTag extends eshTable
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(& $db) {
		parent::__construct('#__tags', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML($mapKeysToText = false)
	{
		$xml = ''; 
		
		// Initialise variables.
		$xml = array();
		
		// Open root node.
		$xml[] = '<tag>';
		
		$xml[] = parent::_serialize(
			array('parent_id','lft','rgt','level','checked_out','checked_out_time','created_user_id','modified_user_id'), 
			array(
				'created_by'=>'SELECT username FROM #__users WHERE id = '.(int)$this->created_user_id,
				'modified_by'=>'SELECT username modified_by FROM #__users WHERE id = '.(int)$this->modified_user_id,
				'access'=>'SELECT IF(f.id<=6,f.id,f.title) FROM #__viewlevels f RIGHT JOIN #__tags a ON f.id = a.access WHERE a.id = '. (int)$this->id,
			),
			array()
		); // $excluded,$aliases,$jsons

		// Close root node.
		$xml[] = '</tag>';
						
		// Return the XML array imploded over new lines.
		return implode("\n", $xml);
	}
}

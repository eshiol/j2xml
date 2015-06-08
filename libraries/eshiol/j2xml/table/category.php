<?php
/**
 * @version		15.3.248 libraries/eshiol/j2xml/table/category.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.5.1
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
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

class eshTableCategory extends eshTable
{
	/**
	* @param database A database connector object
	*/
	function __construct(&$db)
	{
		parent::__construct('#__categories', 'id', $db);
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
		$xml[] = '<category>';
		
		$xml[] = parent::_serialize( // $excluded,$aliases,$jsons
			// excluded
			array('asset_id','parent_id','lft','rgt','level','checked_out','checked_out_time'), 
			// renamed
			array(
				'created_user_id'=>'SELECT username created_by FROM #__users WHERE id = '.(int)$this->created_user_id,
				'modified_user_id'=>'SELECT username modified_by FROM #__users WHERE id = '.(int)$this->modified_user_id,
				'access'=>'SELECT IF(f.id<=6,f.id,f.title) FROM #__viewlevels f RIGHT JOIN #__categories a ON f.id = a.access WHERE a.id = '. (int)$this->id,
			), 
			array() //'params', 'metadata')
			);

		// Close root node.
		$xml[] = '</category>';
						
		// Return the XML array imploded over new lines.
		return implode("\n", $xml);
	}
}

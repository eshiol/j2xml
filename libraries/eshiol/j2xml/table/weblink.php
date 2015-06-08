<?php
/**
 * @version		15.3.248 libraries/eshiol/j2xml/table/weblink.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.5.3beta3.38
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
* Weblink Table class
*/
class eshTableWeblink extends eshTable
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.5.3beta3.38
	 */
	function __construct(& $db) {
		parent::__construct('#__weblinks', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML($mapKeysToText = false)
	{
		// Initialise variables.
		$xml = array();
		
		// Open root node.
		$xml[] = '<weblink>';

		$xml[] = parent::_serialize( // $excluded,$aliases,$jsons
			array('checked_out','checked_out_time'), 
			array(
				'catid'=>'SELECT path FROM #__categories WHERE id = '.(int)$this->catid,
				'created_by'=>'SELECT username FROM #__users WHERE id = '.(int)$this->created_by,
				'modified_by'=>'SELECT username modified_by FROM #__users WHERE id = '.(int)$this->modified_by,
				'access'=>'SELECT IF(f.id<=6,f.id,f.title) FROM #__viewlevels f RIGHT JOIN #__weblinks a ON f.id = a.access WHERE a.id = '. (int)$this->id,
			),
			array() //'attribs', 'metadata', 'images', 'urls')
			);

		// Close root node.
		$xml[] = '</weblink>';
				
		// Return the XML array imploded over new lines.
		return implode("\n", $xml);
	}
}

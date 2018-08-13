<?php
/**
 * @version		17.1.294 libraries/eshiol/j2xml/table/menutype.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		17.1.294
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
* Menutype Table class
* 
* @since 17.1.294
*/
class eshTableMenutype extends eshTable
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 17.1.294
	 */
	function __construct(& $db) {
		parent::__construct('#__menu_types', 'id', $db);
	}
}

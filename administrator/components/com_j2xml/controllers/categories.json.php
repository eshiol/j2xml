<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

require_once dirname(__FILE__) . '/json.php';

/**
 * Categories controller class.
 *
 * @since 3.1.111
 */
class J2xmlControllerCategories extends J2xmlControllerJson
{
	/**
	 * The _context for persistent state.
	 *
	 * @var string
	 * @since 3.1.112
	 */
	protected $_context = 'j2xml.categories';

}

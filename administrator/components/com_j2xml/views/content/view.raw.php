<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2021 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

require_once dirname(__FILE__) . '/../raw.php';

/**
 * J2XML Component Content RAW View
 *
 * @since 3.6.161
 */
class J2xmlViewContent extends J2xmlView
{

	/**
	 * Constructor
	 *
	 * @param array $config
	 *			A named configuration array for object construction.
	 *			name: the name (optional) of the view (defaults to the view class name suffix).
	 *			charset: the character set to use for display
	 *			escape: the name (optional) of the function to use for escaping strings
	 *			base_path: the parent path (optional) of the views directory (defaults to the component folder)
	 *			template_plath: the path (optional) of the layout directory (defaults to base_path + /views/ + view name
	 *			helper_path: the path (optional) of the helper files (defaults to base_path + /helpers/)
	 *			layout: the layout (optional) to use to display the view
	 */
	public function __construct($config = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		parent::__construct($config);

		$jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');

		$this->params->loadArray($jform);
	}
}

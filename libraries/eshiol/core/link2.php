<?php
/**
 * @package		J2XML
 * @subpackage	lib_eshiol
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2020 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('JPATH_PLATFORM') or die;

/**
 * Renders a link button
 *
 * @version __DEPLOY_VERSION__
 * @since 3.0
 */
class JToolbarButtonLink2 extends JToolbarButton
{
	/**
	 * Button type
	 * @var    string
	 */
	protected $_name = 'Link2';

	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string  $type  Unused string.
	 * @param   string  $name  Name to be used as apart of the id
	 * @param   string  $text  Button text
	 * @param   string  $url   The link url
	 *
	 * @return  string  HTML string for the button
	 *
	 * @since   3.0
	 */
	public function fetchButton($type = 'Link2', $name = 'back', $text = '', $url = null)
	{
		return '<button onclick="window.open(\''.$this->_getCommand($url).'\');" '
			.'class="btn btn-small">'
			.'<span class="'.$this->fetchIconClass($name).'"></span> '
			.JText::_($text).'</button>';
	}

	/**
	 * Get the button CSS Id
	 *
	 * @param   string  $type  The button type.
	 * @param   string  $name  The name of the button.
	 *
	 * @return  string  Button CSS Id
	 *
	 * @since   3.0
	 */
	public function fetchId($type = 'Link', $name = '')
	{
		return $this->_parent->getName() . '-' . $name;
	}

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   object  $url  Button definition
	 *
	 * @return  string  JavaScript command string
	 *
	 * @since   3.0
	 */
	protected function _getCommand($url)
	{
		return $url;
	}
}

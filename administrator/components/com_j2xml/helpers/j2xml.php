<?php
/**
 * @version		3.3.156 administrator/components/cm_j2xml/helpers/j2xml.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		2.5.85
 * 
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Content component helper.
 */
class J2XMLHelper
{
	public static $extension = 'com_j2xml';

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return	JObject
	 * @since	2.5
	 */
	public static function getActions()
	{
		$user	= JFactory::getUser();
		$result	= new JObject;

		$assetName = 'com_content';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action) {
			$result->set($action,	$user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * @return	boolean
	 * @since	2.5
	 */
	public static function updateReset()
	{
		return true;
	}

	public static function copyright()
	{
		if ($xml = JFactory::getXML(JPATH_COMPONENT_ADMINISTRATOR.'/j2xml.xml'))
		{
			return
			'<div class="clearfix"> </div>'.
			'<div style="text-align:center;font-size:xx-small">'.
			JText::_($xml->name).' '.$xml->version.' '.str_replace('(C)', '&copy;', $xml->copyright).
			'</div>';
		}
	}

	/**
	 * Configure the Linkbar.
	 *
	 * @param   string	The name of the active view.
	 */
	public static function addSubmenu($vName = 'cpanel')
	{
		JHtmlSidebar::addEntry(
				JText::_('COM_J2XML_SUBMENU_CPANEL'),
				'index.php?option=com_j2xml&view=cpanel',
				$vName == 'cpanel'
		);
		JHtmlSidebar::addEntry(
				JText::_('COM_J2XML_SUBMENU_WEBSITES'),
				'index.php?option=com_j2xml&view=websites',
				$vName == 'websites'
		);
	}

	/**
	 * Removes invalid XML
	 *
	 * @access public
	 * @param string $value
	 * @return string
	 */
	static function stripInvalidXml($value)
	{
	    $ret = "";
	    $current;
	    if (empty($value)) 
	    {
	        return $ret;
	    }

	    $length = strlen($value);
	    for ($i=0; $i < $length; $i++)
	    {
	        $current = ord($value{$i});
	        if (($current == 0x9) ||
	            ($current == 0xA) ||
	            ($current == 0xD) ||
	            (($current >= 0x20) && ($current <= 0xD7FF)) ||
	            (($current >= 0xE000) && ($current <= 0xFFFD)) ||
	            (($current >= 0x10000) && ($current <= 0x10FFFF)))
	        {
	            $ret .= chr($current);
	        }
	        else
	        {
	            $ret .= " ";
	        }
	    }
	    return $ret;
	}
}

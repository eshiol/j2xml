<?php
/**
 * @version		16.11.23 libraries/eshiol/core/send.php
 * 
 * @package		J2XML
 * @subpackage	plg_system_j2xml
 * @since		1.5.2
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License 
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

/**
 * Renders a send button
 */
class JToolbarButtonSend extends JToolbarButton
{
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'Send';

	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string   $type  Unused string.
	 * @param   string   $name  The name of the button icon class.
	 * @param   string   $text  Button text.
	 * @param   string   $task  Task associated with the button.
	 * @param   boolean  $list  True to allow lists
	 *
	 * @return  string  HTML string for the button
	 *
	 * @since   3.0
	 */
	public function fetchButton($type = 'Send', $name = '', $text = '', $urls = array(), $list = true)
	{
		$i18n_text = JText::_($text);
		$class = $this->fetchIconClass($name);

		if ($name == "apply" || $name == "new")
		{
			$btnClass = "btn btn-small btn-success";
			$iconWhite = "icon-white";
		}
		else
		{
			$btnClass = "btn btn-small";
			$iconWhite = "";
		}

		if (is_array($urls))
		{
			$html  = "<div class=\"btn-group\">\n";
			$html .= "	<button class=\"btn btn-small dropdown-toggle\" data-toggle=\"dropdown\"><i class=\"icon-{$name}\"> </i> <span>{$i18n_text}</span> <i class=\"caret\"> </i></button>\n";
			$html .= "	<ul class=\"dropdown-menu\">\n";
			for ($i = 0; $i < count($urls); $i++)
			{
				$doTask = $this->_getCommand($name, $urls[$i]->title, $urls[$i]->url, $list);
				$html .= "		<li><a href=\"#\" onclick=\"{$doTask}\">{$urls[$i]->title}</a></li>\n";
			}
			$html .= "	</ul>\n";
			$html .= "</div>\n";
		}			
		else
		{
			$doTask = $this->_getCommand($name, $i18n_text, $urls->url, $list);
			$html = "<button href=\"#\" onclick=\"$doTask\" class=\"" . $btnClass . "\">\n";
			$html .= "<i class=\"$class $iconWhite\">\n";
			$html .= "</i>\n";
			$html .= "<span>{$i18n_text}</span>\n";
			$html .= "</button>\n";
		}
				
		return $html;
	}

	/**
	 * Get the button CSS Id
	 *
	 * @param   string   $type      Unused string.
	 * @param   string   $name      Name to be used as apart of the id
	 * @param   string   $text      Button text
	 * @param   string   $task      The task associated with the button
	 * @param   boolean  $list      True to allow use of lists
	 * @param   boolean  $hideMenu  True to hide the menu on click
	 *
	 * @return  string  Button CSS Id
	 *
	 * @since   3.0
	 */
	public function fetchId($type = 'Send', $name = '', $text = '', $task = '', $list = true, $hideMenu = false)
	{
		return $this->_parent->getName() . '-' . $name;
	}

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   string   $name  The task name as seen by the user
	 * @param   string   $task  The task used by the application
	 * @param   boolean  $list  True is requires a list confirmation.
	 *
	 * @return  string   JavaScript command string
	 *
	 * @since   3.0
	 */
	protected function _getCommand($name, $text, $url, $list)
	{
		JHtml::_('behavior.framework');
		$message = JText::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
		$message = addslashes($message);

		$token = JSession::getFormToken();
		$url = base64_encode("{$url}&format=json&{$token}=1");
		if ($list)
			$cmd = "
			if (document.adminForm.boxchecked.value==0)
				alert('{$message}');
			else 
				eshiol.sendAjax('{$name}', '{$text}', '{$url}')";
		return $cmd;
	}
}

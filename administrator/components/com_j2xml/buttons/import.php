<?php
/**
 * @version		3.3.142 administrator/components/com_j2xml/buttons/import.php
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.0
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
defined('_JEXEC') or die('Restricted access.');

class JToolbarButtonImport extends JToolbarButton
{
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'Import';

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
	public function fetchButton($type='Import', $name = '', $text1 = '', $text2 = '', $task = 'import', $list = true)
	{
		$doAction = 'index.php?option=com_j2xml&amp;task='.$task;
		$doTask	= $this->_getCommand($name, $task, $list);
		
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

		JFactory::getDocument()->addStyleDeclaration('div#toolbar div#toolbar-'.$name.' button.btn i.icon-'.$name.'-import::before {color: #2F96B4;content: "g";}');
		JFactory::getDocument()->addStyleDeclaration('div#toolbar div#toolbar-'.$name.' button.btn i.icon-'.$name.'-open::before {color: #2F96B4;content: "r";}');
		JFactory::getDocument()->addStyleDeclaration('div#toolbar div#toolbar-'.$name.' button.btn i.icon-'.$name.'-model::before {color: #2F96B4;content: "-";}');
			
		$html = "";
		$html .= "<form name=\"".$name."Form\" method=\"post\" enctype=\"multipart/form-data\" action=\"$doAction\" style=\"margin:0\">\n";
		
		$i18n_text	= JText::_($text2);
		$class	= $this->fetchIconClass($name.'-import');
		$html .="			
		<div class=\"btn-group input-append\" style=\"margin-bottom:0px\">
			<input type=\"file\" name=\"file_upload\" class=\"js-stools-search-string\" />
			<input type=\"text\" name=\"remote_file\" placeholder=\"URL\" value=\"\" style=\"line-height:14px;height:14px;display:none\">		
			<button title=\"\" class=\"btn btn-small hasTooltip\" data-toggle=\"dropdown\">
				<i class=\"caret\" style=\"margin-bottom:0\"></i>
			</button>
			<ul class=\"dropdown-menu\">
				<li><a href=\"#\" onclick=\"javascript:document.j2xmlForm.file_upload.style.display='';document.j2xmlForm.remote_file.style.display='none';\">Local file</a></li>
				<li><a href=\"#\" onclick=\"javascript:document.j2xmlForm.file_upload.style.display='none';document.j2xmlForm.remote_file.style.display='';\">URL</a></li>
			</ul>
			<button title=\"Import\" class=\"btn btn-small hasTooltip\" type=\"submit\" data-original-title=\"$i18n_text\">
				<i class=\"icon-j2xml-import\"></i> $i18n_text
			</button>
		</div>
		";
		
		$html .= JHTML::_('form.token');
		$html .= "</form> \n";
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
	public function fetchId($type = 'Import', $name = '', $text = '', $task = '', $list = true, $hideMenu = false)
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
	private function _getCommand($name, $task, $list)
	{
		$todo		= JString::strtolower(JText::_($name));
		$message	= JText::sprintf('COM_J2XML_BUTTON_PLEASE_SELECT_A_FILE_TO', $todo);
		$message	= addslashes($message);
	
		return "javascript:if((document.".$name."Form.file_upload.value=='') && (document.".$name."Form.remote_file.value=='http://')){alert('$message');}else{ document.".$name."Form.submit()}";
	}
}

<?php
/**
 * @version		14.2.10 libraries/eshiol/core/sendbydate.php
 * 
 * @package		J2XML
 * @subpackage	plg_system_j2xml
 * @since		14.2.10
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2014 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License 
 * or other free or open source software licenses.
 */
 
defined('JPATH_PLATFORM') or die;

/**
 * Renders a sendbydate button
 */
class JToolbarButtonSendbydate extends JToolbarButtonSend
{
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'SendByDate';
	
	private $_d1;
	private $_d2;
	
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
	public function fetchButton($type = 'Send', $name = '', $text = '', $urls = array(), $list = true, $d1 = null, $d2 = null)
	{
		$this->_d1 = $d1;
		$this->_d2 = $d2;
		return parent::fetchButton($type,$name,$text,$urls,$list);
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
		$app = JFactory::getApplication();
		$d1 = $this->_d1; //->format('Y/m/d');
		$d2 = $this->_d2; //->format('Y/m/d');
		
		$token = JSession::getFormToken();
		$url = base64_encode("{$url}&format=json&{$token}=1");
		if ($list)
			$cmd = "
			if (document.adminForm.boxchecked.value==0)
				alert('{$message}');
			else 
				eshiol.sendAjaxByDate('{$name}', '{$text}', '{$url}', '{$d1}', '{$d2}')";
		return $cmd;
	}
}
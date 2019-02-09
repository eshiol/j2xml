<?php
/**
 * @version		16.2.20 libraries/eshiol/core/sendbydate.php
 * 
 * @package		J2XML
 * @subpackage	plg_system_j2xml
 * @since		14.2.10
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
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
class JToolbarButtonSendbydate extends JToolbarButton
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
	public function fetchButton($type = 'Sendbydate', $name = '', $text = '', $urls = array(), $list = true, $d1 = null, $d2 = null)
	{
		$this->_d1 = $d1;
		$this->_d2 = $d2;
		$i18n_text = JText::_($text);
		$class = $this->fetchIconClass($name);

		$doc = JFactory::getDocument();
		$doc->addStyleDeclaration("
#{$name}_begin_img { width:35px!important; margin-left:0px; }
#{$name}_end_img { width:35px!important; margin-left:0px; }
");
		$doc->addScriptDeclaration("
jQuery(window).on('resize', function(){
	width = jQuery(window).width();
	if (width < 480)
		width = width - 150;
	else
		width = 137;
	jQuery('#{$name}_button').width(width);
});
");

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

		$d1_field = JHtmlJ2XML::calendar2($d1, $name.'_begin', $name.'_begin', '%Y/%m/%d', array('style' => 'display:none', 'class' => 'btn-small'));
		$d2_field = JHtmlJ2XML::calendar2($d2, $name.'_end', $name.'_end', '%Y/%m/%d', array('style' => 'display:none', 'class' => 'btn-small'));

		$html  = "<div class=\"btn-group\">\n";
		if (is_array($urls))
		{
			$html .= "	<button id=\"{$name}_button\" class=\"btn btn-small dropdown-toggle\" data-toggle=\"dropdown\"><i class=\"icon-{$name}\"> </i> <span>{$i18n_text}</span> <i class=\"caret\"> </i>\n";
			$html .= "  ".$d1_field."\n";
			$html .= "  ".$d2_field."\n";
			$html .= "  </button>\n";
			$html .= "	<ul class=\"dropdown-menu\">\n";
			for ($i = 0; $i < count($urls); $i++)
			{
				$doTask = $this->_getCommand($name, $urls[$i]->title, $urls[$i]->url, $list);
				$html .= "		<li><a href=\"#\" onclick=\"{$doTask}\">{$urls[$i]->title}</a></li>\n";
			}
			$html .= "	</ul>\n";
		}
		else
		{
			$doTask = $this->_getCommand($name, $i18n_text, $urls->url, $list);
			$html .= "<button id=\"{$name}_button\" href=\"#\" onclick=\"$doTask\" class=\"" . $btnClass . "\">\n";
			$html .= "<i class=\"$class $iconWhite\">\n";
			$html .= "</i>\n";
			$html .= "<span>{$i18n_text}</span>\n";
			$html .= "</button>\n";
			$html .= $d1_field."\n";
			$html .= $d2_field."\n";
		}
		$html .= "</div>\n";

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
	public function fetchId($type = 'Sendbydate', $name = '', $text = '', $task = '', $list = true, $hideMenu = false)
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
				eshiol.sendAjaxByDate('{$name}', '{$text}', '{$url}');
			";
			return $cmd;
	}
}

abstract class JHtmlJ2XML extends JHtml
{
	/**
	 * Displays a calendar control field
	 *
	 * @param   string  $value    The date value
	 * @param   string  $name     The name of the text field
	 * @param   string  $id       The id of the text field
	 * @param   string  $format   The date format
	 * @param   mixed   $attribs  Additional HTML attributes
	 *
	 * @return  string  HTML markup for a calendar field
	 *
	 * @since   1.5
	 */
	public static function calendar2($value, $name, $id, $format = '%Y-%m-%d', $attribs = null)
	{
		static $done;

		if ($done === null)
		{
			$done = array();
		}

		$readonly = isset($attribs['readonly']) && $attribs['readonly'] == 'readonly';
		$disabled = isset($attribs['disabled']) && $attribs['disabled'] == 'disabled';

		if (is_array($attribs))
		{
			$attribs['class'] = isset($attribs['class']) ? $attribs['class'] : 'input-medium';
			$attribs['class'] = trim($attribs['class'] . ' hasTooltip');

			$attribs = JArrayHelper::toString($attribs);
		}

		static::_('bootstrap.tooltip');

		// Format value when not nulldate ('0000-00-00 00:00:00'), otherwise blank it as it would result in 1970-01-01.
		if ($value && $value != JFactory::getDbo()->getNullDate())
		{
			$tz = date_default_timezone_get();
			date_default_timezone_set('UTC');
			$inputvalue = strftime($format, strtotime($value));
			date_default_timezone_set($tz);
		}
		else
		{
			$inputvalue = '';
		}

		// Load the calendar behavior
		static::_('behavior.calendar');

		// Only display the triggers once for each control.
		if (!in_array($id, $done))
		{
			$document = JFactory::getDocument();
			$document
			->addScriptDeclaration(
					'jQuery(document).ready(function($) {Calendar.setup({
			// Id of the input field
			inputField: "' . $id . '",
			// Format of the input field
			ifFormat: "' . $format . '",
			// Trigger for the calendar (button ID)
			button: "' . $id . '_img",
			// Alignment (defaults to "Bl")
			align: "Tl",
			singleClick: true,
			firstDay: ' . JFactory::getLanguage()->getFirstDay() . '
			});});'
					);
			$done[] = $id;
		}

		// Hide button using inline styles for readonly/disabled fields
		$btn_style = ($readonly || $disabled) ? ' style="display:none;"' : '';
		$div_class = (!$readonly && !$disabled) ? ' class="input-append"' : '';

		return '<input type="text" title="' . ($inputvalue ? static::_('date', $value, null, null) : '')
		. '" name="' . $name . '" id="' . $id . '" value="' . htmlspecialchars($inputvalue, ENT_COMPAT, 'UTF-8') . '" ' . $attribs . ' />'
				. '<button type="button" class="btn btn-small" id="' . $id . '_img"' . $btn_style . '><span class="icon-calendar"></span></button>'
						;
	}
}

<?php
/**
 * @version		16.11.25 libraries/eshiol/core/file.php
 * @package		J2XML
 * @subpackage	lib_eshiol
 * @since		14.9.11
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

// no direct access
defined('_JEXEC') or die('Restricted access.');

class JToolbarButtonFile extends JToolbarButton
{
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'File';

	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string   $type     Unused string, formerly button type.
	 * @param   string   $name     Modal name, used to generate element ID
	 * @param   string   $text     The link text
	 * @param   integer  $width    Width of popup
	 * @param   integer  $height   Height of popup
	 * @param   string   $onClose  JavaScript for the onClose event.
	 * @param   string   $title    The title text
	 * @param   string   $filter   ['xml,gz'] The file type filter
	 * @param	mixed    $plugins  The plugins (string or array)
	 * @param	string	 $ajax     The javascript code to implement the ajax request
	 *
	 * @return  string  HTML string for the button
	 *
	 * @since   3.0
	 */
	public function fetchButton($type='File', $name = 'File', $open = 'Open', $upload = 'Upload', $task = 'file', $width = 640, $height = 480, $onClose = '', $filter = 'xml,gz', $plugins = null, $ajax = null)
	{
		$jce = file_exists(JPATH_ADMINISTRATOR.'/components/com_jce/helpers/browser.php');
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$default_editor = JFactory::getConfig()->get('editor');
		$editor = $user->getParam('editor', $default_editor);
		$jce = ($editor == 'jce');

		$doc = JFactory::getDocument();
/*
		$doc->addStyleDeclaration("#{$name}_local1 {margin-bottom:0;height:14px;}");
		$doc->addStyleDeclaration("#{$name}_url    {margin-bottom:0;height:14px;}");
		$doc->addStyleDeclaration("#{$name}_server {margin-bottom:0;height:14px;}");
*/
		$doc->addStyleDeclaration("
#{$name}Form .file-button[type=\"text\"] {
	margin-bottom:0px;
	height:14px;
}
@media (max-width: 480px) {
#{$name}Form .file-button[type=\"text\"] {
	margin-left:10px;
}
#{$name}Form .btn { width:32px!important; margin-left:0px; }
#{$name}Form .btn span { display:none; }
}
");
		$doc->addScriptDeclaration("
jQuery(window).on('resize', function(){
	width = jQuery(window).width();
	if (width < 480)
		width = width - 150;
	else
		width = 137;
	jQuery('#{$name}_local1').width(width);
	jQuery('#{$name}_url').width(width + 32);
	jQuery('#{$name}_server').width(width + 32);
});
");

		// Store all data to the options array for use with JLayout
		$class = $this->fetchIconClass($name);
		$title = JText::_($name);
		$open = JText::_($open);
		$upload = JText::_($upload);

		$component = substr($task, 0, ($i = strpos($task, '.')));
		$task = substr($task, $i + 1);
		$doAction = "index.php?option=com_$component&amp;task=$task";

		$html = array();
		$html[] = "<form id=\"".$name."Form\" name=\"".$name."Form\" method=\"post\" enctype=\"multipart/form-data\" action=\"$doAction\" style=\"margin:0\">\n";

		$html[] = "<div class=\"btn-group input-append\" style=\"margin:0;\">";

		$html[] = "<input type=\"hidden\" id=\"".$name."_filetype\"  name=\"".$name."_filetype\" value=\"1\" />";
		$html[] = "<input type=\"file\" id=\"".$name."_local\"  name=\"".$name."_local\" />";
		$html[] = "<input type=\"text\" id=\"".$name."_local1\"                           class=\"file-button\" placeholder=\"No selected file...\" />";
		$html[] = "<input type=\"text\" id=\"".$name."_url\"    name=\"".$name."_url\"    class=\"file-button\" placeholder=\"URL\" />";
		$html[] = "<input type=\"text\" id=\"".$name."_server\" name=\"".$name."_server\" class=\"file-button\" placeholder=\"Server\" value=\"\" />";

		$html[] = "<button title=\"\" class=\"btn btn-small hasTooltip\" data-toggle=\"dropdown\" id=\"".$name."_type\">";
		$html[] = "<i class=\"caret\" style=\"margin-bottom:0\"></i>";
		$html[] = "</button>";
		$html[] = "<ul class=\"dropdown-menu\">";

		$html[] = "<li><a href=\"#\" onclick=\"$('".$name."_local1').style.display='';
			$('".$name."_local_open').style.display='';
			$('".$name."_url').style.display='none';
			$('".$name."_server').style.display='none';
			".($jce?"$('".$name."_server_open').style.display='none';":"")."
			$('".$name."_filetype').value=1;
			\">Local</a></li>";
		$html[] = "<li><a href=\"#\" onclick=\"$('".$name."_local1').style.display='none';
			$('".$name."_local_open').style.display='none';
			$('".$name."_url').style.display='';
			$('".$name."_server').style.display='none';
			".($jce?"$('".$name."_server_open').style.display='none';":"")."
			$('".$name."_filetype').value=2;
			\">URL</a></li>";
		$html[] = "<li><a href=\"#\" onclick=\"$('".$name."_local1').style.display='none';
			$('".$name."_local_open').style.display='none';
			$('".$name."_url').style.display='none';
			$('".$name."_server').style.display='';
			".($jce?"$('".$name."_server_open').style.display='';":"")."
			$('".$name."_filetype').value=3;
			\">Server</a></li>";
		$html[] = "</ul>";

		$html[] = "<button id=\"".$name."_local_open\" onclick=\"javascript:".$name."_local.click();return false;\" class=\"btn btn-small\" >";
		$html[] = "<i class=\"icon-folder\"></i>";
		$html[] = "<span> $open</span>";
		$html[] = "</button>";

		if ($jce)
		{
			require_once(JPATH_ADMINISTRATOR.'/components/com_jce/helpers/browser.php');
			$doTask = JUri::base().WFBrowserHelper::getBrowserLink($name.'_server', $filter);
			$html[] = "<button id=\"{$name}_server_open\" onclick=\"{$doTask}\" class=\"btn btn-small modal\" data-toggle=\"modal\" data-target=\"#{$name}_server_modal\" >";
			$html[] = "<i class=\"icon-folder\"></i>";
			$html[] = "<span> $open</span>";
			$html[] = "</button>";

			// Place modal div and scripts in a new div
			$html[] = '<div class="btn-group" style="width: 0; margin: 0">';
			// Build the options array for the modal
			$params = array();
			$params['title']  = $title;
			$params['url']    = $doTask;
			$params['height'] = $height;
			$params['width']  = $width;
			$html[] = JHtml::_('bootstrap.renderModal', $name.'_server_modal', $params);
			$html[] = '</div>';
		}

		if (is_array($plugins) && (count($plugins) > 0))
		{
			if (count($plugins) > 1)
			{
				$html[] = "</div>";
				$html[] = "<div class=\"btn-group input-append\" style=\"margin:0;\">";
				$i = 0;
				foreach ($plugins as $v => $n)
					if (strlen($v) > $i)
						$i = strlen($v);
				$html[] = "<input type=\"text\" id=\"".$name."_plugin\" class=\"btn btn-small input-append\" name=\"".$name."_plugin\" value=\"".key($plugins)."\" readonly=\"readonly\" style=\"width:".$i."em\" />";
				$html[] = "<button title=\"\" class=\"btn btn-small hasTooltip\" data-toggle=\"dropdown\">";
				$html[] = "<i class=\"caret\" style=\"margin-bottom:0\"></i>";
				$html[] = "</button>";
				$html[] = "<ul class=\"dropdown-menu\">";
				foreach ($plugins as $v => $n)
					$html[] = "<li><a href=\"#\" onclick=\"$('".$name."_plugin').value='$v';\">$n</a></li>";
				$html[] = "</ul>";
			}
			else
				$html[] = "<input type=\"hidden\" id=\"".$name."_plugin\" name=\"".$name."_plugin\" value=\"".key($plugins)."\" />";
		}

		$html[] = '<script>
		var jQuery;
		(function ($) {
			$(document).ready(function () {
				$(\'#'.$name.'_local\').on(\'change\', function () {
					$(\'#'.$name.'_local1\').val(this.files[0].name);
				});
				$(\'#'.$name.'_local\').hide();
				$(\'#'.$name.'_local1\').on(\'click\', function () {
					$(\'#'.$name.'_local_open\').click();
					return false;
				});
				$(\'#'.$name.'_local1\').attr(\'readonly\',\'readonly\');
				$(\'#'.$name.'_url\').hide();
			});
		})(jQuery);
		</script>';

//		if ($jce)
		$html[] = '<script>
		var jQuery;
		(function ($) {
			$(document).ready(function () {
//				$(\'#'.$name.'_server\').attr(\'readonly\',\'readonly\');
				$(\'#'.$name.'_server\').hide();
				$(\'#'.$name.'_server_open\').hide();
					'.(strlen($onClose) >= 1 ? '$(\'#'.$name.'_server_modal\').on(\'hide\', function () {'.$onClose.';});' : '').'
//				$(\'#'.$name.'_server\').on(\'click\', function () {
//					$(\'#'.$name.'_server_open\').click();
//					return false;
//				});
			});
		})(jQuery);
		</script>';

		$html[] = '<script>
		var jQuery;
		(function ($) {
			$(document).ready(function () {
//				$(\'#'.$name.'_server\').attr(\'readonly\',\'readonly\');
				$(\'#'.$name.'_server\').hide();
				$(\'#'.$name.'_server_open\').hide();
					'.(strlen($onClose) >= 1 ? '$(\'#'.$name.'_server_modal\').on(\'hide\', function () {'.$onClose.';});' : '').'
//				$(\'#'.$name.'_server\').on(\'click\', function () {
//					$(\'#'.$name.'_server_open\').click();
//					return false;
//				});
			});
		})(jQuery);
		</script>';

		$html[] = "<button title=\"{$title}\" class=\"btn btn-small hasTooltip\" type=\"submit\" id=\"{$name}_upload\"".
			($ajax ? " onclick=\"return !{$ajax}('{$name}', '{$doAction}');\"" : "")
			." data-original-title=\"{$title}\">";
		$html[] = "<i class=\"icon-upload\"></i>";
		$html[] = "<span> {$upload}</span>";
		$html[] = "</button>";

		$html[] = '</div>';
		$html[] = JHTML::_('form.token');
		$html[] = '</form>';

		return implode("\n", $html);
	}

	/**
	 * Get the button id
	 *
	 * @param   string  $type  Button type
	 * @param   string  $name  Button name
	 *
	 * @return  string	Button CSS Id
	 *
	 * @since   3.0
	 */
	public function fetchId($type, $name)
	{
		return $this->_parent->getName() . '-file-' . $name;
	}

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   string  $url  URL for popup
	 *
	 * @return  string  JavaScript command string
	 *
	 * @since   3.0
	 */
	private function _getCommand($url)
	{
		if (substr($url, 0, 4) !== 'http')
		{
			$url = JUri::base() . $url;
		}

		return $url;
	}
}
				
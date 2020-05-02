<?php
/**
 * @package		Joomla.Libraries
 * @subpackage	eshiol.Core
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2020 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
defined('_JEXEC') or die();

/**
 * Renders a send button
 *
 * @version __DEPLOY_VERSION__
 * @since 1.5.2
 */
if (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge'))
{

	class JToolbarButtonSend extends JToolbarButton
	{

		/**
		 * Button type
		 *
		 * @var string
		 */
		protected $_name = 'Send';

		/**
		 * Fetch the HTML for the button
		 *
		 * @param string $type
		 *        	Unused string.
		 * @param string $name
		 *        	The name of the button icon class.
		 * @param string $text
		 *        	Button text.
		 * @param string $task
		 *        	Task associated with the button.
		 * @param boolean $list
		 *        	True to allow lists
		 *        	
		 * @return string HTML string for the button
		 *        
		 * @since 3.0
		 */
		public function fetchButton ($type = 'Send', $name = '', $text = '', $urls = array(), $list = true)
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

			$doc = JFactory::getDocument();
			$min = JFactory::getConfig()->get('debug') ? '' : '.min';
			$doc->addScript("../media/lib_eshiol_core/js/encryption{$min}.js");
			$doc->addScript("../media/lib_eshiol_core/js/core{$min}.js");

			if (is_array($urls))
			{
				$html = "<div class=\"btn-group\">\n";
				$html .= "	<button class=\"btn btn-small dropdown-toggle\" data-toggle=\"dropdown\"><i class=\"icon-{$name}\"> </i> <span>{$i18n_text}</span> <i class=\"caret\"> </i></button>\n";
				$html .= "	<ul class=\"dropdown-menu\">\n";
				for ($i = 0; $i < count($urls); $i ++)
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
		 * @param string $type
		 *        	Unused string.
		 * @param string $name
		 *        	Name to be used as apart of the id
		 * @param string $text
		 *        	Button text
		 * @param string $task
		 *        	The task associated with the button
		 * @param boolean $list
		 *        	True to allow use of lists
		 * @param boolean $hideMenu
		 *        	True to hide the menu on click
		 *        	
		 * @return string Button CSS Id
		 *        
		 * @since 3.0
		 */
		public function fetchId ($type = 'Send', $name = '', $text = '', $task = '', $list = true, $hideMenu = false)
		{
			return $this->_parent->getName() . '-' . $name;
		}

		/**
		 * Get the JavaScript command for the button
		 *
		 * @param string $name
		 *        	The task name as seen by the user
		 * @param string $task
		 *        	The task used by the application
		 * @param boolean $list
		 *        	True is requires a list confirmation.
		 *        	
		 * @return string JavaScript command string
		 *        
		 * @since 3.0
		 */
		protected function _getCommand ($name, $text, $url, $list)
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
}
else
{
	jimport('joomla.html.toolbar.button');

	class JButtonSend extends JButton
	{

		/**
		 * Button type
		 *
		 * @var string
		 */
		protected $_name = 'Send';

		/**
		 * Fetch the HTML for the button
		 *
		 * @param string $type
		 *        	Unused string.
		 * @param string $name
		 *        	The name of the button icon class.
		 * @param string $text
		 *        	Button text.
		 * @param string $task
		 *        	Task associated with the button.
		 * @param boolean $list
		 *        	True to allow lists
		 *        	
		 * @return string HTML string for the button
		 *        
		 * @since 12.0.1
		 */
		public function fetchButton ($type = 'Send', $name = '', $text = '', $task = '', $view = '', $list = true)
		{
			$i18n_text = JText::_($text);
			$class = $this->fetchIconClass($name);
			
			// Load the modal behavior script.
			JHTML::_('behavior.framework', true);
			
			if (JFactory::getConfig()->get('debug'))
			{
				$uncompressed = '-uncompressed';
				$min = '';
			}
			else
			{
				$uncompressed = '';
				$min = '.min';
			}

			JHTML::_('script', 'system/modal' . $uncompressed . '.js', true, true);
			JHTML::_('stylesheet', 'media/system/css/modal.css');

			$doc = JFactory::getDocument();

			$doc->addScript("../media/lib_eshiol_core/js/encryption{$min}.js");
			$doc->addScript("../media/lib_eshiol_core/js/core.11{$min}.js");
			$doc->addStyleDeclaration(" .icon-32-waiting {background:url(../media/lib_eshiol_core/images/icon-32-waiting.gif) no-repeat; }");

			$todo = JString::strtolower(JText::_($text));
			$message = JText::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');

			$tmp = explode('.', $task);
			$task = (isset($tmp[2]) ? $tmp[1] . '.' . $tmp[2] : $tmp[1]);
			$url = "index.php?option=com_$tmp[0]&task=$task&format=json";
			$url .= '&' . JUtility::getToken() . '=1';
			$url = base64_encode($url);
			
			$link = JRoute::_(
					"index.php?option=com_{$tmp[0]}&amp;view={$view}&amp;layout=modal&amp;tmpl=component&amp;filter_state=1&amp;field={$name}&amp;url={$url}");
			$rel_handler = "{handler: 'iframe', size: {x: 800, y: 400}}";
			$onclick = "if(document.adminForm.boxchecked.value==0) alert('{$message}'); else SqueezeBox.setContent('iframe',this.href);";
			
			$html = "<a class=\"toolbar\" href=\"{$link}\" rel=\"{$rel_handler}\" onclick=\"{$onclick} return false; \">";
			$html .= "<span class=\"$class\">\n";
			$html .= "</span>\n";
			$html .= "$i18n_text\n";
			$html .= "</a>\n";
			
			return $html;
		}

		/**
		 * Get the button CSS Id
		 *
		 * @param string $type
		 *        	Unused string.
		 * @param string $name
		 *        	Name to be used as apart of the id
		 * @param string $text
		 *        	Button text
		 * @param string $task
		 *        	The task associated with the button
		 * @param boolean $list
		 *        	True to allow use of lists
		 * @param boolean $hideMenu
		 *        	True to hide the menu on click
		 *        	
		 * @return string Button CSS Id
		 *        
		 * @since 12.0.1
		 */
		public function fetchId ($type = 'Send', $name = '', $text = '', $task = '', $list = true, $hideMenu = false)
		{
			return $this->_parent->getName() . '-' . $name;
		}
	}
}
<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5.3
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2022 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

JHtml::_('behavior.framework');

jimport('joomla.html.html.tabs');

/**
 *
 */
class J2XMLViewCpanel extends JViewLegacy
{

	function display ($tpl = null)
	{
		// Trigger the onAfterDispatch event.
		JPluginHelper::importPlugin('j2xml');
		// JFactory::getApplication()->triggerEvent('onAfterDispatch');

		$info = $this->get('Info');
		$this->assignRef('info', $info);
		$params = JComponentHelper::getParams('com_j2xml');
		$this->assignRef('params', $params);

		J2XMLHelper::addSubmenu('cpanel');
		$this->sidebar = JHtmlSidebar::render();

		$this->addToolbar();
		parent::display($tpl);
	}

	function _quickiconButton ($link, $image, $text, $path = null, $target = '', $onclick = '')
	{
		$app = JFactory::getApplication('administrator');
		if ($target != '')
		{
			$target = 'target="' . $target . '"';
		}
		if ($onclick != '')
		{
			$onclick = 'onclick="' . $onclick . '"';
		}
		if ($path === null || $path === '')
		{
			$template = $app->getTemplate();
			$path = '/templates/' . $template . '/images/header/';
		}

		$lang = JFactory::getLanguage();

		if (! class_exists('JPlatform') || version_compare(JPlatform::RELEASE, '12', 'lt'))
		{
			?>
<div style="float:<?php echo ($lang->isRTL()) ? 'right' : 'left'; ?>;">
	<div class="icon">
		<a href="<?php echo $link; ?>" <?php echo $target;?>
			<?php echo $onclick;?>>
					<?php echo JHTML::_('image.administrator', $image, $path, NULL, NULL, $text ); ?>
					<span><?php echo $text; ?></span>
		</a>
	</div>
</div>
<?php
		}
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since 1.6
	 */
	protected function addToolbar ()
	{
		require_once JPATH_COMPONENT . '/helpers/j2xml.php';
		$canDo = j2xmlHelper::getActions();

		$toolbar = JToolBar::getInstance('toolbar');
		$toolbar->addButtonPath(JPATH_COMPONENT . DS . 'buttons');

		JToolBarHelper::title(JText::_('COM_J2XML_TOOLBAR_J2XML'), 'j2xml.png');

		$doc = JFactory::getDocument();
		if ($canDo->get('core.create') || ($canDo->get('core.edit')))
		{
			jimport('eshiol.core.file');

			$min = ($this->params->get('debug', 0) ? '' : '.min');
			$doc->addScript("../media/lib_eshiol_core/js/encryption{$min}.js");
			$doc->addScript("../media/lib_eshiol_core/js/core{$min}.js");
			$doc->addScript("../media/lib_eshiol_core/js/version_compare{$min}.js");

			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('enabled'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('library'));
			$version = new \JVersion();
			if ($version->isCompatible('3.9'))
			{
				$query->where($db->quoteName('element') . ' = ' . $db->quote('eshiol/J2xmlpro'));
			}
			else
			{
				$query->where($db->quoteName('element') . ' = ' . $db->quote('J2xmlpro'));
			}
			$pro = ((bool) $db->setQuery($query)->loadResult()) ? 'pro' : '';
			$doc->addScript("../media/lib_eshiol_j2xml{$pro}/js/j2xml{$min}.js");

			$toolbar = JToolBar::getInstance('toolbar');
			$toolbar->appendButton('File', 'j2xml', 'COM_J2XML_BUTTON_OPEN', 'COM_J2XML_BUTTON_IMPORT', 'j2xml.cpanel.import', 600, 400, null,
					'xml,gz', null, $this->params->get('ajax', 0) ? 'eshiol.j2xml.importer' : null);
			// $params = JComponentHelper::getParams('com_j2xml');
			// $hostname = JFactory::getURI()->getHost();
			$jinput = JFactory::getApplication()->input;
			if (
			// ($params->get('deveopment') &&
			// ($hostname == 'localhost') &&
			($jinput->getCmd('d3v3l0p', '0') === '1'))
			{
				$toolbar->appendButton('Link', 'purge', 'COM_J2XML_CONTENT_DELETE',
						'index.php?option=com_j2xml&task=cpanel.clean&develop=1&' . JSession::getFormToken() . '=1');
			}
			JToolBarHelper::divider();
		}
		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_j2xml');
		}

		$doc->addStyleDeclaration('#toolbar-credit{float:right;}');
		$toolbar->appendButton('Link', 'credit', 'COM_J2XML_DONATE', 'https://www.eshiol.it/' . Jtext::_('COM_J2XML_DONATE_1'));
	}
}
?>
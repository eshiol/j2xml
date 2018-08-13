<?php
/**
 * @version		3.7.176 administrator/components/com_j2xml/models/cpanel/view.html.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.3
 * 
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2018 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('joomla.html.html.tabs');

class J2XMLViewCpanel extends JViewLegacy
{
	function display($tpl = null)
	{
		// Trigger the onAfterDispatch event.
		JPluginHelper::importPlugin('j2xml');
//		JFactory::getApplication()->triggerEvent('onAfterDispatch');

		$info = $this->get('Info');
		$this->assignRef('info', $info);
		$params = JComponentHelper::getParams('com_j2xml');
		$this->assignRef('params', $params);

		J2XMLHelper::addSubmenu('cpanel');
		$this->sidebar = JHtmlSidebar::render();

		$this->addToolbar();
		parent::display($tpl);
	}

	function _quickiconButton( $link, $image, $text, $path=null, $target='', $onclick='' ) {
		$app = JFactory::getApplication('administrator');
		if( $target != '' ) {
			$target = 'target="' .$target. '"';
		}
		if( $onclick != '' ) {
			$onclick = 'onclick="' .$onclick. '"';
		}
		if( $path === null || $path === '' ) {
			$template = $app->getTemplate();
			$path = '/templates/'. $template .'/images/header/';
		}

		$lang = JFactory::getLanguage();

		if (!class_exists('JPlatform') || version_compare(JPlatform::RELEASE, '12', 'lt'))
		{
		?>
		<div style="float:<?php echo ($lang->isRTL()) ? 'right' : 'left'; ?>;">
			<div class="icon">
				<a href="<?php echo $link; ?>" <?php echo $target;?>  <?php echo $onclick;?>>
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
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT.'/helpers/j2xml.php';
		$canDo	= j2xmlHelper::getActions();

		$toolbar = JToolBar::getInstance('toolbar');
		$toolbar->addButtonPath(JPATH_COMPONENT.DS.'buttons');

		JToolBarHelper::title(JText::_('COM_J2XML_TOOLBAR_J2XML'), 'j2xml.png');

		$doc = JFactory::getDocument();
		if ($canDo->get('core.create') || ($canDo->get('core.edit'))) {
			jimport('eshiol.core.file');

			$min = ($this->params->get('debug', 0) ? '' : '.min');
			JLog::add(new JLogEntry("loading encryption{$min}.js...", JLOG::DEBUG, 'com_j2xml'));			
			$doc->addScript("../media/lib_eshiol_core/js/encryption{$min}.js");
			JLog::add(new JLogEntry("loading core{$min}.js...", JLOG::DEBUG, 'com_j2xml'));
			$doc->addScript("../media/lib_eshiol_core/js/core{$min}.js");
			JLog::add(new JLogEntry("loading version_compare{$min}.js...", JLOG::DEBUG, 'com_j2xml'));
			$doc->addScript("../media/lib_eshiol_core/js/version_compare{$min}.js");
			JLog::add(new JLogEntry("loading j2xml{$min}.js...", JLOG::DEBUG, 'com_j2xml'));
			$doc->addScript("../media/lib_eshiol_j2xml/js/j2xml{$min}.js");

			$toolbar = JToolBar::getInstance('toolbar');
			$toolbar->appendButton('File', 'j2xml', 'COM_J2XML_BUTTON_OPEN', 'COM_J2XML_BUTTON_IMPORT', 'j2xml.cpanel.import', 600, 400, null, 'xml,gz', null, $this->params->get('ajax', 0) ? 'eshiol.j2xml.import' : null);
//			$params = JComponentHelper::getParams('com_j2xml');
//			$hostname = JFactory::getURI()->getHost();
			$jinput   = JFactory::getApplication()->input;
			if (
//					($params->get('deveopment') &&
//					($hostname == 'localhost') &&
					($jinput->getCmd('d3v3l0p', '0') === '1') 
			)
			{
				$toolbar->appendButton('Link', 'purge', 'COM_J2XML_CONTENT_DELETE', 'index.php?option=com_j2xml&task=cpanel.clean&develop=1&'.JSession::getFormToken().'=1');
			}
			JToolBarHelper::divider();
		}
		if ($canDo->get('core.admin')) {
			JToolBarHelper::preferences('com_j2xml');
		}

		$doc->addStyleDeclaration('#toolbar-credit{float:right;}');
		$toolbar->appendButton('Link', 'credit', 'COM_J2XML_DONATE', 'http://www.eshiol.it/'.Jtext::_('COM_J2XML_DONATE_1'));
	}
}
?>
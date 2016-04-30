<?php
/**
 * @version		3.3.145 administrator/components/com_j2xml/models/cpanel/view.html.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.3
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

jimport('joomla.html.html.tabs');

class J2XMLViewCpanel extends JViewAbstract
{
	function display($tpl = null)
	{
		$info = $this->get('Info');
		$this->assignRef('info', $info);
		$params = JComponentHelper::getParams('com_j2xml');
		$this->assignRef('params', $params);

		if (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge'))
		{
			J2XMLHelper::addSubmenu('cpanel');
			$this->sidebar = JHtmlSidebar::render();
		}
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
			$icon_48_j2xml = " .icon-48-j2xml {background:url(../media/com_j2xml/images/icon-48-j2xml.png) no-repeat; }"; 
			$doc->addStyleDeclaration($icon_48_j2xml);
			if (class_exists('JPlatform'))
			{
				if (version_compare(JPlatform::RELEASE, '12', 'ge'))
				{
					jimport('eshiol.core.file');
					$toolbar = JToolBar::getInstance('toolbar');
					$toolbar->appendButton('File', 'j2xml', 'COM_J2XML_BUTTON_OPEN', 'COM_J2XML_BUTTON_IMPORT', 'j2xml.cpanel.import', 600, 400, null, 'xml,gz');
				}
			}
//			$params = JComponentHelper::getParams('com_j2xml');
//			$hostname = JFactory::getURI()->getHost();
			if (
//					($params->get('deveopment') &&
//					($hostname == 'localhost') &&
					(JRequest::getCmd('d3v3l0p', '0') === '1') 
			)
				$toolbar->appendButton('Link', 'purge', 'COM_J2XML_CONTENT_DELETE', 'index.php?option=com_j2xml&task=cpanel.clean&develop=1&'.JSession::getFormToken().'=1');
			JToolBarHelper::divider();
		}	
		if ($canDo->get('core.admin')) {
			JToolBarHelper::preferences('com_j2xml');
		}		
	}
}
?>
<?php
/**
 * @version		3.7.171 administrator/components/com_j2xml/views/websites/view.html.php
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

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View class for a list of websites.
 */
class J2XMLViewWebsites extends JViewLegacy
{
	protected $items;
	protected $pagination;
	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->state		= $this->get('State');

		if (version_compare(JPlatform::RELEASE, '12', 'ge'))
		{
			J2XMLHelper::addSubmenu('websites');
			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_state',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.state'), true)
			);
			$this->sidebar = JHtmlSidebar::render();
		}
		$this->addToolbar();

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT.'/helpers/j2xml.php';

		$canDo	= J2XMLHelper::getActions();

		$doc = JFactory::getDocument();
		$icon_48_websites = " .icon-48-websites {background:url(../media/com_j2xml/images/icon-48-websites.png) no-repeat; }";
		$doc->addStyleDeclaration($icon_48_websites);

		JToolBarHelper::title(JText::_('COM_J2XML_MANAGER_WEBSITES'), 'websites.png');
		if ($canDo->get('core.create')) {
			JToolBarHelper::addNew('website.add');
		}
		if ($canDo->get('core.edit')) {
			JToolBarHelper::editList('website.edit');
		}
		if ($canDo->get('core.edit.state')) {
			JToolBarHelper::divider();
			JToolBarHelper::publish('websites.publish', 'JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublish('websites.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::divider();
			JToolBarHelper::checkin('websites.checkin');
			JToolBarHelper::divider();
		}
		if ($this->state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'websites.delete', 'JTOOLBAR_EMPTY_TRASH');
		} elseif ($canDo->get('core.edit.state')) {
			JToolBarHelper::trash('websites.trash');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields()
	{
		return array(
			'a.title' => JText::_('JGLOBAL_TITLE'),
			'a.remote_url' => JText::_('COM_J2XML_HEADING_SERVER'),
			'a.username' => JText::_('COM_J2XML_HEADING_USERNAME'),
			'a.state' => JText::_('JSTATUS'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}

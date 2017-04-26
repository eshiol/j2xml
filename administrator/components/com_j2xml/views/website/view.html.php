<?php
/**
 * @version		3.7.170 administrator/components/com_j2xml/views/website/view.html.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.3
 * 
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2017 Helios Ciancio. All Rights Reserved
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
 * View to edit a website
 */
class J2XMLViewWebsite extends JViewLegacy
{
	protected $form;
	protected $item;
	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$this->form	= $this->get('Form');
		$this->item	= $this->get('Item');
		$this->state	= $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$this->addToolbar();

		$this->form->setValue('redirect_uri', null, rtrim(JUri::base(), '/').'/index.php?option=com_j2xml&task=website.oauth2');

		if ($this->item->id != 0)
		{
			if ($this->item->type == 1)
			{
				$this->form->setFieldAttribute('username', 'readonly', 'true', $group = null);
			}
			$this->form->setFieldAttribute('type', 'readonly', 'true', $group = null);
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
		$this->input->set('hidemainmenu', true);

		$user		= JFactory::getUser();
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo		= J2XMLHelper::getActions();

		JToolBarHelper::title($isNew ? JText::_('COM_J2XML_MANAGER_WEBSITE_NEW') : JText::_('COM_J2XML_MANAGER_WEBSITE_EDIT'), 'websites.png');

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit')||$canDo->get('core.create'))) {
			JToolBarHelper::apply('website.apply');
			JToolBarHelper::save('website.save');
		}
		if (!$checkedOut && $canDo->get('core.create')) {

			JToolBarHelper::save2new('website.save2new');
		}
		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create')) {
			JToolBarHelper::save2copy('website.save2copy');
		}

		if (empty($this->item->id))  {
			JToolBarHelper::cancel('website.cancel');
		} else {
			JToolBarHelper::cancel('website.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}

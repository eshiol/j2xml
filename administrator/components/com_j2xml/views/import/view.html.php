<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2021 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

JLoader::register('J2XMLViewDefault', dirname(__DIR__) . '/default/view.php');

/**
 * J2xml Import View
 *
 * @since  3.9.0
 */
class J2xmlViewImport extends J2xmlViewDefault
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 *
	 * @since   3.9
	 */
	public function display($tpl = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		// Initialiase variables.
		$this->form  = $this->get('Form');

		$paths = new stdClass;
		$paths->first = '';
		$state = $this->get('state');

		$this->paths = &$paths;
		$this->state = &$state;

		JPluginHelper::importPlugin('installer');

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since 1.6
	 */
	protected function addToolbar ()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		JToolBarHelper::title(JText::_('COM_J2XML_TOOLBAR_J2XML'), 'upload import');

		if (JFactory::getUser()->authorise('core.admin'))
		{
			JToolBarHelper::preferences('com_j2xml');
		}

		$doc = JFactory::getDocument();
		$doc->addStyleDeclaration('#toolbar-credit{float:right;}');

		$toolbar = JToolBar::getInstance('toolbar');
		$toolbar->appendButton('Popup', 'credit', 'COM_J2XML_DONATE', 'https://www.eshiol.it/' . Jtext::_('COM_J2XML_DONATE_1'), 550, 350);		
	}
}

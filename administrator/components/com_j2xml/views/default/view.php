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

/**
 * J2XML Default View
 *
 * @since  3.9
 */
class J2xmlViewDefault extends JViewLegacy
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  Configuration array
	 *
	 * @since   3.9
	 */
	public function __construct($config = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$app = JFactory::getApplication();
		parent::__construct($config);
		$this->_addPath('template', $this->_basePath . '/views/default/tmpl');
		$this->_addPath('template', JPATH_THEMES . '/' . $app->getTemplate() . '/html/com_j2xml/default');
	}

	/**
	 * Display the view.
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

		// Get data from the model.
		$state = $this->get('State');

		// Are there messages to display?
		$showMessage = false;

		if (is_object($state))
		{
			$message	 = $state->get('message');
			$showMessage = (bool) $message;
		}

		$this->showMessage = $showMessage;
		$this->state	   = &$state;

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   3.9
	 */
	protected function addToolbar()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$canDo = JHelperContent::getActions('com_j2xml');
		JToolbarHelper::title(JText::_('COM_J2XML_HEADER_' . $this->getName()), 'upload import');

		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			JToolbarHelper::preferences('com_j2xml');
			JToolbarHelper::divider();
		}
	}
}

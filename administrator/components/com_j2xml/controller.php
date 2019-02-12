<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
 * 
 * @version		3.7.192
 * @since		1.5.3
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

// No direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

/**
 * J2XML master display controller
 */
class J2XMLController extends JControllerLegacy
{

	/**
	 * Method to display a view.
	 *
	 * @param
	 *        	boolean If true, the view output will be cached
	 * @param
	 *        	array An array of safe url parameters and their variable
	 *        	types, for valid values see {@link JFilterInput::clean()}.
	 *        
	 * @return JController This object to support chaining.
	 * @since 1.5
	 */
	public function display ($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT . '/helpers/j2xml.php';
		J2XMLHelper::updateReset();

		$jinput = JFactory::getApplication()->input;
		$view = $jinput->getCmd('view', '');
		$layout = $jinput->getCmd('layout', 'default');
		$id = $jinput->getInt('id');

		// Check for edit form.
		if ($view == 'website' && $layout == 'edit' && ! $this->checkEditId('com_j2xml.edit.website', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			throw new Exception(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setRedirect(JRoute::_('index.php?option=com_j2xml&view=websites', false));

			return false;
		}
		elseif ($view == '')
		{
			$this->setRedirect(JRoute::_('index.php?option=com_j2xml&view=cpanel', false));
			return false;
		}
		parent::display();
		return $this;
	}
}

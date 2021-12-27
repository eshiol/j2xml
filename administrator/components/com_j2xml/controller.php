<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2021 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

JLoader::import('joomla.application.component.controller');

/**
 * J2XML master display controller
 *
 * @since 1.5.3
 */
class J2xmlController extends JControllerLegacy
{

	/**
	 * Method to display a view.
	 *
	 * @param
	 *			boolean If true, the view output will be cached
	 * @param
	 *			array An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return JController This object to support chaining.
	 * @since 1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		// Get the document object.
		$document = JFactory::getDocument();

		// Set the default view name and format from the Request.
		$vName = $this->input->get('view', 'import');
		$vFormat = $document->getType();
		$lName = $this->input->get('layout', 'default', 'string');

		// Get and render the view.
		if ($view = $this->getView($vName, $vFormat))
		{
			$ftp = JClientHelper::setCredentialsFromRequest('ftp');
			$view->ftp = &$ftp;

			// Get the model for the view.
			$model = $this->getModel($vName, 'J2xmlModel');
			$model->getState();

			// Push the model into the view (as default).
			$view->setModel($model, true);
			$view->setLayout($lName);

			// Push document object into the view.
			$view->document = $document;

			$view->display();
		}

		return $this;
	}
}

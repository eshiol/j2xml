<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
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

/**
 * Importer controller for J2xml class.
 *
 * @since  3.9
 */
class J2xmlControllerImport extends JControllerLegacy
{
	/**
	 * Import data.
	 *
	 * @return  boolean
	 *
	 * @since   3.9
	 */
	public function import()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		// Check for request forgeries.
		$version = new \JVersion();
		if ($version->isCompatible('3.7'))
		{
			$this->checkToken();
		}
		else
		{
			JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		}

		/** @var J2xmlModelImport $model */
		$model = $this->getModel('import');

		// @todo Reset the users acl here as well to kill off any missing bits.
		$result = $model->import();

		$app = JFactory::getApplication();
		$redirect_url = $app->getUserState('com_j2xml.redirect_url');

		if (!$redirect_url)
		{
			$redirect_url = base64_decode($app->input->get('return'));
		}

		// Don't redirect to an external URL.
		if (!JUri::isInternal($redirect_url))
		{
			$redirect_url = '';
		}

		if (empty($redirect_url))
		{
			$redirect_url = JRoute::_('index.php?option=com_j2xml&view=import', false);
		}
		else
		{
			// Wipe out the user state when we're going to redirect.
			$app->setUserState('com_j2xml.redirect_url', '');
			$app->setUserState('com_j2xml.message', '');
		}

		$this->setRedirect($redirect_url);

		return $result;
	}

	/**
	 * Import data from drag & drop ajax upload.
	 *
	 * @return  void
	 *
	 * @since   3.9
	 */
	public function ajax_upload()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$app = JFactory::getApplication();
		$message = $app->getUserState('com_j2xml.message');

		$jform  = $app->input->post->get('jform', array(), 'array');
		$data = array();
		foreach($jform as $k => $v)
		{
			if (substr($k, 0, 7) == 'import_')
			{
				$data[substr($k, 7)] = $v;
			}
		}
		// Save the posted data in the session.
		$app->setUserState('com_j2xml.import.data', $data);
		JLog::add(new JLogEntry('setUserState(\'com_j2xml.import.data\'): ' . print_r($data, true), JLog::DEBUG, 'com_j2xml'));
		
		// Do import
		$result = $this->import();

		// Get redirect URL
		$redirect = $this->redirect;

		// Push message queue to session because we will redirect page by Javascript, not $app->redirect().
		// The "application.queue" is only set in redirect() method, so we must manually store it.
		$app->getSession()->set('application.queue', $app->getMessageQueue());

		header('Content-Type: application/json');

		echo new JResponseJson(array('redirect' => $redirect), $message, !$result);

		exit();
	}
}

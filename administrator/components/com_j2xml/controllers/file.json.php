<?php
/**
 * @version		2.5.85 controllers/file.php
 *
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.6.1.75
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2013 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport( 'joomla.application.component.controller' );

class J2XMLControllerFile extends JController
{
	function select()
	{
		
		// Check for request forgeries
		if (!JSession::checkToken('request')) {
			echo json_encode(array(
					'status' => '0',
					'error' => JText::_('JINVALID_TOKEN')
				)
			);
			return;
		}

		// Get the user
		$user		= JFactory::getUser();

		$params = JComponentHelper::getParams('com_j2xml');
		$remote_folder = $params->get('remote_folder', '../media/com_j2xml/files');

		jimport('eshiol.filemanager.filemanager');
		$browser = new FileManager(array(
			  'directory' => $remote_folder,
			//  'chmod' => 0777
		));

		$browser->fireEvent(!empty($_GET['event']) ? $_GET['event'] : null);
	}
}

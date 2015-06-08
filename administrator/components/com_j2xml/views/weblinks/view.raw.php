<?php
/**
 * @version		3.0.96 views/weblinks/view.raw.php
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.3beta3.38
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

jimport('eshiol.j2xml.importer');

/**
 * J2XML Component Content View
 */
class J2XMLViewWeblinks extends JViewAbstract
{
	function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$cid = JRequest::getVar('cid');		
		$ids = explode(",", $cid);

		$params = JComponentHelper::getParams('com_j2xml');
		$images = array();

		$xml = J2XMLExporter::weblinks($ids,
					$params->get('export_images', '1'),
					1,
					$params->get('export_users', '1'),
					$images
				);
		foreach ($images as $image)
			$xml .= $image;	
		
		if (!J2XMLExporter::export(
				$xml,		
				$params->get('debug', 0), 
				$params->get('export_gzip', '0')
			))
			$app->redirect('index.php?option=com_weblinks');
	}
}
?>
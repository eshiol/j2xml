<?php
/**
 * @version		3.6.161 administrator/components/com_j2xml/views/raw.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		3.2.137
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
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('eshiol.j2xml.exporter');

/**
 * J2XML Component View
 */
class J2XMLView extends JViewLegacy
{
	function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$cid = JRequest::getVar('cid');
		$ids = explode(",", $cid);

		$params = JComponentHelper::getParams('com_j2xml');

		$options = array();
		$options['images'] = $params->get('export_images', '1');
		$options['users'] = $params->get('export_users', '1');
		$options['categories'] = 1;
		$options['contacts'] = $params->get('export_contacts', '1');

		$j2xml = new J2XMLExporter();
		$get_xml = strtolower(str_replace('J2XMLView', '', get_class($this)));
		$j2xml->$get_xml($ids, $xml, $options);

		$options = array();
		$options['debug'] = $params->get('debug', 0);
		$options['gzip'] = $params->get('export_gzip', '0');

		if (!$j2xml->export($xml, $options))
			$app->redirect('index.php?option=com_categories&extension=com_content');
	}
}
?>
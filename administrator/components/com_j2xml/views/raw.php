<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
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

// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('eshiol.j2xml.Exporter');

/**
 * J2XML Component View
 * 
 * @version		3.7.188
 * @since		3.2.137
 */
class J2XMLView extends JViewLegacy
{

	function display ($tpl = null)
	{
		$app = JFactory::getApplication();
		$jinput = $app->input;
		$cid = $jinput->get('cid', null, 'RAW');
		$ids = explode(",", $cid);

		$params = JComponentHelper::getParams('com_j2xml');

		$options = array();
		$options['images'] = $params->get('export_images', '1');
		$options['users'] = $params->get('export_users', '1');
		$options['categories'] = 1;
		$options['contacts'] = $params->get('export_contacts', '1');

		$exporter = new eshiol\J2XML\Exporter();

		$get_xml = strtolower(str_replace('J2XMLView', '', get_class($this)));
		$exporter->$get_xml($ids, $xml, $options);

		$options = array();
		$options['debug'] = $params->get('debug', 0);
		$options['gzip'] = $params->get('export_gzip', '0');

		$exporter->export($xml, $options);
	}
}
?>
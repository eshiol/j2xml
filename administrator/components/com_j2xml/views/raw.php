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

use eshiol\J2xml\Exporter;
use eshiol\J2xml\Version;

JLoader::import('eshiol.J2xml.Exporter');
JLoader::import('eshiol.J2xml.Version');

/**
 * J2XML Component base RAW View
 *
 * @since 3.2.137
 */
class J2xmlView extends JViewLegacy
{

	/**
	 * The list of IDs to be exported
	 *
	 * @var array
	 */
	protected $ids;

	/**
	 * The params object
	 *
	 * @var JRegistry
	 */
	protected $params;

	/**
	 * Constructor
	 *
	 * @param array $config
	 *			A named configuration array for object construction.
	 *			name: the name (optional) of the view (defaults to the view class name suffix).
	 *			charset: the character set to use for display
	 *			escape: the name (optional) of the function to use for escaping strings
	 *			base_path: the parent path (optional) of the views directory (defaults to the component folder)
	 *			template_plath: the path (optional) of the layout directory (defaults to base_path + /views/ + view name
	 *			helper_path: the path (optional) of the helper files (defaults to base_path + /helpers/)
	 *			layout: the layout (optional) to use to display the view
	 */
	public function __construct($config = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		parent::__construct($config);

		$jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');

		$this->ids = explode(",", $jform['cid']);
		unset($jform['cid']);

		$this->params = new JRegistry();
		$this->params->loadArray($jform);
	}

	/**
	 * Execute and display a template script.
	 *
	 * @param string $tpl
	 *			The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @see JViewLegacy::loadTemplate()
	 */
	function display($tpl = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$params = new JRegistry();
		foreach ($this->params->toArray() as $k => $v)
		{
			$params->set(substr($k, 0, 7) == 'export_' ? substr($k, 7) : $k, $v);
		}

		$j2xml = new Exporter();
		$get_xml = strtolower(str_replace('J2xmlView', '', get_class($this)));
		$j2xml->$get_xml($this->ids, $xml, $params);

		$out = 'j2xml' . str_replace('.', '', Version::$DOCVERSION) . JFactory::getDate()->format("YmdHis");

		$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$data = $dom->saveXML();

		// modify the MIME type
		$document = JFactory::getDocument();
		$compression = $params->get('compression', 0);

		if (!\extension_loaded('zlib') || ini_get('zlib.output_compression'))
		{
			$document->setMimeEncoding('text/xml', true);
			JFactory::getApplication()->setHeader('Content-disposition', 'attachment; filename="' . $out . '.xml"', true);
		}
		elseif ($compression)
		{
			$document->setMimeEncoding('application/gzip', true);
			JFactory::getApplication()->setHeader('Content-disposition', 'attachment; filename="' . $out . '.gz"', true);
			$data = gzencode($data, 4);
		}
		else
		{
			$document->setMimeEncoding('text/xml', true);
			JFactory::getApplication()->setHeader('Content-disposition', 'attachment; filename="' . $out . '.xml"', true);
		}
		echo $data;
		return true;
	}
}

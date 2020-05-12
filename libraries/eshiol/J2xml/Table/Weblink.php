<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2020 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or die();

use eshiol\J2xml\Table\Table;
\JLoader::import('eshiol.J2xml.Table.Table');

/**
 * Viewlevel Table
 *
 * @version __DEPLOY_VERSION__
 * @since 15.3.248
 */
class Weblink extends Table
{

	/**
	 * Constructor
	 *
	 * @param \JDatabaseDriver $db
	 *        	A database connector object
	 *
	 * @since 1.5.3beta3.38
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		parent::__construct('#__weblinks', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 * @since 15.9.261
	 */
	function toXML ($mapKeysToText = false)
	{
		$version = new \JVersion();
		if ($version->isCompatible('3.1'))
		{
			// $this->_aliases['tag']='SELECT t.path FROM #__tags t,
			// #__contentitem_tag_map m WHERE type_alias =
			// "com_weblinks.weblink" AND t.id = m.tag_id AND m.content_item_id
			// = '. (int)$this->id;
			$this->_aliases['tag'] = (string) $this->_db->getQuery(true)
				->select($this->_db->quoteName('t.path'))
				->from($this->_db->quoteName('#__tags', 't'))
				->from($this->_db->quoteName('#__contentitem_tag_map', 'm'))
				->where($this->_db->quoteName('type_alias') . ' = ' . $this->_db->quote('com_weblinks.weblink'))
				->where($this->_db->quoteName('t.id') . ' = ' . $this->_db->quoteName('m.tag_id'))
				->where($this->_db->quoteName('m.content_item_id') . ' = ' . $this->_db->quote((string) $this->id));
		}

		return parent::_serialize();
	}

	/**
	 * Export data
	 *
	 * @param int $id
	 *        	the id of the item to be exported
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param array $options
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function export ($id, &$xml, $options)
	{
		if ($xml->xpath("//j2xml/weblink/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \JFactory::getDbo();
		$item = new Weblink($db);
		if (! $item->load($id))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param \JRegistry $params
	 *        	@option int 'weblinks' 0: No | 1: Yes, if not exists | 2: Yes,
	 *        	overwrite if exists
	 *        	@option string 'context'
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function import ($xml, &$params)
	{
		$import_weblinks = $params->get('weblinks', 1);
		if ($import_weblinks == 0)
			return;

		$db = \JFactory::getDbo();

		// Check if component is installed
		$db->setQuery("SELECT enabled FROM #__extensions WHERE name = 'com_weblinks'");
		if (! $db->loadResult())
			return;

		$params->set('extension', 'com_weblinks');
		$params->def('category_default', self::getCategoryId('uncategorised', 'com_weblinks'));

		foreach ($xml->xpath("//j2xml/weblink[not(title = '')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$id = $data['id'];

			$query = $db->getQuery(true)
				->select(array(
					$db->quoteName('id'),
					$db->quoteName('title')
			))
				->from($db->quoteName('#__weblinks'))
				->where($db->quoteName('alias') . ' = ' . $db->quote($data['alias']));
			$item = $db->setQuery($query)->loadObject();

			if (! $item || ($import_weblinks))
			{
				$table = new \eshiol\J2xml\Table\Weblink($db);
				if (! $item)
				{
					$data['id'] = null;
				}
				else
				{
					$data['id'] = $item->id;
					$table->load($data['id']);
				}

				// Trigger the onContentBeforeSave event.
				$table->bind($data);
				if ($table->store())
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_WEBLINK_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
					// Trigger the onContentAfterSave event.
				}
				else
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED', $data['title']), \JLog::ERROR, 'lib_j2xml'));
					\JLog::add(new \JLogEntry($table->getError(), \JLog::ERROR, 'lib_j2xml'));
				}
				$table = null;
			}
		}
	}
}
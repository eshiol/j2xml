<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2020 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
namespace eshiol\J2XML\Table;
defined('JPATH_PLATFORM') or die();

use eshiol\J2XML\Table\Table;
\JLoader::import('eshiol.j2xml.Table.Table');

use Joomla\CMS\Component\ComponentHelper;

/**
 * Table
 *
 * @version __DEPLOY_VERSION__
 * @since 19.2.323
 */
class Fieldgroup extends Table
{

	/**
	 * Object constructor to set table and key fields.
	 * In most cases this will
	 * be overridden by child classes to explicitly set the table and key fields
	 * for a particular database table.
	 *
	 * @param
	 *        	string Name of the table to model.
	 * @param
	 *        	string Name of the primary key field in the table.
	 * @param
	 *        	object JDatabase connector object.
	 *
	 * @since 19.2.323
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		parent::__construct('#__fields_groups', 'id', $db);
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param \JRegistry $params
	 *        	@option int 'fields' 0: No | 1: Yes, if not exists | 2: Yes,
	 *        	overwrite if exists
	 *        	@option string 'context'
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 19.2.323
	 */
	public static function import ($xml, &$params)
	{
		$import_fields = $params->get('fields', 0);
		if ($import_fields == 0)
			return;

		$context = $params->get('context');
		$db = \JFactory::getDBO();
		$nullDate = $db->getNullDate();
		$userid = \JFactory::getUser()->id;

		foreach ($xml->xpath("//j2xml/fieldgroup") as $record)
		{
			self::prepareData($record, $data, $params);

			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->select($db->quoteName('title'))
				->from($db->quoteName('#__fields_groups'))
				->where($db->quoteName('context') . ' = ' . $db->quote($data['context']))
				->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));
			$fieldgroup = $db->setQuery($query)->loadObject();

			if (! $fieldgroup )
			{
				\JLoader::register('GroupTable', JPATH_ADMINISTRATOR . '/components/com_fields/Table/GroupTable.php');
				if (class_exists('\Joomla\Component\Fields\Administrator\Table\GroupTable'))
				{
					$table = new GroupTable($db);
				}
				else
				{ // Joomla! 4
					\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/tables');
					$table = \JTable::getInstance('Group', 'FieldsTable');
				}

				$data['id'] = null;

				// TODO: Trigger the onContentBeforeSave event.
				$table->bind($data);
				if ($table->store())
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_FIELDGROUP_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
					// TODO: Trigger the onContentAfterSave event.
				}
				else
				{
					\JLog::add(
							new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_FIELDGROUP_NOT_IMPORTED', $data['title'], $table->getError()), \JLog::ERROR,
									'lib_j2xml'));
				}
				$table = null;
			}
		}
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
	 * @since 19.2.323
	 */
	public static function export ($id, &$xml, $options)
	{
		if ($xml->xpath("//j2xml/fieldgroup/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \JFactory::getDbo();
		$item = new Fieldgroup($db);
		if (! $item->load($id))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if ($options['users'])
		{
			if ($item->created_by)
			{
				User::export($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				User::export($item->modified_by, $xml, $options);
			}
		}
	}
}
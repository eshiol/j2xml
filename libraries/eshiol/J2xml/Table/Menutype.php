<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
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
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or die();

/**
 * Menutype Table class
 *
 * @since 22.1.355
 */
class Menutype extends \eshiol\J2XML\Table\Table
{

	/**
	 * Constructor
	 *
	 * @param
	 *        	object Database connector object
	 * @since 17.1.294
	 */
	function __construct (& $db)
	{
		parent::__construct('#__menu_types', 'id', $db);
	}

	/**
	 * Export menutype
	 *
	 * @param int $id
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 19.2.318
	 */
	public static function export ($id, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('id: ' . $id, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

		$db = \JFactory::getDbo();
		$item = new Menutype($db);
		if (! $item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/menutype/id[text() = '" . $item->id . "']"))
		{
			return;
		}

		/* export modules */
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->select($db->qn('params'))
			->from($db->qn('#__modules'))
			->where($db->qn('module') . ' = ' . $db->q('mod_menu'));
		\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
		$modules = $db->setQuery($query)->loadObjectList();

		foreach ($modules as $module)
		{
			$params = new \JRegistry($module->params);
			if ($params->get('menutype') == $item->menutype)
			{
				Module::export($module->id, $xml, $options);
			}
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		/* export menus */
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__menu'))
			->where($db->qn('menutype') . ' = ' . $db->q($item->menutype))
			->where($db->qn('parent_id') . ' = 1')
			-> // export only level 1
			                                       // ->order($db->qn('level')) //
			                                       // export all levels
		order($db->qn('lft'));
		\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
		$ids_menu = $db->setQuery($query)->loadColumn();

		foreach ($ids_menu as $id_menu)
		{
			Menu::export($id_menu, $xml, $options);
		}
	}

	/**
	 * importing menu types
	 *
	 * @param SimpleXMLElement $xml
	 * @param \JRegistry $params
	 *
	 * @since 19.2.318
	 */
	public static function import ($xml, &$params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$db = \JFactory::getDbo();
		$import_menus = $params->get('import_menus', '1');

		foreach ($xml->xpath("//j2xml/menutype[not(title = '')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$query = $db->getQuery(true)
				->select($db->qn(array(
					'id',
					'title'
			)))
				->from($db->qn('#__menu_types'))
				->where($db->qn('menutype') . '=' . $db->q($data['menutype']));
			\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
			$menutype = $db->setQuery($query)->loadResult();

			if (! $menutype || ($import_menus == 2))
			{
				$table = new MenuType($db);

				if (! $menutype)
				{ // new menutype
					$data['id'] = null;
				}
				else // menutype already exists
				{
					$data['id'] = $menutype->id;
					$table->load($data['id']);
				}

				// Trigger the onContentBeforeSave event.
				$table->bind($data);
				if ($table->store())
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_MENUTYPE_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
					// Trigger the onContentAfterSave event.
				}
				else
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_MENUTYPE_NOT_IMPORTED', $data['title'], $table->getError()), \JLog::ERROR, 'lib_j2xml'));
				}
				$table = null;
			}
		}
	}
}

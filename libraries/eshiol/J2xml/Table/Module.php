<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       22.1.355
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or die();

/**
 *
 * Menu Table class
 *
 */
class Module extends \eshiol\J2XML\Table\Table
{

	/**
	 * Constructor
	 *
	 * @param object $db
	 *        	Database connector
	 *
	 * @since 17.1.294
	 */
	function __construct (&$db)
	{
		parent::__construct('#__modules', 'id', $db);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \eshiol\J2XML\Table::toXML()
	 */
	function toXML ($mapKeysToText = false)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		$this->_aliases['menus'] = "SELECT DISTINCT IF(SIGN(mm.menuid) > 0, 'include', 'exclude') FROM `#__modules_menu` mm INNER JOIN `#__menu` m ON ABS(mm.menuid) = m.id WHERE mm.moduleid = " .
				 (int) $this->id . " UNION SELECT 'all' FROM `#__modules_menu` mm WHERE mm.moduleid = " . (int) $this->id . " AND mm.menuid = 0";
		$this->_aliases['menu'] = "SELECT CONCAT(m.menutype, '/', m.path) FROM `#__modules_menu` mm INNER JOIN `#__menu` m ON ABS(mm.menuid) = m.id WHERE mm.moduleid = " .
				 (int) $this->id;

		return parent::_serialize();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \eshiol\J2XML\Table::export()
	 */
	public static function export ($id, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('id: ' . $id, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

		$db = \JFactory::getDbo();
		$item = new Module($db);
		if (! $item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/module/id[text() = '" . $item->id . "']"))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \eshiol\J2XML\Table::import()
	 */
	public static function import ($xml, &$params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$db = \JFactory::getDbo();
		$import_modules = $params->get('modules', '2');

		foreach ($xml->xpath("//j2xml/module[not(title = '')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$db = \JFactory::getDbo();

			/* import module */
			$query = $db->getQuery(true)
				->select($db->qn('id'))
				->select($db->qn('title'))
				->from($db->qn('#__modules'))
				->where($db->qn('module') . ' = ' . $db->q($data['module']))
				->where($db->qn('title') . ' = ' . $db->q($data['title']));
			\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
			$module = $db->setQuery($query)->loadObject();

			if (! $module || ($import_modules == 2))
			{
				$table = new Module($db);

				if (! $module)
				{ // new menutype
					$data['id'] = null;
				}
				else // module already exists
				{
					$data['id'] = $module->id;
					$table->load($data['id']);
				}
				\JLog::add(new \JLogEntry('bind: ' . print_r($data, true), \JLog::DEBUG, 'lib_j2xml'));

				// Trigger the onContentBeforeSave event.
				$table->bind($data);
				if ($table->store())
				{
					$query = $db->getQuery(true)
						->delete('#__modules_menu')
						->where($db->quoteName('moduleid') . ' = ' . $table->id);
					$db->setQuery($query)->execute();
					if (isset($data['menus']))
					{
						$query->clear()->insert('#__modules_menu');

						if ($data['menus'] == 'all')
						{
							$query->values($table->id . ', 0');
						}
						else
						{
							$include = ($data['menus'] == 'exclude') ? - 1 : 1;
							if (isset($data['menu']))
							{
								$query->values($table->id . ', ' . ($include * parent::getMenuId($data['menu'])));
							}
							elseif (isset($data['menulist']))
							{
								foreach ($data['menulist']['menu'] as $v)
								{
									$query->values($table->id . ', ' . ($include * parent::getMenuId($v)));
								}
							}
						}
						$db->setQuery($query)->execute();
					}
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_MODULE_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
					// Trigger the onContentAfterSave event.
				}
				else
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_MODULE_NOT_IMPORTED', $data['title'], $table->getError()), \JLog::ERROR, 'lib_j2xml'));
				}
				$table = null;
			}
		}
	}
}

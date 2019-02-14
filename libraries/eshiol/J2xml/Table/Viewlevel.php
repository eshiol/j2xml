<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
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
namespace eshiol\J2XML\Table;
defined('JPATH_PLATFORM') or die();

use eshiol\J2XML\Table\Table;
\JLoader::import('eshiol.j2xml.Table.Table');

/**
 * Viewlevel Table
 *
 * @version 19.2.325
 * @since 15.3.248
 */
class Viewlevel extends Table
{

	/**
	 * Constructor
	 *
	 * @param \JDatabaseDriver $db
	 *        	A database connector object
	 *        
	 * @since 15.3.248
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		parent::__construct('#__viewlevels', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML ($mapKeysToText = false)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry(print_r($this->rules, true), \JLog::DEBUG, 'lib_j2xml'));

		$this->_excluded = array_merge($this->_excluded, array(
				'rules'
		));

		$serverType = (new \JVersion())->isCompatible('3.5') ? $this->_db->getServerType() : 'mysql';

		if ($serverType === 'postgresql')
		{
			$this->_aliases['rule'] = '
				WITH RECURSIVE usergroups(id, title, parent_id, depth, path) AS (
				  SELECT tn.id, tn.title, tn.parent_id, 1::INT AS depth, tn.title::TEXT AS path
				  FROM #__usergroups AS tn
				  WHERE tn.parent_id = 0
				UNION ALL
				  SELECT c.id, c.title, c.parent_id, p.depth + 1 AS depth,
				        (p.path || \'","\' || c.title) AS path
				  FROM usergroups AS p, #__usergroups AS c
				  WHERE c.parent_id = p.id
				)
				SELECT (\'["\' || path || \'"]\') FROM usergroups WHERE id IN ' . str_replace(array(
					'[',
					']'
			), array(
					'(',
					')'
			), $this->rules);
		}
		else
		{
			$this->_aliases['rule'] = (string) $this->_db->getQuery(true)
				->select('usergroups_getpath(' . $this->_db->quoteName('id') . ')')
				->from($this->_db->quoteName('#__usergroups', 'g'))
				->where(
					$this->_db->quoteName('g.id') . ' IN ' . str_replace(array(
							'[',
							']'
					), array(
							'(',
							')'
					), $this->rules));
		}
		\JLog::add(new \JLogEntry($this->_aliases['rule'], \JLog::DEBUG, 'lib_j2xml'));

		return parent::_serialize();
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param \JRegistry $params
	 *        	@option int 'viewlevels' 0: No | 1: Yes, if not exists | 2:
	 *        	Yes, overwrite if exists
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
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$import_viewlevels = 2; // $params->get('viewlevels', 1);
		if ($import_viewlevels == 0)
			return;

		$db = \JFactory::getDbo();
		foreach ($xml->xpath("//j2xml/viewlevel[not(title = '')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$id = $data['id'];

			$query = $db->getQuery(true)
				->select(array(
					$db->quoteName('id'),
					$db->quoteName('title')
			))
				->from($db->quoteName('#__viewlevels'))
				->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));
			\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
			$item = $db->setQuery($query)->loadObject();

			if (! $item || ($import_viewlevels == 2))
			{
				$table = new \eshiol\J2XML\Table\Viewlevel($db);
				if (! $item)
				{
					$data['id'] = null;
				}
				else
				{
					$data['id'] = $item->id;
					$table->load($data['id']);
				}

				// Add rules to the viewlevel data.
				$rules_id = array();
				if (isset($data['rule']))
				{
					$rules_id[] = $data['rule'];
					unset($data['rule']);
				}
				if (isset($data['rulelist']))
				{
					foreach ($data['rulelist']['rule'] as $v)
					{
						$rules_id[] = $v;
					}
					unset($data['rulelist']);
				}

				for ($i = 0; $i < count($rules_id); $i ++)
				{
					$usergroup = parent::getUsergroupId($rules_id[$i]);
					if ($usergroup !== null)
					{
						$rules_id[$i] = $usergroup;
					}
					else
					{
						$groups = json_decode($rules_id[$i]);
						$g = array();
						$id = 0;
						\JLog::add(new \JLogEntry(print_r($groups, true), \JLog::DEBUG, 'lib_j2xml'));

						for ($j = 0; $j < count($groups); $j ++)
						{
							$g[] = $groups[$j];
							$group = json_encode($g, JSON_NUMERIC_CHECK);
							$usergroup = parent::getUsergroupId($group);
							if ($usergroup !== null)
							{
								$id = $usergroup;
							}
							else // import usergroup
							{
								$u = new \Joomla\CMS\Table\Usergroup($db); // \JTable::getInstance('Usergroup');
								$u->save(array(
										'title' => $groups[$j],
										'parent_id' => $id
								));
								$id = $u->id;
								\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USERGROUP_IMPORTED', $groups[$j]), \JLog::INFO, 'lib_j2xml'));
							}
						}
						$rules_id[$i] = $id;
					}
				}
				$data['rules'] = json_encode($rules_id, JSON_NUMERIC_CHECK);

				\JLog::add(new \JLogEntry(print_r($data, true), \JLog::DEBUG, 'lib_j2xml'));
				if ($table->save($data))
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_VIEWLEVEL_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
				}
				else
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_VIEWLEVEL_NOT_IMPORTED', $data['title']), \JLog::ERROR, 'lib_j2xml'));
					\JLog::add(new \JLogEntry($table->getError(), \JLog::ERROR, 'lib_j2xml'));
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
	 * @since 18.8.310
	 */
	public static function export ($id, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('id: ' . $id, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

		if ($xml->xpath("//j2xml/viewlevel/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \JFactory::getDbo();
		$item = new Viewlevel($db);
		if (! $item->load($id))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);
	}
}

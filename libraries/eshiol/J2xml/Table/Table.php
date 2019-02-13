<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
namespace eshiol\J2XML\Table;
defined('JPATH_PLATFORM') or die();

use Joomla\CMS\Component\ComponentHelper;

/**
 * Table
 *
 * @version 19.2.323
 * @since 1.5.3.39
 */
class Table extends \JTable
{

	/**
	 * An array of key names to be excluded in the toXML function
	 *
	 * @var array
	 * @since 1.5.3.39
	 */
	protected $_excluded = array();

	/**
	 * An array of key names to be exported as alias in the toXML function
	 *
	 * @var array
	 * @since 1.5.3.39
	 */
	protected $_aliases = array();

	/**
	 *
	 * @var string
	 * @since 18.8.310
	 */
	const IMAGE_MATCH_STRING = '/<img.*?src="([^"]*)".*?[^>]*>/s';

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
	 * @since 1.0
	 */
	function __construct ($table, $key, &$db)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry($table, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry($key, \JLog::DEBUG, 'lib_j2xml'));

		parent::__construct($table, $key, $db);

		$this->_excluded = array(
				'asset_id',
				'parent_id',
				'lft',
				'rgt',
				'level',
				'checked_out',
				'checked_out_time'
		);
		$this->_aliases = array();
	}

	/**
	 * Method to load a row from the database by primary key and bind the fields
	 * to the JTable instance properties.
	 *
	 * @param mixed $keys
	 *        	An optional primary key value to load the row by, or an array
	 *        	of fields to match. If not
	 *        	set the instance property value is used.
	 * @param boolean $reset
	 *        	True to reset the default values before loading the new row.
	 *        
	 * @return boolean True if successful. False if row not found.
	 *        
	 * @link https://docs.joomla.org/JTable/load
	 * @since 11.1
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 */
	public function load ($keys = null, $reset = true)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		if ($ret = parent::load($keys, $reset))
		{
			if (isset($this->created_by))
			{
				$this->_aliases['created_by'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('username'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->created_by);
				\JLog::add(new \JLogEntry($this->_aliases['created_by'], \JLog::DEBUG, 'lib_j2xml'));
			}
			if (isset($this->created_user_id))
			{
				$this->_aliases['created_user_id'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('username'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->created_user_id);
				\JLog::add(new \JLogEntry($this->_aliases['created_user_id'], \JLog::DEBUG, 'lib_j2xml'));
			}
			if (isset($this->modified_by))
			{
				$this->_aliases['modified_by'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('username'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->modified_by);
				\JLog::add(new \JLogEntry($this->_aliases['modified_by'], \JLog::DEBUG, 'lib_j2xml'));
			}
			if (isset($this->modified_user_id))
			{
				$this->_aliases['modified_user_id'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('username'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->modified_user_id);
				\JLog::add(new \JLogEntry($this->_aliases['modified_user_id'], \JLog::DEBUG, 'lib_j2xml'));
			}
			if (isset($this->catid))
			{
				$this->_aliases['catid'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('path'))
					->from($this->_db->quoteName('#__categories'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->catid);
				\JLog::add(new \JLogEntry($this->_aliases['catid'], \JLog::DEBUG, 'lib_j2xml'));
			}
			if (isset($this->access))
			{
				// $this->_aliases['access']='SELECT IF(f.id<=6,f.id,f.title)
				// FROM #__viewlevels f RIGHT JOIN '.$this->_tbl.' a ON f.id =
				// a.access WHERE a.id = '. (int)$this->id;
				$query = $this->_db->getQuery(true);
				$serverType = (new \JVersion())->isCompatible('3.5') ? $this->_db->getServerType() : 'mysql';

				if ($serverType === 'postgresql')
				{
					$query->select(
							'CASE WHEN ' . $this->_db->quoteName('v.id') . '<=6 THEN TO_CHAR(' . $this->_db->quoteName('v.id') . ', \'9\') ELSE ' .
									 $this->_db->quoteName('v.title') . ' END');
				}
				else
				{
					$query->select(
							'IF(' . $this->_db->quoteName('v.id') . '<=6, ' . $this->_db->quoteName('v.id') . ', ' . $this->_db->quoteName('v.title') .
									 ')');
				}
				$query->from($this->_db->quoteName('#__viewlevels', 'v'))
					->join('RIGHT',
						$this->_db->quoteName($this->_tbl, 'a') . ' ON ' . $this->_db->quoteName('v.id') . ' = ' . $this->_db->quoteName('a.access'))
					->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);
				$this->_aliases['access'] = (string) $query;
				\JLog::add(new \JLogEntry($this->_aliases['access'], \JLog::DEBUG, 'lib_j2xml'));
			}
		}
		return $ret;
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 * @param
	 *        	boolean Map foreign keys to text values
	 */
	protected function _serialize ()
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$mainTag = strtolower((new \ReflectionClass($this))->getShortName());

		// Initialise variables.
		$xml = array();

		$xml[] = '<' . $mainTag . '>';

		foreach (get_object_vars($this) as $k => $v)
		{
			// If the value is null or non-scalar, or the field is internal
			// ignore it.
			if (! is_scalar($v) || ($k[0] == '_'))
			{
				continue;
			}
			if ($this->_excluded && in_array($k, $this->_excluded))
			{
				continue;
			}
			if ($this->_aliases && array_key_exists($k, $this->_aliases))
			{
				continue;
			}
			else if ($this->_jsonEncode && in_array($k, $this->_jsonEncode))
			{
				$v = json_encode($v, JSON_NUMERIC_CHECK);
			}
			// collapse json variables
			if ($v)
			{
				$x = json_decode($v);
				if (($x != NULL) && ($x != $v))
				{
					$v = json_encode($x, JSON_NUMERIC_CHECK);
				}
			}
			$xml[] = $this->_setValue($k, $v);
		}

		foreach ($this->_aliases as $k => $query)
		{
			\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
			$this->_db->setQuery($query);
			$v = $this->_db->loadObjectList();
			\JLog::add(new \JLogEntry(print_r($v, true), \JLog::DEBUG, 'lib_j2xml'));
			if (count($v) == 1)
			{
				$xml[] = $this->_setValue($k, $v[0]);
			}
			elseif ($v)
			{
				$xml[] = '<' . $k . 'list>';
				foreach ($v as $val)
				{
					$xml[] = $this->_setValue($k, $val);
				}
				$xml[] = '</' . $k . 'list>';
			}
		}

		$xml[] = '</' . $mainTag . '>';

		\JLog::add(new \JLogEntry(implode("\n", $xml), \JLog::DEBUG, 'lib_j2xml'));

		// Return the XML array imploded over new lines.
		return implode("\n", $xml);
	}

	private function _setValue ($k, $v)
	{
		\JLog::add(new \JLogEntry(__METHOD__ . ' ' . print_r($k, true) . ' ' . print_r($v, true), \JLog::DEBUG, 'lib_j2xml'));

		$xml = '';
		if (is_object($v))
		{
			$x = get_object_vars($v);
			if (count($x) == 1)
			{
				$xml = $this->_setValue($k, array_shift($x));
			}
			else
			{
				foreach ($x as $k1 => $v1)
				{
					$xml .= $this->_setValue($k1, $v1);
				}

				// Open root node.
				$xml = '<' . $k . '>' . $xml . '</' . $k . '>';
			}
		}
		else if (is_numeric($v))
		{
			$xml = '<' . $k . '>' . $v . '</' . $k . '>';
		}
		else if ($v != '')
		{
			$v = htmlentities($v, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8");

			$length = strlen($v);
			for ($i = 0; $i < $length; $i ++)
			{
				$current = ord($v{$i});
				if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) || (($current >= 0x20) && ($current <= 0xD7FF)) ||
						 (($current >= 0xE000) && ($current <= 0xFFFD)) || (($current >= 0x10000) && ($current <= 0x10FFFF)))
				{
					$xml .= chr($current);
				}
				else
				{
					$xml .= " ";
				}
			}

			$xml = '<' . $k . '><![CDATA[' . $xml . ']]></' . $k . '>';
		}

		// Return the XML value.
		return $xml;
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML ($mapKeysToText = false)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		return $this->_serialize();
	}

	/**
	 * Method to convert the object to be imported into an array
	 *
	 * @param \SimpleXMLElement $record
	 *        	the object to be imported
	 * @param array $data
	 *        	the array to be imported
	 * @param \JRegistry $params
	 *        	the parameters of the conversation
	 *        
	 * @throws
	 * @return void
	 * @access public
	 */
	public static function prepareData ($record, &$data, $params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$db = \JFactory::getDbo();
		$nullDate = $db->getNullDate();
		$userid = \JFactory::getUser()->id;

		$data = self::xml2array($record);
		// TODO: fix alias
		/**
		 * if (empty($data['alias']))
		 * {
		 * getTableColumns($name, false);
		 *
		 * $data['alias'] = $data['title'] ? $data['title'] : $data['name'];
		 * $data['alias'] = str_replace(' ', '-', $data['alias']);
		 * }
		 */

		$data['checked_out'] = 0;
		$data['checked_out_time'] = $nullDate;

		if (isset($data['catid']))
		{
			$data['catid'] = self::getCategoryId($data['catid'], $params->get('extension'), $params->get('category_default'));
		}
		if (isset($data['created_by']))
		{
			$data['created_by'] = self::getUserId($data['created_by'], $userid);
		}
		if (isset($data['created_user_id']))
		{
			$data['created_user_id'] = self::getUserId($data['created_user_id'], $userid);
		}
		if (isset($data['modified_by']))
		{
			$data['modified_by'] = self::getUserId($data['modified_by'], 0);
		}
		if (isset($data['modified_user_id']))
		{
			$data['modified_user_id'] = self::getUserId($data['modified_user_id'], 0);
		}
		if (isset($data['access']))
		{
			$data['access'] = self::getAccessId($data['access']);
		}
		if (isset($data['publish_up']) && ($data['publish_up'] != $nullDate))
		{
			$data['publish_up'] = self::fixdate($data['publish_up']);
		}
		if (isset($data['publish_down']) && ($data['publish_down'] != $nullDate))
		{
			$data['publish_down'] = self::fixdate($data['publish_down']);
		}
		if (isset($data['created']) && ($data['created'] != $nullDate))
		{
			$data['created'] = self::fixdate($data['created']);
		}
		if (isset($data['modified']) && ($data['modified'] != $nullDate))
		{
			$data['modified'] = self::fixdate($data['modified']);
		}

		$import_fields = $params->get('fields', 0);
		if ($import_fields)
		{
			if (isset($data['field']))
			{
				$data['com_fields'] = array(
						$data['field']['name'] => $data['field']['value']
				);
				unset($data['field']);
			}
			elseif (isset($data['fieldlist']['field']))
			{
				$data['com_fields'] = array();
				foreach ($data['fieldlist']['field'] as $field)
				{
					$data['com_fields'][$field['name']] = $field['value'];
				}
				unset($data['fieldlist']);
			}
		}

		if ((new \JVersion())->isCompatible('3.1') && isset($data['tag']))
		{
			$data['tags'] = (array) self::getTagId($data['tag']);
			unset($data['tag']);
		}
		elseif (isset($data['taglist']))
		{
			$data['tags'] = self::getTagId($data['taglist']);
			unset($data['taglist']);
		}

		if (isset($data['params']))
		{
			$registry = new \JRegistry($data['params']);
			$data['params'] = $registry->toArray();
		}

		\JLog::add(new \JLogEntry(print_r($data, true), \JLog::DEBUG, 'lib_j2xml'));
	}

	/**
	 * Get the article id from path
	 *
	 * @param string $path
	 *        	the path of the article to search for
	 *        
	 * @return int|null the id of the article or null if the article doesn't
	 *         exist
	 */
	public static function getArticleId ($path)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$db = \JFactory::getDBO();
		$i = strrpos($path, '/');
		$article_id = $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('c.id'))
					->from($db->quoteName('#__content', 'c'))
					->join('INNER', $db->quoteName('#__categories', 'cc') . ' ON ' . $db->quoteName('c.catid') . ' = ' . $db->quoteName('cc.id'))
					->where($db->quoteName('cc.extension') . ' = ' . $db->quote('com_content'))
					->where($db->quoteName('c.alias') . ' = ' . $db->quote(substr($path, $i + 1)))
					->where($db->quoteName('cc.path') . ' = ' . $db->quote(substr($path, 0, $i))))
			->loadResult();

		\JLog::add(new \JLogEntry($path . ' -> ' . $article_id, \JLog::DEBUG, 'lib_j2xml'));
		return $article_id;
	}

	/**
	 * Get the user id from the username
	 *
	 * @param string $username
	 *        	the username of the user to search for
	 *        
	 * @return int the id of the user if it exists or the default user id
	 */
	public static function getUserId ($username, $default_user_id = null)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$db = \JFactory::getDBO();
		$user_id = $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__users'))
					->where($db->quoteName('username') . ' = ' . $db->quote($username)))
			->loadResult();

		$user_id = $user_id ?: ($default_user_id ?: \JFactory::getUser()->id);

		\JLog::add(new \JLogEntry($username . ' -> ' . $user_id, \JLog::DEBUG, 'lib_j2xml'));
		return $user_id;
	}

	/**
	 *
	 * @param string $usergroup
	 * @param boolean $import
	 *
	 *
	 * @return boolean|mixed|stdClass|void|NULL
	 * @return mixed The usergroup id on success, boolean false on failure.
	 */
	public static function getUsergroupId ($usergroup, $import = true)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		if (empty($usergroup))
		{
			$usergroup_id = \JComponentHelper::getParams('com_users')->get('new_usertype');
		}
		elseif (! is_numeric($usergroup))
		{
			$db = \JFactory::getDBO();
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__usergroups'))
				->where('usergroups_getpath(' . $db->quoteName('id') . ') = ' . $db->quote($usergroup));
			\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
			$usergroup_id = $db->setQuery($query)->loadResult();
			if ($import && ! $usergroup_id)
			{
				// import usergroup tree if it doesn't exists
				$groups = json_decode($usergroup);
				$g = array();
				$usergroup_id = 0;
				$parent_id = 0;
				for ($j = 0; $j < count($groups); $j ++)
				{
					$g[] = $groups[$j];
					$usergroup = json_encode($g, JSON_NUMERIC_CHECK);
					$query = $db->getQuery(true)
						->select($db->quoteName('id'))
						->from($db->quoteName('#__usergroups'))
						->where($db->quoteName('title') . ' = ' . $db->quote($groups[$j]))
						->where($db->quoteName('parent_id') . ' = ' . $parent_id);
					$usergroup_id = $db->setQuery($query)->loadResult();
					if (! ($usergroup_id = $db->setQuery($query)->loadResult()))
					{
						$u = \JTable::getInstance('Usergroup');
						$u->save(array(
								'title' => $groups[$j],
								'parent_id' => $parent_id
						));
						$usergroup_id = $u->id;
						\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USERGROUP_IMPORTED', $groups[$j]), \JLog::INFO, 'lib_j2xml'));
					}
					else
					{
						$parent_id = $usergroup_id;
					}
				}
			}
		}
		elseif ($usergroup > 0)
		{
			$usergroup_id = $usergroup;
		}
		else
		{
			$usergroup_id = ComponentHelper::getParams('com_users')->get('new_usertype');
		}

		\JLog::add(new \JLogEntry($usergroup . ' -> ' . $usergroup_id, \JLog::DEBUG, 'lib_j2xml'));

		return $usergroup_id;
	}

	public static function getAccessId ($access)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		if (is_numeric($access))
			return $access;

		$db = \JFactory::getDBO();
		$access_id = $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__viewlevels'))
					->where($db->quoteName('title') . ' = ' . $db->quote($access)))
			->loadResult();
		if (! $access_id)
		{
			$access_id = 3;
		}

		\JLog::add(new \JLogEntry($access . ' -> ' . $access_id, \JLog::DEBUG, 'lib_j2xml'));
		return $access_id;
	}

	public static function getCategoryId ($category, $extension, $default_category = 0)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		if (is_numeric($category))
			return $category;

		$db = \JFactory::getDBO();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('path') . ' = ' . $db->quote($category))
			->where($db->quoteName('extension') . ' = ' . $db->quote($extension));
		\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
		$category_id = $db->setQuery($query)->loadResult();
		if (! $category_id)
		{
			$category_id = $default_category;
		}

		\JLog::add(new \JLogEntry($extension . '/' . $category . ' -> ' . $category_id, \JLog::DEBUG, 'lib_j2xml'));
		return $category_id;
	}

	/**
	 * get tag id from tag path
	 *
	 * @param string|array $tags
	 *        	tag path
	 *        
	 * @return mixed An array with tag ids, a single id or false if an error
	 *         occurs
	 *        
	 * @since 14.8.240
	 */
	public static function getTagId ($tag)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$db = \JFactory::getDbo();

		try
		{
			if (is_array($tag))
			{
				$tags = array_unique($tag);
				$query = 'SELECT CASE WHEN b.id IS NOT NULL THEN b.id ELSE CONCAT(\'#new#\', a.path) END FROM (' . 'SELECT ' .
						 $db->quote(array_shift($tags)) . ' as path';
				foreach ($tags as $tag)
				{
					$query .= ' UNION ALL SELECT ' . $db->quote($tag);
				}
				$query .= ') a LEFT JOIN #__tags b on a.path = b.path';
				\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
				return $db->setQuery($query)->loadColumn();
			}
			else
			{
				$query = $db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__tags'))
					->where($db->quoteName('path') . ' = ' . $db->quote($tag));
				\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
				return $db->setQuery($query)->loadResult();
			}
		}
		catch (\Exception $e)
		{
		}

		return false;
	}

	/**
	 * fix the datetime
	 *
	 * @param string $date
	 *        	the datetime to be fixed
	 *        
	 * @return string the fixed datetime
	 *        
	 * @since 18.8.301
	 */
	protected static function fixDate ($date)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		return (($date == '0000-00-00 00:00:00') || ($date == '1970-01-01 00:00:00')) ? \JFactory::getDbo()->getNullDate() : (new \JDate($date))->toSQL(
				false);
	}

	/**
	 * function xml2array
	 *
	 * @params mixed $xmlObject
	 * @params array $out
	 *
	 * @since 19.2.320
	 */
	static function xml2array ($xmlObject, $out = array ())
	{
		if (is_object($xmlObject))
		{
			if (count($xmlObject->children()) === 0)
			{
				return preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($xmlObject));
			}
			foreach ((array) $xmlObject as $index => $node)
			{
				$out[$index] = self::xml2array($node);
			}
		}
		elseif (is_array($xmlObject))
		{
			foreach ($xmlObject as $index => $node)
			{
				$node = self::xml2array($node);
				$out[$index] = $node;
			}
		}
		elseif (is_string($xmlObject))
		{
			return preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($xmlObject));
		}
		else
		{
			return $xmlObject;
		}

		return $out;
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param Registry $params
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
	public static function import ($xml, $params) {}

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
	public static function export ($id, &$xml, $options) {}
}

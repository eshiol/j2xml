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
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or die();

use Joomla\CMS\Component\ComponentHelper;

/**
 * Table
 *
 * @version __DEPLOY_VERSION__
 * @since 1.5.3.39
 */
class Table extends \JTable
{
	/**
	 * An array of key names to be not exported
	 *
	 * @var array
	 * @since 1.5.3.39
	 */
	protected $_excluded = array();

	/**
	 * An array of key names to be exported as alias
	 *
	 * @var array
	 * @since 1.5.3.39
	 */
	protected $_aliases = array();

	/**
	 * An array of key names to be exported in json encoded format
	 *
	 * @var array
	 * @since __DEPLOY_VERSION__
	 */
	protected $_jsonEncode = array();

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
		if ($ret = parent::load($keys, $reset))
		{
			if (isset($this->created_by))
			{
				$this->_aliases['created_by'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('username'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->created_by);
			}
			if (isset($this->created_user_id))
			{
				$this->_aliases['created_user_id'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('username'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->created_user_id);
			}
			if (isset($this->modified_by))
			{
				$this->_aliases['modified_by'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('username'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->modified_by);
			}
			if (isset($this->modified_user_id))
			{
				$this->_aliases['modified_user_id'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('username'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->modified_user_id);
			}
			if (isset($this->catid))
			{
				$this->_aliases['catid'] = (string) $this->_db->getQuery(true)
					->select($this->_db->quoteName('path'))
					->from($this->_db->quoteName('#__categories'))
					->where($this->_db->quoteName('id') . ' = ' . (int) $this->catid);
			}
			if (isset($this->access))
			{
				// $this->_aliases['access']='SELECT IF(f.id<=6,f.id,f.title)
				// FROM #__viewlevels f RIGHT JOIN '.$this->_tbl.' a ON f.id =
				// a.access WHERE a.id = '. (int)$this->id;
				$query = $this->_db->getQuery(true);
				$version = new \JVersion();
				$serverType = $version->isCompatible('3.5') ? $this->_db->getServerType() : 'mysql';

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
			}
		}
		return $ret;
	}

	/**
	 * Export item list to xml
	 *
	 * @param
	 *        	bool tag use the main class tag
	 * @access public
	 * @param
	 *        	boolean Map foreign keys to text values
	 */
	protected function _serialize ($tag = true)
	{
		// Initialise variables.
		$xml = array();

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
			try
			{
				$v = $this->_db->setQuery($query)->loadObjectList();
			}
			catch (\Exception $ex)
			{
				if ($ex->getCode() != 1305)
				{
					throw $ex;
				}
				elseif (self::fixDB())
				{
					$v = $this->_db->setQuery($query)->loadObjectList();
				}
				else
				{
					throw $ex;
				}
			}

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

		// Return the XML array imploded over new lines.
		if ($tag)
		{
			$class = new \ReflectionClass($this);
			$mainTag = strtolower($class->getShortName());
			$ret = '<' . $mainTag . '>' . implode("\n", $xml) . '</' . $mainTag . '>';
		}
		else
		{
			$ret = implode("\n", $xml);
		}

		// Return the XML array imploded over new lines.
		return $ret;
	}

	protected function _setValue ($k, $v)
	{
		$kOpen = $k;
		/**
		if (! is_null($attributes))
		{
			foreach ($attributes as $ak => $av)
			{
				$kOpen .= ' ' . $ak . '="' . $av . '"';
			}
		}
		 */
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
					if (substr($k1, 0, 1) != '@')
					{
						$xml .= $this->_setValue($k1, $v1);
					}
					else
					{
						$kOpen .= ' ' . substr($k1, 1) . '="' . $v1 . '"';
					}
				}
				// Open root node.
				$xml = '<' . $kOpen . '>' . $xml . '</' . $k . '>';
			}
		}
		else if (is_numeric($v))
		{
			$xml = '<' . $kOpen . '>' . $v . '</' . $k . '>';
		}
		else if ($v != '')
		{
			// $v = htmlentities($v, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8");
			$length = strlen($v);
			for ($i = 0; $i < $length; $i ++)
			{
				$current = ord($v[$i]);
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

			$xml = '<' . $kOpen . '><![CDATA[' . $xml . ']]></' . $k . '>';
		}
		else
		{
			$xml = '<' . $kOpen . ' />';
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
		$db = \JFactory::getDbo();
		$nullDate = $db->getNullDate();
		$userid = \JFactory::getUser()->id;

		//$data = self::xml2array($record, $params->get('version') == '12.5.0');
		$data = self::xml2array($record, version_compare($params->get('version', '19.2.0'), '19.2.0', 'lt'));

		// TODO: fix alias
		/**
		if (empty($data['alias']))
		{
			getTableColumns($name, false);

			$data['alias'] = $data['title'] ? $data['title'] : $data['name'];
			$data['alias'] = str_replace(' ', '-', $data['alias']);
		}
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

		if (($params->get('version') == '15.9.0') || ($params->get('version') == '12.5.0'))
		{
			if (isset($data['title']))
			{
				$data['title'] = htmlspecialchars_decode($data['title']);
			}
			if (isset($data['introtext']))
			{
				$data['introtext'] = htmlspecialchars_decode($data['introtext']);
			}
			if (isset($data['fulltext']))
			{
				$data['fulltext'] = htmlspecialchars_decode($data['fulltext']);
			}
			if (isset($data['description']))
			{
				$data['description'] = htmlspecialchars_decode($data['description']);
			}
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

		$version = new \JVersion();
		if ($version->isCompatible('3.1') && isset($data['tag']))
		{
			$data['tags'] = (array) self::getTagId($data['tag']);
			unset($data['tag']);
		}
		elseif (isset($data['taglist']))
		{
			$data['tags'] = self::getTagId($data['taglist']['tag']);
			unset($data['taglist']);
		}

		if (isset($data['params']))
		{
			$registry = new \JRegistry($data['params']);
			$data['params'] = $registry->toArray();
		}
	}

	/**
	 * Get the article id from the article path
	 *
	 * @param string $article
	 *        	the path of the article to search for
	 * @param int $defaultArticleId
	 *        	the id to return if the article doesn't exist
	 *
	 * @return int the id of the article if it exists or the default article id
	 */
	public static function getArticleId ($article, $defaultArticleId = 0)
	{
		if (is_numeric($article))
		{
			$articleId = $article;
		}
		else
		{
			$db = \JFactory::getDBO();
			$i = strrpos($article, '/');
			$query = $db->getQuery(true)
				->select($db->quoteName('c.id'))
				->from($db->quoteName('#__content', 'c'))
				->join('INNER', $db->quoteName('#__categories', 'cc') . ' ON ' . $db->quoteName('c.catid') . ' = ' . $db->quoteName('cc.id'))
				->where($db->quoteName('cc.extension') . ' = ' . $db->quote('com_content'))
				->where($db->quoteName('c.alias') . ' = ' . $db->quote(substr($article, $i + 1)))
				->where($db->quoteName('cc.path') . ' = ' . $db->quote(substr($article, 0, $i)));
			$articleId = $db->setQuery($query)->loadResult();
			if (! $articleId)
			{
				$articleId = $defaultArticleId;
			}
		}

		return $articleId;
	}

	/**
	 * Get the user id from the username
	 *
	 * @param string $username
	 *        	the username of the user to search for
	 * @param int $defaultUserId
	 *        	the id to return if the user doesn't exist
	 *
	 * @return int the id of the user if it exists or the default user id
	 */
	public static function getUserId ($username, $defaultUserId = null)
	{
		$db = \JFactory::getDBO();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('username') . ' = ' . $db->quote($username));
		$userId = $db->setQuery($query)->loadResult();

		$userId = $userId ?: ($defaultUserId ?: \JFactory::getUser()->id);

		return $userId;
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
		if (empty($usergroup))
		{
			$usergroupId = \JComponentHelper::getParams('com_users')->get('new_usertype');
		}
		elseif (! is_numeric($usergroup))
		{
			$db = \JFactory::getDBO();
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__usergroups'))
				->where('usergroups_getpath(' . $db->quoteName('id') . ') = ' . $db->quote($usergroup));

			try
			{
				$usergroupId = $db->setQuery($query)->loadResult();
			}
			catch (\Exception $ex)
			{
				if ($ex->getCode() != 1305)
				{
					throw $ex;
				}
				elseif (self::fixDB())
				{
					$usergroupId = $db->setQuery($query)->loadResult();
				}
				else
				{
					throw $ex;
				}

				if ($ex->getCode() != 1305)
				{
					throw $ex;
				}
				elseif (self::fixDB())
				{
					$usergroupId = $db->setQuery($query)->loadResult();
				}
				else
				{
					throw $ex;
				}
			}

			if ($import && ! $usergroupId)
			{
				// import usergroup tree if it doesn't exists
				$groups = json_decode($usergroup);
				$g = array();
				$usergroupId = 0;
				$parentId = 0;
				for ($j = 0; $j < count($groups); $j ++)
				{
					$g[] = $groups[$j];
					$usergroup = json_encode($g, JSON_NUMERIC_CHECK);
					$query = $db->getQuery(true)
						->select($db->quoteName('id'))
						->from($db->quoteName('#__usergroups'))
						->where($db->quoteName('title') . ' = ' . $db->quote($groups[$j]))
						->where($db->quoteName('parent_id') . ' = ' . $parentId);
					$usergroupId = $db->setQuery($query)->loadResult();
					if (! ($usergroupId = $db->setQuery($query)->loadResult()))
					{
						$u = \JTable::getInstance('Usergroup');
						$u->save(array(
							'title' => $groups[$j],
							'parent_id' => $parentId
						));
						$usergroupId = $u->id;
						\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USERGROUP_IMPORTED', $groups[$j]), \JLog::INFO, 'lib_j2xml'));
					}
					else
					{
						$parentId = $usergroupId;
					}
				}
			}
		}
		elseif ($usergroup > 0)
		{
			$usergroupId = $usergroup;
		}
		else
		{
			$usergroupId = ComponentHelper::getParams('com_users')->get('new_usertype');
		}

		return $usergroupId;
	}

	public static function getAccessId ($access)
	{
		if (is_numeric($access))
		{
			$accessId = $access;
		}
		else
		{
			$db = \JFactory::getDBO();
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__viewlevels'))
				->where($db->quoteName('title') . ' = ' . $db->quote($access));
			$accessId = $db->setQuery($query)->loadResult();
		}
		if (! $accessId)
		{
			$accessId = 3;
		}

		return $accessId;
	}

	/**
	 * Get the category id from the category path
	 *
	 * @param string $category
	 *        	the path of the category to search for
	 * @param int $defaultCategoryId
	 *        	the id to return if the category doesn't exist
	 *
	 * @return int the id of the category if it exists or the default category
	 *         id
	 */
	public static function getCategoryId ($category, $extension, $defaultCategoryId = 0)
	{
		if (is_numeric($category))
		{
			$categoryId = $category;
		}
		else
		{
			$db = \JFactory::getDBO();
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__categories'))
				->where($db->quoteName('path') . ' = ' . $db->quote($category))
				->where($db->quoteName('extension') . ' = ' . $db->quote($extension));
			$categoryId = $db->setQuery($query)->loadResult();
		}
		if (! $categoryId)
		{
			$categoryId = $defaultCategoryId;
		}

		return $categoryId;
	}

	/**
	 * get tag id from tag path
	 *
	 * @param string|array $tag
	 *        	tag path
	 *
	 * @return mixed An array with tag ids, a single id or false if an error
	 *         occurs
	 *
	 * @since 14.8.240
	 */
	public static function getTagId ($tag)
	{
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
				$tagId = $db->setQuery($query)->loadColumn();
			}
			else
			{
				$query = $db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__tags'))
					->where($db->quoteName('path') . ' = ' . $db->quote($tag));
				$tagId = $db->setQuery($query)->loadResult();
			}
		}
		catch (\Exception $ex)
		{
			$tagId = false;
		}

		return $tagId;
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
		$d = new \JDate($date);
		return (($date == '0000-00-00 00:00:00') || ($date == '1970-01-01 00:00:00')) ? \JFactory::getDbo()->getNullDate() : $d->toSQL(false);
	}

	/**
	 * function xml2array
	 *
	 * @params mixed $xmlObject
	 * @params array $out
	 *
	 * @since 19.2.320
	 */
	private static function xml2array ($xmlObject, $htmlEntityDecode = false, $out = null)
	{
		if (is_object($xmlObject))
		{
			if ($a = $xmlObject->attributes())
			{
				foreach ($a as $k => $v)
				{
					$out[$k] = (string) $v;
				}
			}
			if (count($xmlObject->children()) === 0)
			{
				if (trim($xmlObject))
				{
					$v = preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($xmlObject));
					if ($htmlEntityDecode)
					{
						$v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
					}
					if ($a)
					{
						$out['value'] = $v;
					}
					else
					{
						$out = $v;
					}
				}
			}
			else
			{
				foreach ((array) $xmlObject as $index => $node)
				{
					$out[$index] = self::xml2array($node, $htmlEntityDecode);
				}
			}
		}
		elseif (is_array($xmlObject))
		{
			foreach ($xmlObject as $index => $node)
			{
				$out[$index] = self::xml2array($node, $htmlEntityDecode);
			}
		}
		elseif (is_string($xmlObject))
		{
			$out = preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($xmlObject));
			if ($htmlEntityDecode)
			{
				$out = html_entity_decode($out, ENT_QUOTES, 'UTF-8');
			}
		}
		else
		{
			$out = $xmlObject;
			if ($htmlEntityDecode)
			{
				$out = html_entity_decode($out, ENT_QUOTES, 'UTF-8');
			}
		}

		return $out;
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
	}

	/**
	 * Overloaded bind function.
	 *
	 * @param array $array
	 *        	Named array.
	 * @param mixed $ignore
	 *        	An optional array or space separated list of properties to
	 *        	ignore while binding.
	 *
	 * @return mixed Null if operation was satisfactory, otherwise returns an
	 *         error
	 *
	 * @see Table::bind()
	 * @since 19.2.327
	 */
	public function bind ($array, $ignore = '')
	{
		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new \JRegistry($array['params']);
			$array['params'] = (string) $registry;
		}

		// Bind the rules.
		if (isset($array['rules']) && is_array($array['rules']))
		{
			$rules = new Rules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Get the menu id from the menu path
	 *
	 * @param string $menu
	 *        	the path of the menu to search for
	 * @param int $defaultMenuId
	 *        	the id to return if the menu doesn't exist
	 *
	 * @return int the id of the menu if it exists or the default menu id
	 */
	public static function getMenuId ($menu, $defaultMenuId = 0)
	{
		if (is_numeric($menu))
		{
			$menuId = $menu;
		}
		else
		{
			$db = \JFactory::getDBO();
			$query = $db->getQuery(true);
			$path = $query->concatenate(array($db->quoteName('menutype'), $db->quoteName('path')), '/');
			$query->select($db->quoteName('id'))
				->from($db->quoteName('#__menu'))
				->where($path . ' = ' . $db->quote($menu));
			$menuId = $db->setQuery($query)->loadResult();
		}
		if (! $menuId)
		{
			$menuId = $defaultMenuId;
		}

		return $menuId;
	}

	/**
	 *
	 * Create usergroups_getpath if it does not exist
	 *
	 * @return boolean
	 *
	 * @since 19.7.335
	 */
	public static function fixDB()
	{
		$db = \JFactory::getDBO();

		$queries = array();
		$query = $db->getQuery(true);

		$version = new \JVersion();
		$serverType = $version->isCompatible('3.5') ? $db->getServerType() : 'mysql';

		if ($serverType === 'mysql')
		{
			$queries[] = "DROP PROCEDURE IF EXISTS usergroups_getpath;";
			$queries[] = preg_replace('!\s+!', ' ', <<<EOL
CREATE PROCEDURE usergroups_getpath(IN id INT, OUT path TEXT)
BEGIN
    DECLARE temp_title VARCHAR(100);
    DECLARE temp_path TEXT;
    DECLARE temp_parent INT;
	SET max_sp_recursion_depth = 255;

	SELECT a.title, a.parent_id FROM #__usergroups a WHERE a.id=id INTO temp_title, temp_parent;

	IF temp_parent = 0
    THEN
       SET path = temp_title;
    ELSE
        CALL usergroups_getpath(temp_parent, temp_path);
        SET path = CONCAT(temp_path, '","', temp_title);
    END IF;
END;
EOL
);
			$queries[] = "DROP FUNCTION IF EXISTS usergroups_getpath;";
			$queries[] = preg_replace('!\s+!', ' ', <<<EOL
CREATE FUNCTION usergroups_getpath(id INT) RETURNS TEXT DETERMINISTIC
BEGIN
    DECLARE res TEXT;
    CALL usergroups_getpath(id, res);
    RETURN CONCAT('["', res, '"]');
END;
EOL
);
		}
		elseif ($serverType === 'postgresql')
		{
			$queries[] = <<<EOL
CREATE OR REPLACE FUNCTION usergroups_getpath(id INT, level INT default 0) RETURNS TEXT
AS $$
DECLARE temp_title VARCHAR(100);
	temp_path TEXT;
	temp_parent INT;
BEGIN
	SELECT a.title, a.parent_id FROM #__usergroups a WHERE a.id = $1 INTO temp_title, temp_parent;

	IF temp_parent = 0
	THEN
		temp_path := temp_title;
	ELSE
		temp_path := CONCAT(usergroups_getpath(temp_parent, $2 + 1), '","', temp_title);
	END IF;
	IF $2 = 0
	THEN
		temp_path = CONCAT('["', temp_path, '"]');
	END IF;
	RETURN temp_path;
END;
$$ LANGUAGE plpgsql;
EOL;
		}

		if ($queries)
		{
			// Process each query in the $queries array (split out of sql file).
			foreach ($queries as $query)
			{
				$db->setQuery($db->convertUtf8mb4QueryToUtf8($query));

				try
				{
					$db->execute();
				}
				catch (\JDatabaseExceptionExecuting $e)
				{
					return false;
				}
			}
		}
		return true;
	}
}

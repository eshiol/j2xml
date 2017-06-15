<?php
/**
 * @version		17.6.299 libraries/eshiol/j2xml/table.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.3.39
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

if (!defined('ENT_IGNORE')) define('ENT_IGNORE', 0);
if (!defined('ENT_SUBSTITUTE')) define('ENT_SUBSTITUTE', ENT_IGNORE);

class eshTable extends JTable
{
	/**
	 * Name of the database table to model.
	 *
	 * @var		string
	 * @since	1.0
	 */
	public $_tbl	= '';

	/**
	 * Name of the primary key field in the table.
	 *
	 * @var		string
	 * @since	1.0
	 */
	public $_tbl_key = '';

	/**
	 * JDatabase connector object.
	 *
	 * @var		object
	 * @since	1.0
	 */
	public $_db;

	protected $_excluded;
	protected $_aliases;
	protected $_jsons;

	/**
	 * Object constructor to set table and key fields.  In most cases this will
	 * be overridden by child classes to explicitly set the table and key fields
	 * for a particular database table.
	 *
	 * @param	string Name of the table to model.
	 * @param	string Name of the primary key field in the table.
	 * @param	object JDatabase connector object.
	 * @since	1.0
	 */
	function __construct($table, $key, &$db)
	{
		parent::__construct($table, $key, $db);

		$this->_excluded = array('asset_id','parent_id','lft','rgt','level','checked_out','checked_out_time'); 
		$this->_aliases = array();
		$this->_jsons = array(); //array('params', 'metadata', 'attribs', 'images', 'urls');

	}

	/**
	 * Method to load a row from the database by primary key and bind the fields
	 * to the JTable instance properties.
	 *
	 * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.  If not
	 *                           set the instance property value is used.
	 * @param   boolean  $reset  True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful. False if row not found.
	 *
	 * @link    https://docs.joomla.org/JTable/load
	 * @since   11.1
	 * @throws  InvalidArgumentException
	 * @throws  RuntimeException
	 * @throws  UnexpectedValueException
	 */
	public function load($keys = null, $reset = true)
	{
		if ($ret = parent::load($keys, $reset))
		{
			if (isset($this->created_by))
				$this->_aliases['created_by']='SELECT username FROM #__users WHERE id = '.(int)$this->created_by;
			if (isset($this->created_user_id))
				$this->_aliases['created_user_id']='SELECT username FROM #__users WHERE id = '.(int)$this->created_user_id;
			if (isset($this->modified_by))
				$this->_aliases['modified_by']='SELECT username FROM #__users WHERE id = '.(int)$this->modified_by;
			if (isset($this->modified_user_id))
				$this->_aliases['modified_user_id']='SELECT username FROM #__users WHERE id = '.(int)$this->modified_user_id;
			if (isset($this->catid))
				$this->_aliases['catid']='SELECT path FROM #__categories WHERE id = '.(int)$this->catid;
			if (isset($this->access))
				$this->_aliases['access']='SELECT IF(f.id<=6,f.id,f.title) FROM #__viewlevels f RIGHT JOIN '.$this->_tbl.' a ON f.id = a.access WHERE a.id = '. (int)$this->id;
		}
		return $ret;
	}

	/**
	 * Export item list to xml
	 * 
	 * @return string
	 */
	protected function _serialize()
	{
		// Initialise variables.
		$xml = array();

		$xml[] = '<'.strtolower(str_replace('eshTable', '', get_class($this))).'>';

		foreach (get_object_vars($this) as $k => $v)
		{
			// If the value is null or non-scalar, or the field is internal ignore it.
			if (!is_scalar($v) || ($k[0] == '_'))
				continue;
			if ($this->_excluded && in_array($k, $this->_excluded))
				continue;
			if ($this->_aliases && array_key_exists($k, $this->_aliases))
				continue;
			else if ($this->_jsons && in_array($k, $this->_jsons))
				$v = json_encode($v, JSON_NUMERIC_CHECK);
			// collapse json variable

			if ($v)
			{
				$x = json_decode($v);
				if (($x != NULL) && ($x != $v))
					$v = json_encode($x, JSON_NUMERIC_CHECK);
			}
			$xml[] = $this->_setValue($k, $v);
		}

		foreach($this->_aliases as $k => $query)
		{
			$this->_db->setQuery($query);
		
			$v = $this->_db->loadRowList();
			if (count($v) > 1)
			{
				$xml[] = '<'.$k.'list>';
			}
			foreach ($v as $val)
			{
				if (count($val) == 2)
				{
					$xml[] = '<'.$k.'>';
					$xml[] = $this->_setValue($val[0], $val[1]);
					$xml[] = '</'.$k.'>';
				}
				else 
				{
					$xml[] = $this->_setValue($k, $val[0]);
				}
			}
			if (count($v) > 1)
			{
				$xml[] = '</'.$k.'list>';
			}
		}

		$xml[] = '</'.strtolower(str_replace('eshTable', '', get_class($this))).'>';

		// Return the XML array imploded over new lines.
		return implode("\n", $xml);
	}

	private function _setValue($k, $v)
	{
		// Open root node.
		$xml = '<'.$k.'>';
		// Set value.
		if (is_numeric($v))
//			$xml .= '<![CDATA['.$v.']]>';
			$xml .= $v;
		else if ($v != '')
		{
			$xml .= '<![CDATA[';
			$v = htmlentities($v, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8");

			$length = strlen($v);
			for ($i=0; $i < $length; $i++)
			{
				$current = ord($v{$i});
				if (($current == 0x9) ||
					($current == 0xA) ||
					($current == 0xD) ||
					(($current >= 0x20) && ($current <= 0xD7FF)) ||
					(($current >= 0xE000) && ($current <= 0xFFFD)) ||
					(($current >= 0x10000) && ($current <= 0x10FFFF)))
				{
					$xml .= chr($current);
				}
				else
				{
					$xml .= " ";
				}
			}
			$xml .= ']]>';
		}
		// Close root node.
		$xml .= '</'.$k.'>';
		// Return the XML value.
		return $xml;
	}

	/**
	 * Export item list to xml
	 * 
	 * @param boolean $mapKeysToText	Map foreign keys to text values
	 * 
	 * @return string
	 */
	function toXML($mapKeysToText = false)
	{
		return $this->_serialize();
	}
}

<?php
/**
 * @version		15.2.246 libraries/eshiol/j2xml/exporter.php
 * 
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.5.2.14
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2015 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

//Import filesystem libraries.
jimport('joomla.filesystem.file');

jimport('eshiol.j2xml.table');
jimport('eshiol.j2xml.table.category');
jimport('eshiol.j2xml.table.content');
jimport('eshiol.j2xml.table.tag');
jimport('eshiol.j2xml.table.user');
jimport('eshiol.j2xml.table.weblink');
jimport('eshiol.j2xml.version');

class J2XMLExporter
{
	static $image_match_string = '/<img.*?src="([^"]*)".*?[^>]*>/s';
	// images/stories is path of the images of the sections and categories hard coded in the file \libraries\joomla\html\html\list.php at the line 52
	static $image_path = "images";

	private static $initialized = false;
	
	/*
	 * Export content articles, images, section and categories
	 * @return 		xml string
	 * @since		1.5.2.14
	 */
	static function contents($ids, $export_images, $export_categories, $export_users, &$images)
	{		
		self::initialize();
		$xml = '';		

		$admin = 42;
		
		$categories = array();		
		$tags = array();

		$user = JTable::getInstance('user','eshTable');
		$users = array();

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}
		
		foreach($ids as $id)
		{
			$item = JTable::getInstance('content', 'eshTable');
			$item->load($id);

			if ($export_users)
			{
				if (($item->created_by != $admin) 
				&& (!array_key_exists($item->created_by, $users)))
				{
					$user->load($item->created_by);
					$users[$item->created_by] = $user->toXML();
				}
				if (($item->modified_by != $admin) 
					&& ($item->modified_by != 0) 
					&& (!array_key_exists($item->modified_by, $users)))
				{
					$user->load($item->modified_by);
					$users[$item->modified_by] = $user->toXML();
				}
			}	

			if ($export_categories && ($item->catid > 0))
				self::_category($item->catid, $export_images, $images, $categories, $export_users);				

			if (class_exists('JHelperTags'))
			{
				$htags = new JHelperTags;
				$itemtags = $htags->getItemTags('com_content.article', $id);
				foreach ($itemtags as $itemtag)
					self::_tag($itemtag->tag_id, $export_images, $images, $tags);
			}
						
			if ($export_images)
			{
				$text = $item->introtext.$item->fulltext;
				$image = preg_match_all(self::$image_match_string,$text,$matches,PREG_PATTERN_ORDER);
				if (count($matches[1]) > 0)
				{
					for ($i = 0; $i < count($matches[1]); $i++)
					{
						$image = $matches[1][$i];						
						$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($image));
						if (!array_key_exists($image, $images) && JFile::exists($file_path))
							$images[$image] = "\t\t<img src=\"".htmlentities($image, ENT_QUOTES, "UTF-8")."\">"
								."\t\t\t".base64_encode(file_get_contents($file_path))
								."\t\t</img>\n";
					}
				}
				
				$imgs = json_decode($item->images);
				
				if ($imgs)
				{
					$image = $imgs->image_fulltext;
					$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($image));
					if (!array_key_exists($image, $images) && JFile::exists($file_path))
						$images[$image] = "\t\t<img src=\"".htmlentities($image, ENT_QUOTES, "UTF-8")."\">"
						."\t\t\t".base64_encode(file_get_contents($file_path))
						."\t\t</img>\n";
					
					$image = $imgs->image_intro;
					$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($image));
					if (!array_key_exists($image, $images) && JFile::exists($file_path))
						$images[$image] = "\t\t<img src=\"".htmlentities($image, ENT_QUOTES, "UTF-8")."\">"
						."\t\t\t".base64_encode(file_get_contents($file_path))
						."\t\t</img>\n";
				}
			}
			$xml .= $item->toXML();
		}
		foreach($categories as $category)
			$xml .= $category;
		foreach($users as $user)
			$xml .= $user;
		foreach($tags as $tag)
			$xml .= $tag;
		return $xml;
	}

	/*
	 * Export users
	 * @return 		xml string
	 * @since		1.5.3beta4.39
	 */
	static function users($ids)
	{		
		self::initialize();
		$xml = '';
		
		foreach($ids as $id)
		{
			$item = JTable::getInstance('user', 'eshTable');
			$item->load($id);

			$xml .= $item->toXML();
		}
		
		return $xml;
	}

	/*
	 * Export categories
	 * @return 		xml string
	 * @since		1.5.3beta5.43
	 */
	static function categories($ids, $export_images, $export_users, &$images)
	{		
		$categories = array();
		self::initialize();	
		$xml = '';
		
		foreach($ids as $id)
			$xml .= self::_category($id, $export_images, $images, $categories, $export_users, true);

		return $xml;
	}

	static function export($xml, $debug, $export_gzip)
	{
		if ($debug > 0)
		{
			$app = JFactory::getApplication();
			$data = ob_get_contents();
			if ($data)
			{	
				$app->enqueueMessage(JText::_('LIB_J2XML_MSG_ERROR_EXPORT'), 'error');
					$app->enqueueMessage($data, 'error');
				return false;
			}
		}
		ob_clean();
			
		$version = explode(".", J2XMLVersion::$DOCVERSION);
		$xmlVersionNumber = $version[0].$version[1].substr('0'.$version[2], strlen($version[2])-1);
		
		$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
		$data .= J2XMLVersion::$DOCTYPE."\n";
		$data .= "<j2xml version=\"".J2XMLVersion::$DOCVERSION."\">\n";
		$data .= $xml; 
		$data .= "</j2xml>";
		// modify the MIME type
		$document = JFactory::getDocument();
		if ($export_gzip)
		{
			$document->setMimeEncoding('application/gzip-compressed', true);
			JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml'.$xmlVersionNumber.date('YmdHis').'.gz"', true);
			$data = gzencode($data, 9);
		}
		else 
		{
			$document->setMimeEncoding('application/xml', true);
			JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml'.$xmlVersionNumber.date('YmdHis').'.xml"', true);
		}
		echo $data;
		return true;
	}

	/*
	 * Export category
	 * @return 		xml string
	 * @since		1.6.1.60
	 */
	private static function _category($id, $export_images, &$images, &$categories, $export_users = true, $export_content = false)
	{
		$xml = '';
		if (!array_key_exists($id, $categories))
		{
			$item = JTable::getInstance('category', 'eshTable');
			$item->load($id);
				
			if ($item->parent_id > 1)
				$xml .= self::_category($item->parent_id, $export_images, $images, $categories, $export_users);
			
			$xml .= $categories[$id] = $item->toXML();

			if ($export_images)
			{
				$text = html_entity_decode($item->description);
				$image = preg_match_all(self::$image_match_string,$text,$matches,PREG_PATTERN_ORDER);
				if (count($matches[1]) > 0)
				{
					for ($i = 0; $i < count($matches[1]); $i++)
					{
						$image = $matches[1][$i];						
						$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($matches[1][$i]));
						if (!array_key_exists($image, $images) && JFile::exists($file_path))
							$images[$image] = "\t\t<img src=\"".$image."\">"
								."\t\t\t".base64_encode(file_get_contents($file_path))
								."\t\t</img>\n";
					}
				}
				$imgs = json_decode($item->params);				
				if ($imgs)
				{
					$image = $imgs->image;
					$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($image));
					if (!array_key_exists($image, $images) && JFile::exists($file_path))
						$images[$image] = "\t\t<img src=\"".htmlentities($image, ENT_QUOTES, "UTF-8")."\">"
						."\t\t\t".base64_encode(file_get_contents($file_path))
						."\t\t</img>\n";
				}
			}

			if ($export_content)
			{
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select('id')
					->from('#__content')
					->where('catid = '.$id);
				$db->setQuery($query);
				$ids_content = $db->loadColumn();
				foreach ($ids_content as $id_content)
					$xml .= self::contents($id_content, $export_images, false, $export_users, $images);
			}
		}
		return $xml;
	}

	/*
	 * Export weblinks
	 * @return 		xml string
	 * @since		1.5.3beta3.38
	 */
	static function weblinks($ids, $export_images, $export_categories, $export_users, &$images)
	{	
		self::initialize();	
		$xml = '';		

		$admin = $user->id;
		jimport('eshiol.j2xml.table.weblink');
				
		$categories = array();

		$user = JTable::getInstance('user', 'eshTable');
		$users = array();
		
		foreach($ids as $id)
		{
			$item = JTable::getInstance('weblink', 'eshTable');
			$item->load($id);

			if ($export_users)
			{
				if (($item->created_by != $admin) 
					&& (!array_key_exists($item->created_by, $users)))
				{
					$user->load($item->created_by);
					$users[$item->created_by] = $user->toXML();
				}
				if (($item->modified_by != $admin) 
					&& ($item->modified_by != 0) 
					&& (!array_key_exists($item->modified_by, $users)))
				{
					$user->load($item->modified_by);
					$users[$item->modified_by] = $user->toXML();
				}
			}	
			
			if ($export_categories && ($item->catid > 0))
				self::_category($item->catid, $export_images, $images, $categories, $export_users);

			if ($export_images)
			{
				$text = $item->description;
				$image = preg_match_all(self::$image_match_string,$text,$matches,PREG_PATTERN_ORDER);
				if (count($matches[1]) > 0)
				{
					for ($i = 0; $i < count($matches[1]); $i++)
					{
						$image = $matches[1][$i];						
						$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($matches[1][$i]));
						if (!array_key_exists($image, $images) && JFile::exists($file_path))
							$images[$image] = "\t\t<img src=\"".htmlentities($image, ENT_QUOTES, "UTF-8")."\">"
								."\t\t\t".base64_encode(file_get_contents($file_path))
								."\t\t</img>\n";
					}
				}
			}
			$xml .= $item->toXML();
		}
		foreach($categories as $category)
			$xml .= $category;
		foreach($users as $user)
			$xml .= $user;
		return $xml;
	}
	
	static function initialize()
	{
		if (!self::$initialized)
		{
			$execute = (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge')) ? 'execute' : 'query';

			$db = JFactory::getDBO();
			$db->setQuery("
					CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
						`id` int(10) unsigned NOT NULL,
						`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
						`title` varchar(100) NOT NULL DEFAULT ''
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
					")->$execute();
			$db->setQuery("
					TRUNCATE TABLE
						`#__j2xml_usergroups`;
					")->$execute();
			$db->setQuery("
					INSERT INTO
						`#__j2xml_usergroups`
					SELECT
						`id`,`parent_id`,CONCAT('[\"',REPLACE(`title`,'\"','\\\"'),'\"]')
					FROM
						`#__usergroups`;
					")->$execute();
			do {
				$db->setQuery("
					UPDATE
						`#__j2xml_usergroups` j
					INNER JOIN
						`#__usergroups` g
					ON
						j.parent_id = g.id
					SET
						j.parent_id = g.parent_id,
						j.title = CONCAT('[\"',REPLACE(`g`.`title`,'\"','\\\"'), '\",', SUBSTR(`j`.`title`,2));
					")->$execute();
				$n = $db->setQuery("
					SELECT
						COUNT(*)
					FROM
						`#__j2xml_usergroups`
					WHERE
						`parent_id` > 0;
					")->loadResult();
			} while ($n > 0);
			self::$initialized = true;
		}		
	}

	/*
	 * Export tag
	 * @return 		xml string
	 * @since		14.8.240
	 */
	private static function _tag($id, $export_images, &$images, &$tags, $export_content = false)
	{
		$xml = '';
		if (!array_key_exists($id, $tags))
		{
			$item = JTable::getInstance('tag', 'eshTable');
			$item->load($id);
			/*
			JError::raiseError(500, print_r($item, true));
			$app = JFactory::getApplication();
			$app->redirect('index.php');
			*/
			if ($item->parent_id > 1)
				$xml .= self::_tag($item->parent_id, $export_images, $images, $tags);
			
			$xml .= $tags[$id] = $item->toXML();
			
			if ($export_images)
			{
				$text = html_entity_decode($item->description);
				$image = preg_match_all(self::$image_match_string,$text,$matches,PREG_PATTERN_ORDER);
				if (count($matches[1]) > 0)
				{
					for ($i = 0; $i < count($matches[1]); $i++)
					{
						$image = $matches[1][$i];						
						$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($matches[1][$i]));
						if (!array_key_exists($image, $images) && JFile::exists($file_path))
							$images[$image] = "\t\t<img src=\"".$image."\">"
								."\t\t\t".base64_encode(file_get_contents($file_path))
								."\t\t</img>\n";
					}
				}
				
				$imgs = json_decode($item->params);				
				if ($imgs)
				{
					$image = $imgs->image_fulltext;
					$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($image));
					if (!array_key_exists($image, $images) && JFile::exists($file_path))
						$images[$image] = "\t\t<img src=\"".htmlentities($image, ENT_QUOTES, "UTF-8")."\">"
						."\t\t\t".base64_encode(file_get_contents($file_path))
						."\t\t</img>\n";
						
					$image = $imgs->image_intro;
					$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($image));
					if (!array_key_exists($image, $images) && JFile::exists($file_path))
						$images[$image] = "\t\t<img src=\"".htmlentities($image, ENT_QUOTES, "UTF-8")."\">"
						."\t\t\t".base64_encode(file_get_contents($file_path))
						."\t\t</img>\n";
				}
			}
			/*
			if ($export_content)
			{
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select('id')
					->from('#__content')
					->where('catid = '.$id);
				$db->setQuery($query);
				$ids_content = $db->loadColumn();
				foreach ($ids_content as $id_content)
					$xml .= self::contents($id_content, $export_images, false, $export_users, $images);
			}
			*/
		}
		return $xml;
	}
}
<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       18.8.310
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

\JLoader::import('joomla.filesystem.file');
\JLoader::import('joomla.filesystem.folder');

/**
 *
 * Image Table
 *
 */
class Image
{

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param \JRegistry $params
	 *			@option int 'images' 1: Yes, if not exists; 2: Yes, overwrite
	 *			if exists
	 *			@option string 'context'
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function import ($xml, &$params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$import_images = $params->get('images', 0);
		if ($import_images == 0)
		{
			return;
		}

		foreach ($xml->img as $image)
		{
			$src = JPATH_SITE . '/' . urldecode(html_entity_decode($image['src'], ENT_QUOTES, 'UTF-8'));
			$data = $image;
			if (!\JFile::exists($src) || ($import_images == 2))
			{
				// many thx to Stefanos Tzigiannis
				$folder = dirname($src);
				if (!\JFolder::exists($folder))
				{
					if (\JFolder::create($folder))
					{
						\JLog::add(
								new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED', $folder), \JLog::INFO, 'lib_j2xml'));
					}
					else
					{
						\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_ERROR_CREATING_FOLDER', $folder), \JLog::ERROR, 'lib_j2xml'));
						break;
					}
				}
				if (\JFile::write($src, base64_decode($data)))
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_IMAGE_IMPORTED', $image['src']), \JLog::INFO, 'lib_j2xml'));
				}
				else
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_IMAGE_NOT_IMPORTED', $image['src'], \JText::_('LIB_J2XML_MSG_UNKNOWN_ERROR')), \JLog::ERROR, 'lib_j2xml'));
				}
			}
		}
	}

	/**
	 * Export data
	 *
	 * @param string $_image
	 *			the image to be exported
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param array $options
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function export ($image, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));
		\JLog::add(new \JLogEntry($image, \JLog::DEBUG, 'com_j2xml'));

		// Joomla 4
		$image = strtok($image, '#');

		if ($xml->xpath("//j2xml/img[@src = '" . htmlentities($image, ENT_QUOTES, "UTF-8") . "']"))
		{
			return;
		}

		$file_path = JPATH_SITE . '/' . urldecode($image);
		if (\JFile::exists($file_path))
		{
			$img = $xml->addChild('img', base64_encode(file_get_contents($file_path)));
			$img->addAttribute('src', htmlentities($image, ENT_QUOTES, "UTF-8"));
		}
	}
}

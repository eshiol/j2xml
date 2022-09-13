<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
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

// no direct access
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;

$version = new JVersion();

$ui = $version->isCompatible('4') ? 'uitab' : 'bootstrap';

// MooTools is loaded for B/C for extensions generating JavaScript in their install scripts, this call will be removed at 4.0
JHtml::_('jquery.framework', true);
JHtml::_('bootstrap.tooltip');

if (!$version->isCompatible('4'))
{
	JHtml::_('behavior.framework');
}

JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton4 = function() {
		var form = document.getElementById("adminForm");

		// do field validation
		if (form.install_url.value == "" || form.install_url.value == "http://" || form.install_url.value == "https://") {
			alert("' . JText::_('COM_J2XML_MSG_INSTALL_ENTER_A_URL', true) . '");
		}
		else
		{
			JoomlaInstaller.showLoading();

			form.installtype.value = "url";
			form.submit();
		}
	};

	// Add spindle-wheel for installations:
	jQuery(document).ready(function($) {
		var outerDiv = $("#j2xml-import");

		JoomlaInstaller.getLoadingOverlay()
			.css("top", outerDiv.position().top - $(window).scrollTop())
			.css("left", "0")
			.css("width", "100%")
			.css("height", "100%")
			.css("display", "none")
			.css("margin-top", "-10px");
	});

	var JoomlaInstaller = {
		getLoadingOverlay: function () {
			return jQuery("#loading");
		},
		showLoading: function () {
			this.getLoadingOverlay().css("display", "block");
		},
		hideLoading: function () {
			this.getLoadingOverlay().css("display", "none");
		}
	};');

JFactory::getDocument()->addStyleDeclaration('
	#loading {
		background: rgba(255, 255, 255, .8) url(\'' . JHtml::_('image', 'jui/ajax-loader.gif', '', null, true, true) . '\') 50% 15% no-repeat;
		position: fixed;
		opacity: 0.8;
		-ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity = 80);
		filter: alpha(opacity = 80);
		overflow: hidden;
	}');
?>

<?php JFactory::getApplication()->getLanguage()->load('com_j2xml.sys'); ?>

<div id="j2xml-import" class="clearfix">
	<form enctype="multipart/form-data" action="<?php echo JRoute::_('index.php?option=com_j2xml'); ?>"
		method="post" name="adminForm" id="adminForm" class="form-horizontal">
		<?php if (!empty($this->sidebar)) : ?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
		</div>
		<div id="j-main-container" class="span10">
			<?php else : ?>
			<div id="j-main-container">
				<?php endif; ?>
				<!-- Render messages set by extension install scripts here -->
				<?php if ($this->showMessage) : ?>
					<?php echo $this->loadTemplate('message'); ?>
				<?php endif; ?>
				<?php echo JHtml::_($ui . '.startTabSet', 'myTab', array('active' => 'package')); ?>
				<?php // Show installation tabs at the start ?>
				<?php // $firstTab =  JFactory::getApplication()->triggerEvent('onInstallerViewBeforeFirstTab', array()); ?>
				<?php // Show installation tabs ?>
				<?php // $tabs =  JFactory::getApplication()->triggerEvent('onInstallerAddInstallationTab', array()); ?>

				<?php
				$tabs = array();
				$tab            = array();
				$tab['name']    = 'package';
				$tab['label']   = JText::_('COM_J2XML_PACKAGEIMPORTER_UPLOAD_DATA_FILE');

				// Render the input
				ob_start();
				include __DIR__ . '/default_package.php';
				$tab['content'] = ob_get_clean();

				$tabs[] = $tab;
				?>

				<?php foreach ($tabs as $tab) : ?>
					<?php echo JHtml::_($ui . '.addTab', 'myTab', $tab['name'], $tab['label']); ?>
					<fieldset class="uploadform">
						<?php echo $tab['content']; ?>
					</fieldset>
					<?php echo JHtml::_($ui . '.endTab'); ?>
				<?php endforeach; ?>
				<?php // Show installation tabs at the end ?>
				<?php // $lastTab =  JFactory::getApplication()->triggerEvent('onInstallerViewAfterLastTab', array()); ?>
				<?php // $tabs = array_merge($firstTab, $tabs, $lastTab); ?>
				<?php if (!$tabs) : ?>
					<?php JFactory::getApplication()->enqueueMessage(JText::_('COM_J2XML_NO_INSTALLATION_PLUGINS_FOUND'), 'warning'); ?>
				<?php endif; ?>

				<?php if ($this->ftp) : ?>
					<?php echo JHtml::_($ui . '.addTab', 'myTab', 'ftp', JText::_('COM_J2XML_MSG_DESCFTPTITLE')); ?>
					<?php echo $this->loadTemplate('ftp'); ?>
					<?php echo JHtml::_($ui . '.endTab'); ?>
				<?php endif; ?>

				<input type="hidden" name="installtype" value=""/>
				<input type="hidden" name="task" value="import.import"/>
				<?php echo JHtml::_('form.token'); ?>

				<?php echo JHtml::_($ui . '.endTabSet'); ?>
			</div>
			<button class="hidden" id="j2xmlImportCloseBtn" type="button" onclick="this.form.install_package.val('');"></button>
			<button class="hidden" id="j2xmlImportBtn" type="button" onclick="console.log('install_package');this.form.install_package.val('');"></button>
	</form>
</div>
<div id="loading"></div>

<?php
JText::script('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED');

$doc = Factory::getDocument();
$cparams = JComponentHelper::getParams('com_j2xml');
$min = $cparams->get('debug', 0) ? '' : '.min';

JLog::add(new JLogEntry("loading ../media/lib_eshiol_j2xml/js/pako_inflate{$min}.js", JLog::DEBUG, 'com_j2xml'));
$doc->addScript("../media/lib_eshiol_j2xml/js/pako_inflate{$min}.js", array('version'=>'auto'));

JLog::add(new JLogEntry("loading ../media/lib_eshiol_j2xml/js/version_compare{$min}.js", JLog::DEBUG, 'com_j2xml'));
$doc->addScript("../media/lib_eshiol_j2xml/js/version_compare{$min}.js", array('version'=>'auto'));

JLog::add(new JLogEntry("loading ../media/lib_eshiol_j2xml/js/j2xml{$min}.js", JLog::DEBUG, 'com_j2xml'));
$doc->addScript("../media/lib_eshiol_j2xml/js/j2xml{$min}.js", array('version'=>'auto'));

JLog::add(new JLogEntry("loading ../media/lib_eshiol_j2xml/js/j2xml{$min}.js", JLog::DEBUG, 'com_j2xml'));
$doc->addScript("../media/lib_eshiol_j2xml/js/base64{$min}.js", array('version'=>'auto'));

JLog::add(new JLogEntry("loading ../media/com_j2xml/js/j2xml{$min}.js", JLog::DEBUG, 'com_j2xml'));
$doc->addScript("../media/com_j2xml/js/j2xml{$min}.js", array('version'=>'auto'));

// Trigger the onLoadJS event.
PluginHelper::importPlugin('j2xml');
Factory::getApplication()->triggerEvent('onLoadJS');

// Load the import options form
$selector = 'j2xmlImport';

if ($version->isCompatible('4'))
{
	echo HTMLHelper::_('bootstrap.renderModal', $selector . 'Modal',
		array(
			'title' => Text::_('COM_J2XML_IMPORT'),
			'url' => JRoute::_('index.php?'. http_build_query([
				'option' => 'com_j2xml',
				'view' => 'import',
				'layout' => 'options',
				'tmpl' => 'component',
				Session::getFormToken() => 1
				])),
			'height' => '420px',
			'width' => '300px',
			'modalWidth' => '50',
			'footer' =>
				'<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-hidden="true">'
				. Text::_('JTOOLBAR_CANCEL') . '</button>'
				. '<button type="button" class="btn btn-success" data-bs-dismiss="modal" aria-hidden="true"'
				. ' onclick="eshiol.j2xml.importerModal();">'
				. Text::_("COM_J2XML_IMPORT") . '</button>'
		)
	);
}
elseif ($version->isCompatible('3.4'))
{
	echo JHtml::_('bootstrap.renderModal', $selector . 'Modal',
		array(
			'title' => JText::_('COM_J2XML_IMPORT'),
			'url' => JRoute::_('index.php?option=com_j2xml&amp;view=import&amp;layout=options&amp;tmpl=component'),
			'height' => '370px',
			'width' => '300px',
			'modalWidth' => '50',
			'modalHeight' => '50',
			'footer' => '<a class="btn" data-dismiss="modal" type="button"'
				. ' onclick="jQuery(\'#' . $selector .'Modal iframe\').contents().find(\'#' . $selector . 'CancelBtn\').click();">' . JText::_("JTOOLBAR_CANCEL") . '</a>'
				. '<button class="btn btn-success" type="button"'
				. ' onclick="eshiol.j2xml.importerModal();jQuery(\'#' . $selector . 'Modal iframe\').contents().find(\'#' . $selector . 'OkBtn\').click();">'
				. JText::_("COM_J2XML_IMPORT") . '</button>'));
}
else
{
	echo JHtml::_('bootstrap.renderModal', $selector . 'Modal',
		array(
			'title' => JText::_('COM_J2XML_IMPORT'),
			'url' => JRoute::_('index.php?option=com_j2xml&amp;view=import&amp;layout=options&amp;tmpl=component'),
			'height' => '370px',
			'width' => '300px',
			'modalWidth' => '40'),
			addslashes('<div class="container-fluid"><div class="row-fluid"><div class="span12"><div class="btn-toolbar">'
			. '<a class="btn btn-wrapper pull-right" data-dismiss="modal" type="button"'
			. ' onclick="jQuery(\'#' . $selector .'Modal iframe\').contents().find(\'#' . $selector . 'CancelBtn\').click();">' . JText::_("JTOOLBAR_CANCEL") . '</a>'
			. '<button class="btn btn-success btn-wrapper pull-right" type="button"'
			. ' onclick="eshiol.j2xml.importerModal();jQuery(\'#' . $selector . 'Modal iframe\').contents().find(\'#' . $selector . 'OkBtn\').click();">'
			. JText::_("COM_J2XML_IMPORT") . '</button>'
			. '</div></div></div></div>'));
}

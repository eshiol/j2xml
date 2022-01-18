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

use Joomla\CMS\Language\Text;

$version = new JVersion();

$ui = $version->isCompatible('4') ? 'uitab' : 'bootstrap';

if ($version->isCompatible('3.4'))
{
	JHtml::_('behavior.formvalidator');
}
else
{
	JHtml::_('behavior.formvalidation');
}

JHtml::_('behavior.keepalive');
JHtml::_('jquery.framework', true);

if ($version->isCompatible( '4' ))
{
	JFactory::getDocument()->getWebAssetManager()
		->useScript( 'webcomponent.toolbar-button' );
	$this->document->addScriptOptions('progressBarContainerClass', 'progress');
	$this->document->addScriptOptions('progressBarClass', 'progress-bar progress-bar-striped progress-bar-animated bg-success');
}
else
{
	JHtml::_('behavior.framework');
	JHtml::_($ui . '.tooltip', '.hasTooltip', array(
		'placement' => 'bottom'
	));
	JHtml::_('formbehavior.chosen', 'select');

	JHtml::_('behavior.tabstate');
	JFactory::getDocument()->addScriptDeclaration(<<<EOL
		// Select first tab
		jQuery(document).ready(function() {
			jQuery( '#j2xmlUsersTabs a:first' ).tab( 'show' );

			// url validator
			document.formvalidator.setHandler( 'url', function( value, element ) {
				var regex = /^(https?|ftp|rmtp|mms):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)*)(:(\d+))?\/?/i;
				return regex.test( value );
			});
		});
EOL
	);
}

$params = JComponentHelper::getParams('com_j2xml');
$min = ($params->get('debug', 0) ? '' : '.min');
$doc = JFactory::getDocument();
$doc->addScript("../media/lib_eshiol_phpxmlrpc/js/jquery.xmlrpc{$min}.js");
$doc->addScript("../media/lib_eshiol_j2xml/js/j2xml{$min}.js");

JText::script('COM_J2XML_SEND_ERROR');
JText::script('COM_J2XML_SEND_ERROR_REMOTEURL_IS_REQUIRED');
JText::script('LIB_J2XML_SENDING');
JText::script('LIB_J2XML_MSG_XMLRPC_DISABLED');
?>

<form action="<?php echo JRoute::_('index.php?option=com_j2xml'); ?>"
	id="adminForm" method="post" name="adminForm"
	class="form-horizontal form-validate">

	<?php $fieldsets = $this->form->getFieldsets(); ?>

	<?php echo JHtml::_($ui . '.startTabSet', 'j2xmlUsers', array('active' => 'export')); ?>

	<?php foreach ($fieldsets as $name => $fieldSet) : ?>
		<?php if ($name == 'details') continue; ?>

		<?php $label = empty($fieldSet->label) ? 'COM_J2XML_' . $name . '_FIELDSET_LABEL' : $fieldSet->label; ?>
		<?php echo JHtml::_($ui . '.addTab', 'j2xmlUsers', $name, Text::_($label)); ?>

		<?php foreach ($this->form->getFieldset($name) as $field) : ?>
			<?php
				$dataShowOn = '';
				$groupClass = $field->type === 'Spacer' ? ' field-spacer' : '';
			?>
			<?php if ($field->showon) : ?>
				<?php JHtml::_('jquery.framework'); ?>
				<?php JHtml::_('script', 'jui/cms.js', array('version' => 'auto', 'relative' => true)); ?>
				<?php $dataShowOn = ' data-showon=\'' . json_encode(JFormHelper::parseShowOnConditions($field->showon, $field->formControl, $field->group)) . '\''; ?>
			<?php endif; ?>
			<?php if ($field->hidden) : ?>
				<?php echo $field->input; ?>
			<?php else : ?>
				<div class="control-group<?php echo $groupClass; ?>"<?php echo $dataShowOn; ?>>
					<?php if ($name != 'permissions') : ?>
						<div class="control-label">
							<?php echo $field->label; ?>
						</div>
					<?php endif; ?>
					<div class="<?php if ($name != 'permissions') : ?>controls<?php endif; ?>">
						<?php echo $field->input; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php echo JHtml::_($ui . '.endTab'); ?>
	<?php endforeach; ?>
	<?php echo JHtml::_($ui . '.endTabSet'); ?>

	<button class="hidden" id="j2xmlSendOkBtn" type="button"
		onclick="
			eshiol.removeMessages(window.parent.jQuery('#system-message-container'));

			var f = document.adminForm;
			if (document.formvalidator.isValid(f)) {
				window.top.setTimeout('window.parent.jQuery(\'#j2xmlSendModal\').modal(\'hide\')', 700);

				eshiol.j2xml.send({
					message_container: window.parent.jQuery('#system-message-container'),
					export_url: 'index.php?option=com_j2xml&task=users.export&format=json&<?php echo JSession::getFormToken(); ?>=1',
					remote_url: jQuery('#jform_remote_url').val().replace(/\/?$/, '/') + 'index.php?option=com_j2xml&task=services.import&format=xmlrpc',
					compression: jQuery('#jform_compression').val(),
					password: jQuery('input:radio[name=\'jform\[password\]\']:checked').first().val(),
					fields: jQuery('input:radio[name=\'jform\[fields\]\']:checked').first().val(),
				});
			}
			else {
				var msg = new Array();
				msg.push(Joomla.JText._('COM_J2XML_SEND_ERROR'));
				if (jQuery('#jform_remote_url').hasClass('invalid')) {
					msg.push(Joomla.JText._('COM_J2XML_SEND_ERROR_REMOTEURL_IS_REQUIRED'));
				}
//				alert (msg.join('\n'));
				return false;
			}
"></button>
</form>
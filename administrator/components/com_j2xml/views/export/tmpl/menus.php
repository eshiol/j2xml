<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
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

// no direct access
defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;

$version = new JVersion();

if ($version->isCompatible('3.4'))
{
	JHtml::_('behavior.formvalidator');
}
else
{
	JHtml::_('behavior.formvalidation');
}

if ($version->isCompatible('4'))
{
	$ui = 'uitab';
}
else
{
	$ui = 'bootstrap';

	JHtml::_($ui . '.tooltip', '.hasTooltip', array(
		'placement' => 'bottom'
	));
	JHtml::_('formbehavior.chosen', 'select');

	JHtml::_('behavior.tabstate');
	JFactory::getDocument()->addScriptDeclaration(<<<EOL
		// Select first tab
		jQuery(document).ready(function() {
			jQuery( '#j2xmlMenusTabs a:first' ).tab( 'show' );
		});
EOL
	);
}
?>

<form
	action="<?php echo JRoute::_('index.php?option=com_j2xml&task=menus.display&format=raw'); ?>"
	id="adminForm" method="post" name="adminForm" autocomplete="off"
	class="form-horizontal">

	<?php $fieldsets = $this->form->getFieldsets(); ?>

	<?php echo JHtml::_($ui . '.startTabSet', 'j2xmlMenus', array('active' => 'export')); ?>

	<?php foreach ($fieldsets as $name => $fieldSet) : ?>
		<?php if ($name == 'details') continue; ?>

		<?php $label = empty($fieldSet->label) ? 'COM_J2XML_' . $name . '_FIELDSET_LABEL' : $fieldSet->label; ?>
		<?php echo JHtml::_($ui . '.addTab', 'j2xmlMenus', $name, Text::_($label)); ?>

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

	<button class="hidden" id="j2xmlExportOkBtn" type="button"
		onclick="this.form.submit();window.top.setTimeout('window.parent.jQuery(\'#j2xmlExportModal\').modal(\'hide\')', 700);">

		<?php /** if ($version->isCompatible('4')) : ?>
			onclick="this.form.submit();window.top.setTimeout('window.parent.Joomla.Modal.getCurrent().close();', 700);"> ?>
		<?php else : ?>
			onclick="this.form.submit();window.top.setTimeout('window.parent.jQuery(\'#j2xmlExportModal\').modal(\'hide\')', 700);">
		<?php endif; **/ ?>
	</button>
</form>

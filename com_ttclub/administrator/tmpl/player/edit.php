<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\Player\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

// Load associated club ID labels for display
$clubLabels = '';
if (!empty($this->item->id)) {
    $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
    $query = $db->getQuery(true)
        ->select($db->quoteName('ci.label'))
        ->from($db->quoteName('#__ttclub_player_club_ids', 'pci'))
        ->join('INNER', $db->quoteName('#__ttclub_club_ids', 'ci') . ' ON ' . $db->quoteName('ci.id') . ' = ' . $db->quoteName('pci.club_id'))
        ->where($db->quoteName('pci.player_id') . ' = ' . (int) $this->item->id)
        ->order($db->quoteName('ci.ordering') . ' ASC');
    $db->setQuery($query);
    $labels = $db->loadColumn();
    $clubLabels = implode(', ', $labels ?: []);
}
?>

<form action="<?php echo Route::_('index.php?option=com_ttclub&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <?php echo $this->form->renderField('first_name'); ?>
                    <?php echo $this->form->renderField('last_name'); ?>
                </div>
            </div>
            <?php if ($clubLabels !== '') : ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo Text::_('Club IDs'); ?></h3>
                    </div>
                    <div class="card-body">
                        <p><?php echo $this->escape($clubLabels); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <?php echo $this->form->renderField('published'); ?>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

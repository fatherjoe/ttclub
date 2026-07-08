<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\Clubid\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
?>

<form action="<?php echo Route::_('index.php?option=com_ttclub&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

    <div class="row">
        <div class="col-lg-9">
            <fieldset class="adminform">
                <?php echo $this->form->renderField('label'); ?>
                <?php echo $this->form->renderField('click_tt_club_id'); ?>
                <?php echo $this->form->renderField('legacy_club_id'); ?>
                <?php echo $this->form->renderField('club_name'); ?>
                <?php echo $this->form->renderField('federation'); ?>
            </fieldset>
        </div>
        <div class="col-lg-3">
            <fieldset class="form-vertical">
                <?php echo $this->form->renderField('ordering'); ?>
            </fieldset>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

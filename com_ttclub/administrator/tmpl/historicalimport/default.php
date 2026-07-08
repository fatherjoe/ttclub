<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\HistoricalImport\HtmlView $this */

$clubUrl = $this->params->get('mytischtennis_club_url', '');
?>

<form action="<?php echo Route::_('index.php?option=com_ttclub&task=historicalimport.import'); ?>" method="post" name="adminForm" id="adminForm">

    <?php // Introduction and warning ?>
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title"><?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_TITLE'); ?></h3>
        </div>
        <div class="card-body">
            <p><?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_DESCRIPTION'); ?></p>

            <?php if ($clubUrl === ''): ?>
                <div class="alert alert-danger" role="alert">
                    <span class="icon-warning" aria-hidden="true"></span>
                    <?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_NO_CLUB_URL'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php // Existing data warning ?>
    <?php if ($this->showConfirmation): ?>
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">
                <span class="icon-warning" aria-hidden="true"></span>
                <?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_EXISTING_DATA_WARNING_TITLE'); ?>
            </h4>
            <p><?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_EXISTING_DATA_WARNING'); ?></p>
        </div>
    <?php endif; ?>

    <?php // Data Source Selection ?>
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title"><?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_DATA_SOURCE_TITLE'); ?></h3>
        </div>
        <div class="card-body">
            <fieldset>
                <legend class="visually-hidden"><?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_DATA_SOURCE_TITLE'); ?></legend>
                <div class="form-check mb-2">
                    <input
                        class="form-check-input"
                        type="radio"
                        name="data_source"
                        id="data_source_mytischtennis"
                        value="mytischtennis"
                        <?php echo ($this->selectedDataSource === 'mytischtennis' || $this->selectedDataSource === '') ? 'checked' : ''; ?>
                        required
                    >
                    <label class="form-check-label" for="data_source_mytischtennis">
                        mytischtennis.de
                    </label>
                    <small class="form-text text-muted d-block">
                        <?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_SOURCE_MYTISCHTENNIS_DESC'); ?>
                    </small>
                </div>
                <div class="form-check mb-2">
                    <input
                        class="form-check-input"
                        type="radio"
                        name="data_source"
                        id="data_source_clicktt"
                        value="clicktt"
                        <?php echo $this->selectedDataSource === 'clicktt' ? 'checked' : ''; ?>
                    >
                    <label class="form-check-label" for="data_source_clicktt">
                        click-tt.de
                    </label>
                    <small class="form-text text-muted d-block">
                        <?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_SOURCE_CLICKTT_DESC'); ?>
                    </small>
                </div>
            </fieldset>
        </div>
    </div>

    <?php // Confirmation and Trigger ?>
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title"><?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_EXECUTE_TITLE'); ?></h3>
        </div>
        <div class="card-body">
            <?php if ($this->showConfirmation): ?>
                <div class="form-check mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="confirmed"
                        id="confirmed"
                        value="1"
                        required
                    >
                    <label class="form-check-label" for="confirmed">
                        <?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_CONFIRM_CHECKBOX'); ?>
                    </label>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-lg" id="btn-trigger-historical-import" <?php echo $clubUrl === '' ? 'disabled' : ''; ?>>
                <span class="icon-download" aria-hidden="true"></span>
                <?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_START'); ?>
            </button>

            <p class="form-text text-muted mt-2">
                <?php echo Text::_('COM_TTCLUB_HISTORICAL_IMPORT_DURATION_NOTE'); ?>
            </p>
        </div>
    </div>

    <?php // Summary Display Area (shown after completion via Joomla messages) ?>
    <div id="historical-import-summary"></div>

    <input type="hidden" name="task" value="historicalimport.import">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

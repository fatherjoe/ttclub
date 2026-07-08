<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\Roster\HtmlView $this */
?>

<h2><?php echo Text::_('COM_TTCLUB_ROSTER_TITLE'); ?></h2>

<?php // Team and half-season selection form ?>
<form action="<?php echo Route::_('index.php?option=com_ttclub&view=roster'); ?>" method="get" name="rosterFilterForm" id="rosterFilterForm" class="mb-4">
    <input type="hidden" name="option" value="com_ttclub">
    <input type="hidden" name="view" value="roster">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="team_id" class="form-label"><?php echo Text::_('COM_TTCLUB_FIELD_TEAM_LABEL'); ?></label>
            <?php echo $this->form->getInput('team_id'); ?>
        </div>
        <div class="col-md-4">
            <label for="half_season_id" class="form-label"><?php echo Text::_('COM_TTCLUB_FIELD_HALF_SEASON_LABEL'); ?></label>
            <?php echo $this->form->getInput('half_season_id'); ?>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary">
                <?php echo Text::_('COM_TTCLUB_ROSTER_LOAD'); ?>
            </button>
        </div>
    </div>
</form>

<?php if ($this->teamId > 0 && $this->halfSeasonId > 0) : ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <?php echo $this->escape($this->teamName); ?> &mdash; <?php echo $this->escape($this->halfSeasonName); ?>
            </h3>
        </div>
        <div class="card-body">
            <?php // Assigned players table ?>
            <?php if (empty($this->rosterEntries)) : ?>
                <div class="alert alert-info">
                    <span class="icon-info-circle" aria-hidden="true"></span>
                    <?php echo Text::_('COM_TTCLUB_ROSTER_NO_PLAYERS'); ?>
                </div>
            <?php else : ?>
                <table class="table table-striped" id="rosterList">
                    <caption class="visually-hidden">
                        <?php echo Text::_('COM_TTCLUB_ROSTER_TABLE_CAPTION'); ?>
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col" class="w-5 text-center">#</th>
                            <th scope="col"><?php echo Text::_('COM_TTCLUB_FIELD_LAST_NAME_LABEL'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_TTCLUB_FIELD_FIRST_NAME_LABEL'); ?></th>
                            <th scope="col" class="w-15 text-center"><?php echo Text::_('COM_TTCLUB_ROSTER_ASSIGNED_DATE'); ?></th>
                            <th scope="col" class="w-10 text-center"><?php echo Text::_('JACTION_DELETE'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->rosterEntries as $i => $entry) : ?>
                            <?php $isUnpublished = empty($entry->published); ?>
                            <tr class="row<?php echo $i % 2; ?><?php echo $isUnpublished ? ' text-muted' : ''; ?>"<?php echo $isUnpublished ? ' style="opacity: 0.5;"' : ''; ?>>
                                <td class="text-center"><?php echo $entry->position ? (int) $entry->position : '—'; ?></td>
                                <td><?php echo $this->escape($entry->last_name); ?><?php echo $isUnpublished ? ' <span class="badge bg-secondary">unpublished</span>' : ''; ?></td>
                                <td><?php echo $this->escape($entry->first_name); ?></td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('date', $entry->created, Text::_('DATE_FORMAT_LC4')); ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo Route::_(
                                        'index.php?option=com_ttclub&task=roster.remove'
                                        . '&roster_id=' . (int) $entry->roster_id
                                        . '&team_id=' . $this->teamId
                                        . '&half_season_id=' . $this->halfSeasonId
                                        . '&' . Session::getFormToken() . '=1'
                                    ); ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('<?php echo Text::_('COM_TTCLUB_ROSTER_CONFIRM_REMOVE'); ?>');"
                                       aria-label="<?php echo Text::sprintf('COM_TTCLUB_ROSTER_REMOVE_PLAYER_ARIA', $this->escape($entry->first_name . ' ' . $entry->last_name)); ?>"
                                    >
                                        <span class="icon-times" aria-hidden="true"></span>
                                        <?php echo Text::_('COM_TTCLUB_ROSTER_REMOVE'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php // Add player form ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0"><?php echo Text::_('COM_TTCLUB_ROSTER_ADD_PLAYER'); ?></h3>
        </div>
        <div class="card-body">
            <form action="<?php echo Route::_('index.php?option=com_ttclub&task=roster.assign'); ?>" method="post" name="assignForm" id="assignForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="player_id" class="form-label"><?php echo Text::_('COM_TTCLUB_FIELD_PLAYER_LABEL'); ?></label>
                        <?php echo $this->form->getInput('player_id'); ?>
                    </div>
                    <div class="col-md-2">
                        <label for="position" class="form-label">Position #</label>
                        <input type="number" name="position" id="position" class="form-control" min="1" placeholder="#">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success">
                            <span class="icon-plus" aria-hidden="true"></span>
                            <?php echo Text::_('COM_TTCLUB_ROSTER_ASSIGN'); ?>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="team_id" value="<?php echo $this->teamId; ?>">
                <input type="hidden" name="half_season_id" value="<?php echo $this->halfSeasonId; ?>">
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>

    <?php // Copy roster form ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0"><?php echo Text::_('COM_TTCLUB_ROSTER_COPY_TITLE'); ?></h3>
        </div>
        <div class="card-body">
            <?php if ($this->targetHasAssignments) : ?>
                <div class="alert alert-warning">
                    <span class="icon-exclamation-triangle" aria-hidden="true"></span>
                    <?php echo Text::_('COM_TTCLUB_ROSTER_COPY_TARGET_HAS_ASSIGNMENTS'); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo Route::_('index.php?option=com_ttclub&task=roster.copy'); ?>" method="post" name="copyForm" id="copyForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="copy_mode" class="form-label"><?php echo Text::_('COM_TTCLUB_ROSTER_COPY_MODE_LABEL'); ?></label>
                        <select name="copy_mode" id="copy_mode" class="form-select">
                            <option value="merge"><?php echo Text::_('COM_TTCLUB_ROSTER_COPY_MODE_MERGE'); ?></option>
                            <option value="replace"><?php echo Text::_('COM_TTCLUB_ROSTER_COPY_MODE_REPLACE'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('<?php echo Text::_('COM_TTCLUB_ROSTER_COPY_CONFIRM'); ?>');">
                            <span class="icon-copy" aria-hidden="true"></span>
                            <?php echo Text::_('COM_TTCLUB_ROSTER_COPY_BUTTON'); ?>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="team_id" value="<?php echo $this->teamId; ?>">
                <input type="hidden" name="half_season_id" value="<?php echo $this->halfSeasonId; ?>">
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
<?php endif; ?>

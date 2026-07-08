<?php

declare(strict_types=1);

use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Site\View\Schedule\HtmlView $this */

$app = Factory::getApplication();
$teamId = $this->teamId;
$seasonId = $this->seasonId;
$upcomingMatches = $this->upcomingMatches;
$pastMatches = $this->pastMatches;
$seasons = $this->seasons;
$team = $this->team;
?>

<div class="com-ttclub-schedule">
    <?php if ($team): ?>
        <h2><?php echo $this->escape(Text::sprintf('COM_TTCLUB_SCHEDULE_HEADING', (string) $team->team_number)); ?></h2>
    <?php else: ?>
        <h2><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_TITLE')); ?></h2>
    <?php endif; ?>

    <?php // Season selector form ?>
    <?php if (!empty($seasons)): ?>
        <form method="get" class="com-ttclub-schedule__season-filter" aria-label="<?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_SEASON_FILTER')); ?>">
            <input type="hidden" name="option" value="com_ttclub">
            <input type="hidden" name="view" value="schedule">
            <input type="hidden" name="team_id" value="<?php echo (int) $teamId; ?>">
            <label for="season_id"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_SELECT_SEASON')); ?></label>
            <select name="season_id" id="season_id" onchange="this.form.submit();">
                <?php foreach ($seasons as $season): ?>
                    <option value="<?php echo (int) $season->id; ?>"<?php echo ((int) $season->id === $seasonId) ? ' selected' : ''; ?>>
                        <?php echo $this->escape(TtclubHelper::getSeasonDisplayName((int) $season->start_year, $season->label ?? '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript>
                <button type="submit"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_GO')); ?></button>
            </noscript>
        </form>
    <?php endif; ?>

    <?php if (empty($upcomingMatches) && empty($pastMatches)): ?>
        <?php // No schedule data available ?>
        <div class="alert alert-info">
            <p><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_NO_DATA')); ?></p>
        </div>
    <?php else: ?>
        <?php // Upcoming matches section ?>
        <?php if (!empty($upcomingMatches)): ?>
            <section class="com-ttclub-schedule__upcoming" aria-labelledby="schedule-upcoming-heading">
                <h3 id="schedule-upcoming-heading"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_UPCOMING')); ?></h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_DATE')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_TIME')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_OPPONENT')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_VENUE')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_HOME_AWAY')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingMatches as $match): ?>
                            <tr>
                                <td><?php echo $this->escape(HTMLHelper::_('date', $match->match_date, Text::_('DATE_FORMAT_LC4'))); ?></td>
                                <td><?php echo $match->match_time ? $this->escape(substr($match->match_time, 0, 5)) : '—'; ?></td>
                                <td><?php echo $this->escape($match->opponent); ?></td>
                                <td><?php echo $this->escape($match->venue); ?></td>
                                <td><?php echo $this->escape($match->home_away == 1 ? Text::_('COM_TTCLUB_SCHEDULE_HOME') : Text::_('COM_TTCLUB_SCHEDULE_AWAY')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <?php // Past matches section ?>
        <?php if (!empty($pastMatches)): ?>
            <section class="com-ttclub-schedule__past" aria-labelledby="schedule-past-heading">
                <h3 id="schedule-past-heading"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_PAST')); ?></h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_DATE')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_TIME')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_OPPONENT')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_VENUE')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_HOME_AWAY')); ?></th>
                            <th scope="col"><?php echo $this->escape(Text::_('COM_TTCLUB_SCHEDULE_RESULT')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pastMatches as $match): ?>
                            <tr>
                                <td><?php echo $this->escape(HTMLHelper::_('date', $match->match_date, Text::_('DATE_FORMAT_LC4'))); ?></td>
                                <td><?php echo $match->match_time ? $this->escape(substr($match->match_time, 0, 5)) : '—'; ?></td>
                                <td><?php echo $this->escape($match->opponent); ?></td>
                                <td><?php echo $this->escape($match->venue); ?></td>
                                <td><?php echo $this->escape($match->home_away == 1 ? Text::_('COM_TTCLUB_SCHEDULE_HOME') : Text::_('COM_TTCLUB_SCHEDULE_AWAY')); ?></td>
                                <td><?php echo $match->result ? $this->escape($match->result) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

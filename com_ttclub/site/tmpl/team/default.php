<?php

declare(strict_types=1);

/**
 * @var \Fatherjoe\Component\Ttclub\Site\View\Team\HtmlView $this
 */

use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$placeholderImage = 'media/com_ttclub/images/placeholder.png';

if ($this->item === null) : ?>
    <div class="com-ttclub-team alert alert-warning">
        <p><?php echo Text::_('COM_TTCLUB_TEAM_NOT_FOUND'); ?></p>
    </div>
    <?php return;
endif;

$teamPhotoSrc = !empty($this->teamPhoto)
    ? Uri::root() . $this->teamPhoto
    : Uri::root() . $placeholderImage;
?>
<div class="com-ttclub-team">
    <h2><?php echo $this->escape(Text::sprintf('COM_TTCLUB_TEAM_NAME', (string) $this->item->team_number)); ?></h2>

    <?php // Half-season switching ?>
    <?php if (!empty($this->halfSeasons) && count($this->halfSeasons) > 1) : ?>
        <div class="com-ttclub-team__half-season-switch" role="navigation" aria-label="<?php echo Text::_('COM_TTCLUB_HALF_SEASON_NAVIGATION'); ?>">
            <?php foreach ($this->halfSeasons as $hs) : ?>
                <?php
                $isActive = $this->halfSeason !== null && (int) $this->halfSeason->id === (int) $hs->id;
                $label = (int) $hs->half === 1 ? Text::_('COM_TTCLUB_FIRST_HALF') : Text::_('COM_TTCLUB_SECOND_HALF');
                ?>
                <a href="<?php echo Route::_('index.php?option=com_ttclub&view=team&id=' . (int) $this->item->id . '&half_season_id=' . (int) $hs->id); ?>"
                   class="com-ttclub-team__half-season-link<?php echo $isActive ? ' active' : ''; ?>"
                   <?php echo $isActive ? 'aria-current="true"' : ''; ?>>
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php // Team photo ?>
    <div class="com-ttclub-team__photo">
        <img src="<?php echo $this->escape($teamPhotoSrc); ?>"
             alt="<?php echo $this->escape(Text::sprintf('COM_TTCLUB_TEAM_PHOTO_ALT', (string) $this->item->team_number)); ?>"
             class="com-ttclub-team__photo-image"
             loading="lazy">
    </div>

    <?php // Team info ?>
    <div class="com-ttclub-team__info">
        <dl class="com-ttclub-team__details">
            <dt><?php echo Text::_('COM_TTCLUB_LEAGUE'); ?></dt>
            <dd><?php echo $this->escape($this->item->league_name ?? ''); ?></dd>

            <dt><?php echo Text::_('COM_TTCLUB_AGE_CLASS'); ?></dt>
            <dd><?php echo $this->escape($this->item->age_class_name ?? ''); ?></dd>

            <dt><?php echo Text::_('COM_TTCLUB_SEASON'); ?></dt>
            <dd><?php echo $this->escape(TtclubHelper::getSeasonDisplayName((int) ($this->item->season_start_year ?? 0), $this->item->season_label ?? '')); ?></dd>
        </dl>
    </div>

    <?php // Roster section ?>
    <div class="com-ttclub-team__roster">
        <h3><?php echo Text::_('COM_TTCLUB_ROSTER'); ?></h3>

        <?php if (empty($this->roster)) : ?>
            <p class="com-ttclub-team__no-roster alert alert-info">
                <?php echo Text::_('COM_TTCLUB_NO_ROSTER_AVAILABLE'); ?>
            </p>
        <?php else : ?>
            <div class="com-ttclub-team__roster-grid">
                <?php foreach ($this->roster as $player) : ?>
                    <?php
                    $playerImage = !empty($player->player_image)
                        ? Uri::root() . $player->player_image
                        : Uri::root() . $placeholderImage;
                    ?>
                    <div class="com-ttclub-team__roster-player">
                        <img src="<?php echo $this->escape($playerImage); ?>"
                             alt="<?php echo $this->escape($player->first_name . ' ' . $player->last_name); ?>"
                             class="com-ttclub-team__roster-player-image"
                             loading="lazy">
                        <span class="com-ttclub-team__roster-player-name">
                            <?php echo $this->escape($player->first_name . ' ' . $player->last_name); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php // Schedule table (Requirement 14 — live from click-tt.de via ScheduleService) ?>
    <div class="com-ttclub-team__schedule">
        <h3><?php echo Text::_('COM_TTCLUB_SCHEDULE'); ?></h3>

        <?php if ($this->schedule === null) : ?>
            <p class="com-ttclub-team__schedule-unavailable alert alert-info">
                <?php echo Text::_('COM_TTCLUB_SCHEDULE_TEMPORARILY_UNAVAILABLE'); ?>
            </p>
        <?php elseif (empty($this->schedule)) : ?>
            <p class="com-ttclub-team__no-schedule alert alert-info">
                <?php echo Text::_('COM_TTCLUB_NO_SCHEDULE_AVAILABLE'); ?>
            </p>
        <?php else : ?>
            <table class="com-ttclub-team__schedule-table table table-striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_SCHEDULE_DATE'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_SCHEDULE_TIME'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_SCHEDULE_HOME_TEAM'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_SCHEDULE_GUEST_TEAM'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_SCHEDULE_RESULT'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $today = (new \DateTime('now'))->format('Y-m-d');
                    foreach ($this->schedule as $match) :
                        $matchDate = $match['match_date'] ?? '';
                        $isPast = $matchDate < $today;
                        $result = ($isPast && !empty($match['result'])) ? $match['result'] : '';
                    ?>
                    <tr class="<?php echo $isPast ? 'com-ttclub-team__schedule-past' : 'com-ttclub-team__schedule-upcoming'; ?>">
                        <td><?php echo $this->escape($matchDate); ?></td>
                        <td><?php echo $this->escape($match['match_time'] ?? ''); ?></td>
                        <td><?php echo $this->escape($match['home_team'] ?? ''); ?></td>
                        <td><?php echo $this->escape($match['guest_team'] ?? ''); ?></td>
                        <td><?php echo $this->escape($result); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php // League ranking table (Requirement 14) ?>
    <div class="com-ttclub-team__ranking">
        <h3><?php echo Text::_('COM_TTCLUB_RANKING_TABLE'); ?></h3>

        <?php if ($this->ranking === null) : ?>
            <p class="com-ttclub-team__ranking-unavailable alert alert-info">
                <?php echo Text::_('COM_TTCLUB_RANKING_TEMPORARILY_UNAVAILABLE'); ?>
            </p>
        <?php else : ?>
            <table class="com-ttclub-team__ranking-table table table-striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_RANKING_POSITION'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_RANKING_TEAM'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_RANKING_MATCHES'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_RANKING_WINS'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_RANKING_DRAWS'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_RANKING_LOSSES'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_TTCLUB_RANKING_POINTS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->ranking as $row) :
                        $isOwnTeam = !empty($row['is_own_team']);
                    ?>
                        <tr class="<?php echo $isOwnTeam ? 'ttclub-own-team' : ''; ?>">
                            <td><?php echo $this->escape((string) ($row['position'] ?? '')); ?></td>
                            <td><?php echo $this->escape((string) ($row['team_name'] ?? '')); ?></td>
                            <td><?php echo $this->escape((string) ($row['matches'] ?? '')); ?></td>
                            <td><?php echo $this->escape((string) ($row['wins'] ?? '')); ?></td>
                            <td><?php echo $this->escape((string) ($row['draws'] ?? '')); ?></td>
                            <td><?php echo $this->escape((string) ($row['losses'] ?? '')); ?></td>
                            <td><?php echo $this->escape((string) ($row['points'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php // Season navigation ?>
    <?php if (!empty($this->seasons)) : ?>
        <div class="com-ttclub-team__season-nav">
            <form method="get" class="com-ttclub-team__season-form">
                <label for="team_season_select"><?php echo Text::_('COM_TTCLUB_VIEW_OTHER_SEASONS'); ?>:</label>
                <select id="team_season_select" name="season_id" onchange="this.form.submit()">
                    <?php foreach ($this->seasons as $season) : ?>
                        <option value="<?php echo (int) $season->id; ?>"
                            <?php echo ((int) $this->item->season_id === (int) $season->id) ? 'selected' : ''; ?>>
                            <?php echo $this->escape(TtclubHelper::getSeasonDisplayName((int) $season->start_year, $season->label ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="option" value="com_ttclub">
                <input type="hidden" name="view" value="teams">
                <noscript><button type="submit"><?php echo Text::_('COM_TTCLUB_GO'); ?></button></noscript>
            </form>
        </div>
    <?php endif; ?>
</div>

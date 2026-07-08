<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\Import\HtmlView $this */

$federation = $this->params->get('clicktt_federation', '');
$clubNumber = $this->params->get('clicktt_club_number', '');
$clubName = $this->params->get('clicktt_club_name', '');
$clubIds = trim((string) $this->params->get('clicktt_club_ids', ''));
$isConfigured = ($clubIds !== '');
?>

<div class="row">
    <div class="col-lg-8">
        <?php // Configuration Status ?>
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">click-TT Configuration</h3>
            </div>
            <div class="card-body">
                <?php if (!$isConfigured) : ?>
                    <div class="alert alert-warning">
                        <strong>Not configured.</strong> Please configure the click-TT connection in
                        <a href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_ttclub'); ?>">Component Options</a>.
                    </div>
                <?php else : ?>
                    <?php
                    $clubLines = array_filter(array_map('trim', explode("\n", (string) $clubIds)));
                    ?>
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Club IDs</dt>
                        <dd class="col-sm-9">
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($clubLines as $line) :
                                    $parts = explode('|', $line);
                                    if (count($parts) >= 2) {
                                        $fed = trim($parts[0]);
                                        $cid = trim($parts[1]);
                                        $clabel = trim($parts[2] ?? '');
                                    } else {
                                        $fed = '';
                                        $cid = trim($parts[0]);
                                        $clabel = '';
                                    }
                                ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($cid, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if ($fed) : ?><span class="text-muted">(<?php echo htmlspecialchars($fed, ENT_QUOTES, 'UTF-8'); ?>)</span><?php endif; ?>
                                        <?php echo $clabel ? ' — ' . htmlspecialchars($clabel, ENT_QUOTES, 'UTF-8') : ''; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </dd>
                    </dl>
                <?php endif; ?>
            </div>
        </div>

        <?php // Import Form ?>
        <form action="<?php echo Route::_('index.php?option=com_ttclub&task=import.import'); ?>" method="post" name="adminForm" id="adminForm">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Import Data</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><strong>Data types to import:</strong></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="import_types[]" value="players" id="import_players" checked>
                            <label class="form-check-label" for="import_players">Players</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="import_types[]" value="rosters" id="import_rosters" checked>
                            <label class="form-check-label" for="import_rosters">Rosters</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="import_types[]" value="schedules" id="import_schedules" checked>
                            <label class="form-check-label" for="import_schedules">Schedules</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="season_id" class="form-label"><strong>Season:</strong></label>
                        <select name="season_id" id="season_id" class="form-select">
                            <option value="">- Select Season (optional for Full Import) -</option>
                            <?php
                            $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
                            $query = $db->getQuery(true)
                                ->select(['id', 'start_year'])
                                ->from($db->quoteName('#__ttclub_seasons'))
                                ->where($db->quoteName('published') . ' = 1')
                                ->order('start_year DESC');
                            $db->setQuery($query);
                            $seasons = $db->loadObjectList();
                            foreach ($seasons as $season) :
                                $sy = (int) $season->start_year;
                                $label = sprintf('%d/%02d', $sy, ($sy + 1) % 100);
                            ?>
                                <option value="<?php echo (int) $season->id; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="half_season_id" class="form-label"><strong>Half-Season (for rosters):</strong></label>
                        <select name="half_season_id" id="half_season_id" class="form-select">
                            <option value="">- Select Half-Season -</option>
                            <?php
                            $query = $db->getQuery(true)
                                ->select(['hs.id', 'hs.half', 's.start_year'])
                                ->from($db->quoteName('#__ttclub_half_seasons', 'hs'))
                                ->innerJoin($db->quoteName('#__ttclub_seasons', 's') . ' ON s.id = hs.season_id')
                                ->where($db->quoteName('s.published') . ' = 1')
                                ->order('s.start_year DESC, hs.half ASC');
                            $db->setQuery($query);
                            $halfSeasons = $db->loadObjectList();
                            foreach ($halfSeasons as $hs) :
                                $sy = (int) $hs->start_year;
                                $label = sprintf('%d/%02d - %s', $sy, ($sy + 1) % 100, (int)$hs->half === 1 ? 'First Half' : 'Second Half');
                            ?>
                                <option value="<?php echo (int) $hs->id; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" id="btn-import-selected" class="btn btn-primary" disabled <?php echo !$isConfigured ? '' : ''; ?>>
                        <span class="icon-download" aria-hidden="true"></span> Import Selected
                    </button>
                    <span class="mx-2">or</span>
                    <button type="submit" name="season_id" value="0" class="btn btn-success" <?php echo !$isConfigured ? 'disabled' : ''; ?>>
                        <span class="icon-refresh" aria-hidden="true"></span> Full Import (all clubs, all seasons)
                    </button>
                </div>
            </div>

            <input type="hidden" name="task" value="import.import">
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var seasonSelect = document.getElementById('season_id');
            var halfSeasonSelect = document.getElementById('half_season_id');
            var importBtn = document.getElementById('btn-import-selected');
            var isConfigured = <?php echo $isConfigured ? 'true' : 'false'; ?>;

            function updateButtonState() {
                var seasonSelected = seasonSelect.value !== '';
                var halfSeasonSelected = halfSeasonSelect.value !== '';
                importBtn.disabled = !isConfigured || !seasonSelected || !halfSeasonSelected;
            }

            seasonSelect.addEventListener('change', updateButtonState);
            halfSeasonSelect.addEventListener('change', updateButtonState);
            updateButtonState();
        });
        </script>

        <?php // Import from URL (Parallel Seasons) ?>
        <form action="<?php echo Route::_('index.php?option=com_ttclub&task=import.importUrl'); ?>" method="post" name="urlImportForm" id="urlImportForm">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Import Parallel Season via URL</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Paste a click-tt.de URL to import data for a parallel season (e.g., cup or Pokal competitions).
                        The season label will be derived automatically from the championship name. No season or half-season selection is required.
                    </p>
                    <div class="mb-3">
                        <label for="click_tt_url" class="form-label"><strong>click-tt.de URL:</strong></label>
                        <input type="url" name="click_tt_url" id="click_tt_url" class="form-control"
                               placeholder="https://battv.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?club=..."
                               size="80">
                        <div class="form-text">
                            Example: A clubPools or clubTeams page URL from click-tt.de for a cup competition.
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" id="btn-import-url" class="btn btn-info" disabled>
                        <span class="icon-download" aria-hidden="true"></span> Import from URL
                    </button>
                </div>
            </div>
            <input type="hidden" name="task" value="import.importUrl">
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var urlInput = document.getElementById('click_tt_url');
            var urlBtn = document.getElementById('btn-import-url');

            function updateUrlButtonState() {
                var url = urlInput.value.trim();
                urlBtn.disabled = url === '' || !url.includes('click-tt.de');
            }

            urlInput.addEventListener('input', updateUrlButtonState);
            updateUrlButtonState();
        });
        </script>

        <?php // Historical Import Link ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Historical Import (First-Time Setup)</h3>
            </div>
            <div class="card-body">
                <p>Import all available past seasons from mytischtennis.de in one bulk operation.</p>
                <a href="<?php echo Route::_('index.php?option=com_ttclub&view=historicalimport'); ?>" class="btn btn-outline-secondary" <?php echo !$isConfigured ? 'disabled' : ''; ?>>
                    <span class="icon-archive" aria-hidden="true"></span> Open Historical Import
                </a>
            </div>
        </div>
    </div>
</div>

<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\Model;

use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * Site Players list model.
 *
 * Lists players with at least one publicly visible attribute,
 * ordered alphabetically by last name.
 */
class PlayersModel extends ListModel
{
    /**
     * The current half-season object (cached).
     *
     * @var object|null
     */
    protected ?object $currentHalfSeason = null;

    /**
     * Whether the half-season was resolved (avoid repeated lookups).
     *
     * @var bool
     */
    protected bool $halfSeasonResolved = false;

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.first_name'),
            $db->quoteName('a.last_name'),
        ])
            ->from($db->quoteName('#__ttclub_players', 'a'))
            ->where($db->quoteName('a.published') . ' = 1');

        // Only show players with at least one publicly visible attribute
        $visibleFields = $this->getVisibleFields();

        if (empty($visibleFields)) {
            // No visible fields configured — no players should be shown
            $query->where('1 = 0');
            return $query;
        }

        // Join player image for the current half-season
        $halfSeason = $this->getCurrentHalfSeason();

        if ($halfSeason !== null) {
            $halfSeasonId = (int) $halfSeason->id;
            $query->select($db->quoteName('pi.image_path', 'image_path'))
                ->join(
                    'LEFT',
                    $db->quoteName('#__ttclub_player_images', 'pi') . ' ON ' .
                    $db->quoteName('pi.player_id') . ' = ' . $db->quoteName('a.id') . ' AND ' .
                    $db->quoteName('pi.half_season_id') . ' = :halfSeasonId'
                )
                ->bind(':halfSeasonId', $halfSeasonId, \Joomla\Database\ParameterType::INTEGER);
        } else {
            $query->select('NULL AS ' . $db->quoteName('image_path'));
        }

        // Order alphabetically by last name
        $query->order($db->quoteName('a.last_name') . ' ASC, ' . $db->quoteName('a.first_name') . ' ASC');

        return $query;
    }

    /**
     * Get the current half-season using TtclubHelper.
     *
     * @return object|null
     */
    public function getCurrentHalfSeason(): ?object
    {
        if (!$this->halfSeasonResolved) {
            $this->currentHalfSeason = TtclubHelper::getCurrentHalfSeason($this->getDatabase());
            $this->halfSeasonResolved = true;
        }

        return $this->currentHalfSeason;
    }

    /**
     * Get the list of player fields configured as publicly visible.
     *
     * @return array
     */
    public function getVisibleFields(): array
    {
        $params = $this->getState('params');

        if ($params === null) {
            return [];
        }

        $fields = $params->get('player_visible_fields', ['first_name', 'last_name']);

        if (\is_string($fields)) {
            $fields = explode(',', $fields);
        }

        return \is_array($fields) ? array_map('trim', $fields) : [];
    }

    protected function populateState($ordering = 'a.last_name', $direction = 'ASC'): void
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $params = $app->getParams('com_ttclub');
        $this->setState('params', $params);

        parent::populateState($ordering, $direction);
    }
}

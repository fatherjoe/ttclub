<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

class PlayersModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'first_name', 'a.first_name',
                'last_name', 'a.last_name',
                'published', 'a.published',
                'club_id',
            ];
        }

        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('a.*')
            ->from($db->quoteName('#__ttclub_players', 'a'));

        // Join to get current roster assignment status
        $query->select('COUNT(DISTINCT ' . $db->quoteName('r.id') . ') AS roster_count')
            ->join(
                'LEFT',
                $db->quoteName('#__ttclub_rosters', 'r') . ' ON ' .
                $db->quoteName('r.player_id') . ' = ' . $db->quoteName('a.id')
            );

        // Join to get associated club ID labels
        $query->select('GROUP_CONCAT(DISTINCT ' . $db->quoteName('ci.label') . ' ORDER BY ' . $db->quoteName('ci.ordering') . ' ASC SEPARATOR ' . $db->quote(', ') . ') AS club_labels')
            ->join(
                'LEFT',
                $db->quoteName('#__ttclub_player_club_ids', 'pci') . ' ON ' .
                $db->quoteName('pci.player_id') . ' = ' . $db->quoteName('a.id')
            )
            ->join(
                'LEFT',
                $db->quoteName('#__ttclub_club_ids', 'ci') . ' ON ' .
                $db->quoteName('ci.id') . ' = ' . $db->quoteName('pci.club_id')
            );

        $query->group($db->quoteName('a.id'));

        // Filter by club_id
        $clubId = $this->getState('filter.club_id');

        if (is_numeric($clubId) && (int) $clubId > 0) {
            $clubIdInt = (int) $clubId;
            $query->join(
                'INNER',
                $db->quoteName('#__ttclub_player_club_ids', 'pci_filter') . ' ON ' .
                $db->quoteName('pci_filter.player_id') . ' = ' . $db->quoteName('a.id') .
                ' AND ' . $db->quoteName('pci_filter.club_id') . ' = ' . $clubIdInt
            );
        }

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by search term (case-insensitive LIKE on last_name)
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . trim($search) . '%';
            $query->where('LOWER(' . $db->quoteName('a.last_name') . ') LIKE LOWER(:search)')
                ->bind(':search', $search);
        }

        // Add ordering
        $orderCol = $this->getState('list.ordering', 'a.last_name');
        $orderDirn = $this->getState('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Get all configured club IDs for the filter dropdown.
     *
     * @return array List of club ID objects with id and label.
     */
    public function getClubIdOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('label')])
            ->from($db->quoteName('#__ttclub_club_ids'))
            ->order($db->quoteName('ordering') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    protected function populateState($ordering = 'a.last_name', $direction = 'ASC'): void
    {
        $clubId = $this->getUserStateFromRequest($this->context . '.filter.club_id', 'filter_club_id', '');
        $this->setState('filter.club_id', $clubId);

        parent::populateState($ordering, $direction);
    }
}

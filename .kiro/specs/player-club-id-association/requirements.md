# Requirements Document

## Introduction

This feature adds a many-to-many association between players and club IDs, tracking which club registrations a player was imported from. Currently, teams store their `club_id_source` to indicate which club ID configuration they originated from, but players lack this traceability. Since a player can appear in rosters under multiple club IDs (e.g., adult teams and youth teams registered under different club numbers), the system needs to record all club IDs where a player was listed.

## Glossary

- **Component**: The Joomla 6 extension `com_ttclub` following MVC architecture for table tennis club management
- **Player**: A registered member stored in `#__ttclub_players`, identified by first name and last name
- **Club_ID_Entry**: A configured club registration record in `#__ttclub_club_ids`, representing one click-tt.de club identifier with associated metadata (federation, club name, label)
- **Player_Club_Association**: A link record connecting a Player to a Club_ID_Entry, stored in the junction table `#__ttclub_player_club_ids`
- **Import_Service**: The `ClickTtImportService` class that fetches and processes data from click-tt.de for a specific club ID configuration
- **Administrator**: A Joomla user with permissions to manage the component data in the backend

## Requirements

### Requirement 1: Store Player-to-Club-ID Associations

**User Story:** As an administrator, I want the system to store which club IDs a player was imported from, so that I can trace a player's registrations across different club configurations.

#### Acceptance Criteria

1. THE Component SHALL maintain a junction table `#__ttclub_player_club_ids` with columns `id` (INT UNSIGNED, AUTO_INCREMENT, PRIMARY KEY), `player_id` (INT UNSIGNED, FK to `#__ttclub_players.id`), and `club_id` (INT UNSIGNED, FK to `#__ttclub_club_ids.id`) to represent the many-to-many relationship
2. THE Component SHALL enforce a unique constraint on the combination of `player_id` and `club_id` in the junction table to prevent duplicate associations
3. IF an insert into `#__ttclub_player_club_ids` violates the unique constraint on (`player_id`, `club_id`), THEN THE Component SHALL reject the insert and leave existing records unchanged
4. THE Component SHALL define foreign key constraints from `player_id` to `#__ttclub_players.id` and from `club_id` to `#__ttclub_club_ids.id`
5. WHEN a Club_ID_Entry is deleted, THE Component SHALL cascade the deletion to all Player_Club_Association records referencing that Club_ID_Entry
6. WHEN a Player is deleted, THE Component SHALL cascade the deletion to all Player_Club_Association records referencing that Player

### Requirement 2: Record Associations During Import

**User Story:** As an administrator, I want the import process to automatically record which club ID a player was found under, so that associations are maintained without manual intervention.

#### Acceptance Criteria

1. WHEN the Import_Service creates a new Player during import for a specific Club_ID_Entry, THE Component SHALL insert a Player_Club_Association linking that Player's ID to the Club_ID_Entry's ID before proceeding to the next player in the roster
2. WHEN the Import_Service matches an existing Player during import for a specific Club_ID_Entry, THE Component SHALL attempt to insert a Player_Club_Association linking that Player to the Club_ID_Entry, and SHALL treat a duplicate-key condition (association already exists) as a successful no-op
3. WHEN a Player appears in rosters under multiple Club_ID_Entries across successive imports, THE Component SHALL accumulate all distinct associations, so that the Player is linked to every Club_ID_Entry where the Player was listed
4. THE Import_Service SHALL not remove existing Player_Club_Association records during import, even if the Player no longer appears in the current roster data returned by click-tt.de for that Club_ID_Entry
5. IF a Player_Club_Association insert fails for a reason other than a duplicate-key constraint, THEN THE Import_Service SHALL log the failure and continue processing the remaining players in the current import without aborting

### Requirement 3: Display Player Club Associations in the Backend

**User Story:** As an administrator, I want to see which club IDs a player belongs to, so that I can understand a player's registration context at a glance.

#### Acceptance Criteria

1. WHEN an administrator views the player list in the backend, THE Component SHALL display the associated Club_ID_Entry labels for each player as a comma-separated list within the player's row
2. WHEN an administrator views a single player record in the backend, THE Component SHALL display all associated Club_ID_Entry labels as a read-only list within the player edit form
3. WHEN a Player has no Club_ID_Entry associations, THE Component SHALL display an empty state (no labels shown) rather than an error in both the player list and the player edit form
4. THE Component SHALL use the `label` field of each associated Club_ID_Entry as the display text for club associations

### Requirement 4: Filter Players by Club ID

**User Story:** As an administrator, I want to filter the player list by club ID, so that I can view only players belonging to a specific club registration.

#### Acceptance Criteria

1. WHEN an administrator selects a Club_ID_Entry from the filter dropdown on the player list, THE Component SHALL display only players that have a Player_Club_Association with the selected Club_ID_Entry
2. WHEN no filter is selected, THE Component SHALL display all players regardless of their club associations
3. THE Component SHALL populate the filter dropdown with all configured Club_ID_Entry records, using the label field as display text

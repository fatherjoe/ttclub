# Requirements Document

## Introduction

The Table Tennis Club Manager is a Joomla 6 component that enables table tennis clubs to manage and display their players, teams, and team schedules. The component provides a backend administration interface for CRUD operations and import via scraping from a web page and a frontend site view for public display. A key concept is that team rosters are managed per half-season, allowing player assignments to change between the first and second halves of a season.

## Glossary

- **Component**: A Joomla 6 extension following MVC architecture that provides both backend (administrator) and frontend (site) functionality
- **Player**: A registered member of the table tennis club who can be assigned to teams
- **Team**: A named group of players competing together in a specific season half. Teams are identified by an administrator-assigned team number, the age class, and a half season. The team name is composed of the team number, the age class and the half season, e.g. "1. Herren 2025/26 (Hinrunde)"
- **Age_Class**: A category that defines the maximum age of players eligible for a team (e.g., "Herren" or "Erwachsene" for open/adult, "Jugend U19" for players no older than 19, "Jugend U15" for players no older than 15)
- **Season**: A competitive period consisting of exactly two halves (first half and second half or first leg and second leg). The second half season is executed in the year following on the first half season. Seasons are identified by a start year (e.g., 2025) and displayed as "2025/26". The name is derived automatically from the start year. In addition to the normal league season there may be other seasons (e.g. cup matches) which are identified by the start year and a label.
- **Half_Season**: One of two periods within a season (first half or second half) during which team rosters remain fixed. A half-season is identified by its season and half number (1 or 2). The current half-season is determined by the calendar month: August–December is the first half, January–July is the second half. In some translations the half seasons get an own name, e.g. Hinrunde for first leg and Rückrunde for second leg.
- **Roster**: The set of players assigned to a specific team for a specific half-season. A player can be assigned to multiple rosters per half season. Each player in a roster has a position number that can be entered manually.
- **Schedule**: A collection of matches or fixtures assigned to a team within a half season
- **League**: A competitive division or tier in which a team plays during a season (e.g., "Kreisliga Staffel 1", "VOL Gr. 1"); a team's league assignment is fixed for the entire season
- **click-tt.de**: The official table tennis results service operated by regional federations (e.g., battv.click-tt.de for Baden, Germany). Provides team rosters, match results, and league standings. Used as the primary data source for import.
- **mytischtennis.de**: An external website that publishes official table tennis data including player information, team compositions, and match schedules; provides a frontend to click-tt.de data
- **Backend**: The Joomla administrator area accessible only to authorized administrators
- **Frontend**: The Joomla site area accessible to public visitors
- **Administrator**: A Joomla user with permissions to manage the component data in the backend

## Requirements

### Requirement 1: Manage Club Configuration

**User Story:** As an administrator, I want to configure multiple click-tt.de club IDs, so that I can import data from different registrations (e.g., one for the adult teams and another for a youth game association).

#### Acceptance Criteria

1. THE Component SHALL allow the administrator to configure multiple click-tt.de club IDs, each with a label identifying its purpose (e.g., "Erwachsene", "Jugend-SG") and a desciption
2. WHEN an administrator triggers a data import, THE Component SHALL iterate over all configured club IDs and import data from each one
3. THE Component SHALL associate imported teams with the club ID they were imported from, so that teams from different registrations can be distinguished
4. THE Component SHALL merge player records across club IDs using the first name and last name combination as the unique identifier, so that a player appearing in both registrations is not duplicated
5. THE Component SHALL allow the administrator to add, edit, and remove club ID entries from the component configuration
6. IF no club IDs are configured, THEN THE Component SHALL display a warning on the import page indicating that at least one club ID must be configured before importing
7. THE Component SHALL provide a dedicated backend form for managing club information, where the administrator can set or update the following fields for each club entry: club ID (the current click-tt.de identifier), legacy club ID (a previous or alternate identifier), club name, and federation abbreviation (e.g., "BaTTV", "WTTV")
8. THE Component SHALL store all club information fields (club ID, legacy club ID, club name, federation) in the `#__ttclub_club_ids` table
9. THE Component SHALL use the federation abbreviation from the club entry when constructing import URLs for that club, rather than requiring a single global federation setting
10. WHEN an administrator views the clubs list in the backend, THE Component SHALL display the club IDs (click-tt.de identifiers) in the list alongside each club entry

### Requirement 2: Manage Seasons

**User Story:** As an administrator, I want to create and manage seasons, so that I can organize team competition into defined time periods.

#### Acceptance Criteria

1. WHEN an administrator submits a new season form with a valid start year (1900–2100), THE Component SHALL create a new season record and automatically create both half-season records (half=1 and half=2) linked to it
2. WHEN an administrator requests the season list, THE Component SHALL display all season records with their derived display name (format "YYYY/YY", e.g., "2025/26")
3. WHEN an administrator submits an updated season form with valid data, THE Component SHALL update the corresponding season record in the database
4. WHEN an administrator requests deletion of a season record, THE Component SHALL remove the season record and its associated half-season records from the database
5. IF a season form is submitted with a missing or invalid start year, THEN THE Component SHALL display a validation error message
6. IF an administrator requests deletion of a season that has roster assignments or schedule entries, THEN THE Component SHALL prevent deletion and display a message indicating the season has associated data
7. THE Component SHALL enforce that each season consists of exactly two half-seasons (first half and second half), created automatically on season save
8. THE Component SHALL determine the current half-season by calendar month: August through December is the first half of the season starting that year; January through July is the second half of the season that started the previous year
9. IF no half-season matches the current calendar period, THEN THE Component SHALL use the most recent season's latest half-season as the current half-season
10. THE Component SHALL allow the administrator to create seasons with an optional label (e.g., "Pokal") in addition to the start year, resulting in display names like "Pokal 2025/26"
11. THE Component SHALL allow multiple seasons with the same start year to coexist, distinguished by their label (e.g., "2025/26" for the main season and "Pokal 2025/26" for the cup)
12. THE Component SHALL enforce uniqueness on the combination of start year and label, preventing duplicate seasons with the same year and label
13. WHEN no label is provided, THE Component SHALL treat the season as the main league season and display it as "YYYY/YY" (e.g., "2025/26")
14. THE Component SHALL allow teams to be assigned to any season regardless of whether it is a main season or a parallel tournament season
15. THE Component SHALL display parallel seasons alongside main seasons in the frontend season navigation, so visitors can view both league and cup data
16. WHEN importing data from click-tt.de, THE Component SHALL create separate parallel seasons for cup competitions (identified by "Pokal" in the championship name) distinct from the main league season

### Requirement 3: Manage Leagues

**User Story:** As an administrator, I want to create and manage leagues, so that I can organize teams into competitive divisions.

#### Acceptance Criteria

1. WHEN an administrator submits a new league form with a league name of 1 to 100 characters, THE Component SHALL create a new league record and store it in the database
2. WHEN an administrator requests the league list, THE Component SHALL display all league records showing at minimum the league name and the number of teams currently assigned to each league
3. WHEN an administrator submits an updated league form with a league name of 1 to 100 characters, THE Component SHALL update the corresponding league record in the database
4. WHEN an administrator requests deletion of a league record, THE Component SHALL remove the league record from the database
5. IF a league form is submitted with a missing or empty league name, THEN THE Component SHALL display a validation error message indicating the league name is required
6. IF an administrator requests deletion of a league that has teams assigned to it, THEN THE Component SHALL prevent deletion and display a message indicating the league still has associated teams
7. IF a league form is submitted with a league name that already exists in the database, THEN THE Component SHALL reject the submission and display a validation error message indicating the name must be unique

### Requirement 4: Manage Age Classes

**User Story:** As an administrator, I want to manage age classes, so that I can categorize teams by player age eligibility.

#### Acceptance Criteria

1. THE Component SHALL allow the administrator to manage age classes (create, update, delete)
2. IF an administrator requests deletion of an age class that is assigned to one or more teams, THEN THE Component SHALL prevent deletion and display a message indicating the age class is still in use

### Requirement 5: Manage Teams

**User Story:** As an administrator, I want to create, read, update, and delete team records, so that I can organize club members into competitive teams.

#### Acceptance Criteria

1. WHEN an administrator submits a new team form with valid data including an administrator-specified team number, THE Component SHALL create a new team record with that team number within the selected season
2. WHEN an administrator requests the team list, THE Component SHALL display all team records with their team number, league assignment, age class, and season
3. WHEN an administrator submits an updated team form with valid data, THE Component SHALL update the corresponding team record in the database
4. WHEN an administrator requests deletion of a team record, THE Component SHALL remove the team record from the database
5. IF a team form is submitted with missing required fields (season, league, age class), THEN THE Component SHALL display a validation error message indicating the missing fields
6. IF an administrator requests deletion of a team that has roster assignments, THEN THE Component SHALL prevent deletion and display a message indicating the team still has assigned players; the deletion prevention SHALL apply regardless of whether the message is successfully displayed
7. WHEN an administrator assigns a team to a season, THE Component SHALL require a league assignment for that team
8. THE Component SHALL keep the league assignment for a team fixed for the entire season; IF an administrator attempts to change the league of an existing team, THEN THE Component SHALL display an error message indicating that league changes are not permitted after creation
9. WHEN an administrator uploads a team photo (JPEG or PNG, max 5 MB) for a specific half-season, THE Component SHALL store the photo and associate it with the team and the selected half-season
10. THE Component SHALL allow a different team photo per half-season
11. THE Component SHALL require an age class assignment for each team

### Requirement 6: Assign Players to Teams per Half-Season

**User Story:** As an administrator, I want to assign players to teams for each half-season independently, so that team rosters can change between the first and second halves of a season.

#### Acceptance Criteria

1. WHEN an administrator assigns a player to a team for a specific half-season, THE Component SHALL create a roster entry linking the player, team, and half-season, with an optional position number that can be entered manually
2. WHEN an administrator removes a player from a team roster for a specific half-season, THE Component SHALL delete the corresponding roster entry
3. WHEN an administrator views a team roster, THE Component SHALL display the players assigned to that team for the selected half-season, ordered by position number ascending (players without a position sort last)
4. THE Component SHALL allow a player to be assigned to different teams in the first half and second half of the same season
5. THE Component SHALL allow a player to be assigned to multiple teams within the same half-season
6. THE Component SHALL allow multiple teams to exist within the same season
7. WHEN an administrator requests to copy a roster to the next half-season, THE Component SHALL duplicate all player assignments from the selected team and half-season to the subsequent half-season (second half of the same season, or first half of the next season if copying from the second half)
8. IF the target half-season of a roster copy already has player assignments for that team, THEN THE Component SHALL prompt the administrator to confirm whether to merge with or replace the existing assignments
9. IF an administrator attempts to assign the same player to the same team and half-season that already has an existing assignment, THEN THE Component SHALL reject the duplicate and display a message indicating the player is already assigned to that team for the selected half-season; duplicate rejection messages SHALL also be displayed during any system operation (including roster loading or validation checks) that detects a duplicate assignment state
10. WHEN displaying a team roster on the frontend, THE Component SHALL exclude players whose published state is set to unpublished (published = 0), so that only active players are visible to site visitors
11. WHEN importing roster data from click-tt.de, THE Component SHALL also import players listed on the teamportrait page who are assigned from lower-numbered teams (e.g., a player with position notation "3.1" appearing on the Team 2 portrait page SHALL be added to Team 2's roster), so that replacement players from lower teams are included in the team's roster

### Requirement 7: Manage Players

**User Story:** As an administrator, I want to create, read, update, and delete player records, so that I can maintain an accurate list of club members.

#### Acceptance Criteria

1. WHEN an administrator submits a new player form with valid data (first name 1–50 characters, last name 1–50 characters), birth date, click-tt player id THE Component SHALL create a new player record and store it in the database
2. WHEN an administrator requests the player list, THE Component SHALL display all player records showing at minimum first name, last name, and current roster assignment status
3. WHEN an administrator submits an updated player form with valid data, THE Component SHALL update the corresponding player record in the database
4. WHEN an administrator requests deletion of a player record, THE Component SHALL remove the player record from the database
5. IF a player form is submitted with a missing or empty first name or last name, THEN THE Component SHALL display a validation error message indicating which required fields are missing
6. IF an administrator requests deletion of a player who is assigned to a roster, THEN THE Component SHALL display a message indicating the player is still assigned to a team and list which teams he is assigned to and asks for confirmation if the player should really be deleted; if the player is deleted he is also removed from all rosters.
7. THE Component SHALL only display the roster assignment prevention message when the player is actually assigned to a roster at the time of the deletion attempt
7. WHEN an administrator uploads a player image (JPEG or PNG, max 2 MB) for a specific half-season, THE Component SHALL store the image and associate it with the player and the selected half-season
8. THE Component SHALL allow a different player image per half-season
9. THE Component SHALL support searching and filtering the player list by last name

### Requirement 8: Import Data from click-tt.de

**User Story:** As an administrator, I want to import player, team, roster, and schedule data from click-tt.de, so that I can populate and update the component data without manual entry.

#### Acceptance Criteria

1. WHEN an administrator triggers a full import, THE Component SHALL resolve the click-tt internal club ID by fetching the `clubSearch` page with the configured BaTTV club ID and extracting the `club=` parameter from the "Spielbetrieb und Ergebnisse" link
2. WHEN the click-tt club ID is resolved, THE Component SHALL POST to the `clubMeetings` endpoint with the season date range (01.08.{startYear} to 31.07.{startYear+1}) to retrieve a list of all matches for the season
3. THE Component SHALL parse the clubMeetings response table to extract for each team: league short name, team name, championship_id, and group_id from the match report links
4. WHEN the import has extracted championship_id and group_id for a team, THE Component SHALL fetch the `groupPage` endpoint with those parameters to resolve the full league name (e.g., "Kreisliga Staffel 1")
5. IF the groupPage fetch fails or the league name cannot be extracted, THEN THE Component SHALL fall back to the short abbreviation from the meetings table or use "Unbekannt" as the league name
6. WHEN an administrator triggers a data import, THE Component SHALL fetch team roster data from click-tt.de using the `clubPools` endpoint and create or update corresponding player and roster records
7. WHEN the import process completes successfully, THE Component SHALL display a summary indicating how many records were created, updated, or unchanged
8. IF the import encounters a connection error or invalid response from click-tt.de, THEN THE Component SHALL display an error message including the attempted URL and leave existing data unchanged
9. THE Component SHALL provide a dedicated backend configuration page where the administrator can configure the club IDs (each with federation abbreviation, BaTTV club ID, legacy club ID, and club name)
10. THE Component SHALL use the click-tt.de URL patterns:
    - Club search: `https://{federation}.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/clubSearch?federation={federation}&searchFor={battvId}`
    - Club meetings (POST): `https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetings`
    - Group page: `https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/groupPage?championship={championship_id}&group={group_id}`
    - Club pools: `https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?club={clubId}`
11. THE Component SHALL log all import operations with timestamps for audit purposes
12. THE Component SHALL match imported player records to existing records using the player's first name and last name combination as the unique identifier (case-insensitive)
13. THE Component SHALL support a "Full Import" mode that does not require a season or half-season to be selected; it discovers all available data from click-tt.de and imports teams, schedules, and rosters
14. WHEN importing rosters, THE Component SHALL parse player positions from the position notation "X.Y" where X is the team number and Y is the player's position within the team
15. THE Component SHALL automatically create seasons and half-seasons during import if they do not already exist in the database
16. THE Component SHALL create schedule entries from the clubMeetings response, including match date, time, opponent, home/away indicator, and result (if available)
17. WHEN looking up or creating teams during import, THE Component SHALL identify a team uniquely by the combination of season, team number, club ID source, AND age class — so that "Team 1 Herren" from one club registration is treated as a distinct team from "Team 1 Jungen U19" from the same or different club registration

### Requirement 9: First-Time Historical Data Import

**User Story:** As an administrator, I want to perform a one-time bulk import of all historical team data from mytischtennis.de or click-tt.de, so that I can bootstrap the component with a complete set of former teams and seasons without manual entry.

#### Acceptance Criteria

1. WHEN an administrator triggers the first-time import, THE Component SHALL discover all available seasons for the configured club on mytischtennis.de or click-tt.de by recursively navigating the season archive pages
2. WHEN the first-time import discovers available seasons, THE Component SHALL create season records for each discovered season that does not already exist in the database
3. WHEN the first-time import processes a discovered season, THE Component SHALL scrape and create team records including team number, league assignment, and age class for all teams found in that season
4. WHEN the first-time import processes a discovered team, THE Component SHALL scrape and create roster entries linking players to teams for each half-season available in the source data
5. WHEN the first-time import processes a discovered team, THE Component SHALL scrape and create schedule entries including match dates, opponents, venues, home/away indicators, and results for each team in each season
6. WHEN the first-time import encounters a player not yet present in the database, THE Component SHALL create a new player record using the player's first name and last name as scraped from the source
7. WHEN the first-time import completes successfully, THE Component SHALL display a summary indicating the total number of seasons, teams, players, roster entries, and schedule entries created
8. IF the first-time import encounters a connection error or invalid response from mytischtennis.de or click-tt.de, THEN THE Component SHALL display an error message indicating which season or page caused the failure and leave existing data unchanged
9. IF the database already contains season or team records, THEN THE Component SHALL warn the administrator that this operation is intended for initial setup and prompt for confirmation before proceeding
10. THE Component SHALL log all first-time import operations with timestamps and per-season status for audit purposes
11. THE Component SHALL match imported player records to existing records using the player's first name and last name combination as the unique identifier, creating new records only when no match is found
12. THE Component SHALL provide the administrator with the option to select either mytischtennis.de or click-tt.de as the data source for the first-time import

### Requirement 10: Import Parallel Seasons via click-tt.de Link

**User Story:** As an administrator, I want to import parallel season data (e.g., cup matches, Sommer-Team-Cup) by providing a click-tt.de link, so that I can populate tournament data without manual entry.

#### Acceptance Criteria

1. THE Component SHALL provide an input field on the import page where the administrator can paste a valid click-tt.de URL pointing to a parallel season's data (e.g., a clubPools or clubTeams page for a cup competition)
2. WHEN an administrator submits a valid click-tt.de URL for a parallel season, THE Component SHALL parse the URL to extract the federation, club ID, and season/competition identifiers
3. WHEN the import processes a parallel season URL, THE Component SHALL create a season record with the appropriate label (derived from the championship name in the source data, e.g., "Pokal", "Sommer-Team-Cup") if it does not already exist
4. WHEN the import processes a parallel season URL, THE Component SHALL import teams, rosters, and schedule entries from the linked page and associate them with the corresponding parallel season
5. IF the provided URL is not a valid click-tt.de URL or returns an error response, THEN THE Component SHALL display an error message indicating the URL is invalid or unreachable and leave existing data unchanged
6. THE Component SHALL match imported player records from parallel season imports to existing records using the first name and last name combination as the unique identifier (case-insensitive), so that players are not duplicated
7. THE Component SHALL log parallel season import operations with timestamps for audit purposes
8. THE Component SHALL allow parallel season imports independently of the regular full import — no season or half-season selection is required in the form when importing via URL

### Requirement 11: Frontend Player Display

**User Story:** As a site visitor, I want to view the list of club players, so that I can see who is a member of the club.

#### Acceptance Criteria

1. WHEN a visitor accesses the players overview page, THE Component SHALL display a grid of all players who have at least one publicly visible attribute, showing each player's image for the current half-season and their name, ordered alphabetically by last name
2. WHEN a visitor selects a specific player from the overview, THE Component SHALL display the player detail view showing all player fields that the administrator has designated as publicly visible; IF the detail view fails to load due to a technical error, THEN THE Component SHALL display an error message indicating the player details could not be retrieved
3. THE Component SHALL only display player information designated as publicly visible by the administrator
4. THE Component SHALL display the player image corresponding to the current half-season on the overview page and the selected half-season on the detail view
5. IF no image exists for the selected half-season, THEN THE Component SHALL display a default placeholder image
6. IF no players with publicly visible information exist, THEN THE Component SHALL display an informational message indicating that no player data is available
7. IF no current half-season can be determined from the configured seasons, THEN THE Component SHALL display players using the most recent half-season that has ended

### Requirement 12: Frontend Team Display

**User Story:** As a site visitor, I want to view the club's teams and their current rosters, so that I can see team compositions.

#### Acceptance Criteria

1. WHEN a visitor accesses the teams overview page, THE Component SHALL display all teams for the current half-season with their team photos, names, age classes, and league assignments, ordered by team number in ascending order
2. IF no season is explicitly selected, THEN THE Component SHALL display teams of the half-season whose date range contains the current date
3. THE Component SHALL allow visitors to navigate to previous seasons and view the teams from those periods
4. WHEN a visitor selects a specific team, THE Component SHALL display the team detail view including the league, the team photo for the selected half-season, and the roster showing each assigned player's name and player image for that half-season
5. THE Component SHALL allow visitors to switch between first-half and second-half rosters for the selected season
6. THE Component SHALL display the team photo corresponding to the current or selected half-season
7. IF no team photo exists for the selected half-season, THEN THE Component SHALL display a default placeholder image
8. IF no players are assigned to the team roster for the selected half-season, THEN THE Component SHALL display a message indicating that no roster is available for that period; other roster display elements (such as team photo, league, and navigation controls) MAY still appear alongside the no-roster message

### Requirement 13: League Ranking Table Display

**User Story:** As a site visitor, I want to see the current league ranking table on a team's page, so that I can understand the team's standing in their league.

#### Acceptance Criteria

1. WHEN a visitor views a team's frontend detail page, THE Component SHALL fetch the current ranking table for that team's league and half-season from click-tt.de and display it as an HTML table embedded on the page
2. THE Component SHALL display the ranking table with at minimum: position, team name, matches played, wins, draws, losses, and points
3. THE Component SHALL highlight the club's own team within the ranking table so that visitors can quickly identify their team's position
4. IF the ranking data cannot be fetched from click-tt.de due to a connection error or invalid response, THEN THE Component SHALL display an informational message indicating that the ranking is temporarily unavailable, without breaking the rest of the team page
5. THE Component SHALL cache the fetched ranking data for a configurable duration (default: 3 days) to avoid excessive requests to click-tt.de
6. WHEN the cached ranking data has expired, THE Component SHALL fetch fresh ranking data from click-tt.de on the next page request
7. THE Component SHALL associate ranking tables with a specific league and half-season, so that each half-season displays the correct ranking for that period
8. THE Component SHALL provide an administrator configuration option to set the cache duration for ranking table data

### Requirement 14: Team Page Match Schedule Table

**User Story:** As a site visitor, I want to see a team's match schedule displayed as a table on the team's page, so that I can see upcoming fixtures and past results fetched live from click-tt.de.

#### Acceptance Criteria

1. WHEN a visitor views a team's frontend detail page, THE Component SHALL fetch the team's match schedule from click-tt.de using the team's stored championship_id and group_id, and display it as an HTML table embedded on the page
2. THE Component SHALL fetch the schedule by POSTing to the `clubMeetings` endpoint with the team's club_id and the season date range, filtering results by the team's name
3. THE Component SHALL display for each schedule entry in the table: date, time, home team name, guest team name
4. IF a match has a recorded result, THEN THE Component SHALL display the match result in the schedule table alongside that entry
5. IF a match date is in the future or no result has been recorded, THEN THE Component SHALL leave the result column empty for that entry
6. THE Component SHALL order the schedule table entries by match date in ascending order
7. IF no schedule data can be retrieved from click-tt.de (connection error or invalid response), THEN THE Component SHALL display an informational message indicating that the schedule is temporarily unavailable, without breaking the rest of the team page
8. THE Component SHALL cache the fetched schedule data for a configurable duration (default: 3 days) to avoid excessive requests to click-tt.de
9. WHEN the cached schedule data has expired, THE Component SHALL fetch fresh schedule data from click-tt.de on the next page request

### Requirement 15: Joomla 6 Architecture Compliance

**User Story:** As a developer, I want the component to follow Joomla 6 MVC architecture and coding standards, so that it integrates correctly with the Joomla platform.

#### Acceptance Criteria

1. THE Component SHALL implement the Joomla 6 MVC (Model-View-Controller) architecture pattern with separate administrator and site sections
2. THE Component SHALL use Joomla 6 namespace conventions (Vendor\Component\ComponentName\) and PSR-4 autoloading standards with PHP 8.2+ strict typing
3. THE Component SHALL use Joomla's database abstraction layer for all database operations
4. THE Component SHALL use MariaDB as the database engine for all data storage
5. THE Component SHALL register menu items for both backend administration and frontend site views (Players, Teams, Schedules), and SHALL provide a corresponding site section to handle each registered frontend menu view
6. THE Component SHALL use Joomla's form validation and CSRF protection mechanisms for all form submissions
7. THE Component SHALL provide an installation manifest (XML) conforming to Joomla 6 extension packaging standards, including install and upgrade SQL schema files
8. THE Component SHALL register a service provider implementing Joomla's ComponentInterface for dependency injection and service registration

### Requirement 16: Access Control

**User Story:** As an administrator, I want the component to enforce access control, so that only authorized users can manage data.

#### Acceptance Criteria

1. THE Component SHALL restrict backend CRUD operations by enforcing the following Joomla ACL action mapping: core.create for creating records, core.edit for updating records, core.delete for deleting records, core.edit.state for changing published state, and core.manage for accessing the backend component interface; WHERE a user holds the core.admin permission for the component, THE Component SHALL grant that user automatic access to all operations without requiring individual permissions
2. IF an unauthorized user attempts to access a backend operation, THEN THE Component SHALL deny the request, redirect the user to the Joomla administrator dashboard, and display an access denied message indicating which permission is required
3. THE Component SHALL allow frontend views to be accessible without authentication (public access)
4. THE Component SHALL register its own asset rules with Joomla's ACL system so that permissions are configurable per component through Joomla's global configuration and component options permissions tab
5. WHERE a user holds the core.admin permission for the component, THE Component SHALL allow that user to configure ACL permissions for other user groups through the component's options interface

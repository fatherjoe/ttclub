# Implementation Plan: Table Tennis Club Manager (com_ttclub)

## Overview

This plan implements the `com_ttclub` Joomla 6 component following the MVC architecture. Tasks progress from project scaffold and database schema through core entity CRUD, import services, frontend views, ranking display, schedule service (live from click-tt.de with caching), and ACL enforcement. Property-based tests use `eris/eris` and unit tests use PHPUnit.

## Tasks

- [x] 1. Project scaffold and core infrastructure
  - [x] 1.1 Create component directory structure and installation manifest
    - Create the full directory structure as defined in the design (administrator/, site/, media/)
    - Create `ttclub.xml` installation manifest conforming to Joomla 6 extension packaging standards
    - Include install/uninstall SQL file references and namespace declarations
    - _Requirements: 15.7_

  - [x] 1.2 Create database schema SQL files
    - Create `administrator/sql/install.sql` with all tables:
      - `#__ttclub_players`, `#__ttclub_player_images`, `#__ttclub_teams`, `#__ttclub_team_photos`
      - `#__ttclub_leagues`, `#__ttclub_age_classes`
      - `#__ttclub_seasons` (start_year SMALLINT UNSIGNED, label VARCHAR(50) DEFAULT '', UNIQUE on start_year+label)
      - `#__ttclub_half_seasons` (season_id, half; NO start_date/end_date fields)
      - `#__ttclub_rosters` (includes position INT UNSIGNED NULL, ordering by position ASC NULLS LAST)
      - `#__ttclub_club_ids` (click_tt_club_id, label, ordering)
      - `#__ttclub_ranking_cache` (team_id, half_season_id, ranking_html, fetched_at)
      - `#__ttclub_schedule_cache` (team_id, half_season_id, schedule_data TEXT, fetched_at DATETIME)
      - `#__ttclub_import_logs`
    - Include all indexes, unique constraints, and foreign key relationships as defined in the design
    - Teams table includes `club_id_source` INT UNSIGNED NULL FK to club_ids.id
    - Create `administrator/sql/uninstall.sql` to drop all tables
    - _Requirements: 15.3, 15.4, 1.8_

  - [x] 1.3 Create service provider and extension class
    - Implement `administrator/services/provider.php` registering MVCFactory, ComponentDispatcherFactory, and ComponentInterface
    - Implement `TtclubComponent` extending MVCComponent with BootableExtensionInterface and RouterServiceInterface
    - _Requirements: 15.8, 15.1_

  - [x] 1.4 Create DisplayController classes for admin and site
    - Implement `Administrator\Controller\DisplayController` extending BaseController
    - Implement `Site\Controller\DisplayController` extending BaseController
    - _Requirements: 15.1_

  - [x] 1.5 Set up PHPUnit configuration and testing framework
    - Create `phpunit.xml` configuration with test groups (property, integration, historical-import, ranking)
    - Add `eris/eris` dependency for property-based testing
    - Create test directory structure matching component structure
    - _Requirements: 15.2_

- [x] 2. Implement Player management (backend)
  - [x] 2.1 Create PlayerTable class with validation
    - Implement `Administrator\Table\PlayerTable` extending Joomla Table
    - Implement `check()` method enforcing first_name and last_name 1–50 characters
    - Implement `delete()` override preventing deletion when roster entries exist
    - _Requirements: 7.1, 7.5, 7.6, 7.7_

  - [x] 2.2 Create Player model and list model
    - Implement `PlayerModel` extending AdminModel with `getForm()` and `save()` methods
    - Implement `PlayersModel` extending ListModel with `getListQuery()` including search/filter by last name
    - _Requirements: 7.1, 7.2, 7.3, 7.9_

  - [x] 2.3 Create Player controllers
    - Implement `PlayerController` extending FormController for single-record CRUD
    - Implement `PlayersController` extending AdminController for list operations (publish, unpublish, delete)
    - _Requirements: 7.1, 7.3, 7.4_

  - [x] 2.4 Create Player admin views and form XML
    - Create `administrator/forms/player.xml` with field definitions and validation rules
    - Implement `Players\HtmlView` for list view with toolbar and filters
    - Implement `Player\HtmlView` for edit form view
    - Create admin templates (tmpl/players/default.php, tmpl/player/edit.php)
    - _Requirements: 7.2, 7.5_

  - [x] 2.5 Implement player image upload with half-season association
    - Add image upload handling in PlayerModel save method
    - Create PlayerImageTable for the #__ttclub_player_images table
    - Validate JPEG/PNG format and max 2 MB size
    - Store images in media/com_ttclub/images/ with half-season association
    - _Requirements: 7.7, 7.8_

  - [x] 2.6 Write property test for player data round-trip (Property 1)
    - **Property 1: Player data round-trip**
    - Generate random strings 1–50 chars for first/last names, save and reload, verify identical values
    - **Validates: Requirements 7.1, 7.3**

  - [x] 2.7 Write property test for player name validation (Property 2)
    - **Property 2: Player name validation rejects invalid input**
    - Generate empty strings, whitespace-only strings, and strings >50 chars; verify rejection with field identification
    - **Validates: Requirements 7.5**

  - [x] 2.8 Write property test for player deletion guard (Property 3)
    - **Property 3: Player deletion is prevented iff roster assignments exist**
    - Generate random players with/without roster entries; verify deletion succeeds iff zero roster assignments
    - **Validates: Requirements 7.6, 7.7**

  - [x] 2.9 Write property test for player search (Property 4)
    - **Property 4: Player search returns correct subset**
    - Generate random player sets + search substrings; verify correct case-insensitive last name filtering
    - **Validates: Requirements 7.9**

- [x] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement Team management (backend)
  - [x] 4.1 Create TeamTable class with validation
    - Implement `Administrator\Table\TeamTable` extending Joomla Table
    - Implement `check()` method enforcing required fields (season_id, league_id, age_class_id, team_number)
    - Implement league immutability check preventing league_id changes on existing records
    - Implement `delete()` override preventing deletion when roster entries exist
    - _Requirements: 5.5, 5.6, 5.7, 5.8, 5.11_

  - [x] 4.2 Create Team model and list model
    - Implement `TeamModel` extending AdminModel with `getForm()` and `save()` enforcing league immutability
    - Implement `TeamsModel` extending ListModel with `getListQuery()` joining league, season, and club_id_source data
    - _Requirements: 5.1, 5.2, 5.3, 1.3_

  - [x] 4.3 Create Team controllers
    - Implement `TeamController` extending FormController for single-record CRUD
    - Implement `TeamsController` extending AdminController for list operations
    - _Requirements: 5.1, 5.3, 5.4_

  - [x] 4.4 Create Team admin views and form XML
    - Create `administrator/forms/team.xml` with field definitions (season, league, age class, team number)
    - Implement `Teams\HtmlView` and `Team\HtmlView`
    - Create admin templates for list and edit views
    - _Requirements: 5.2, 5.5_

  - [x] 4.5 Implement team photo upload with half-season association
    - Add photo upload handling in TeamModel save method
    - Create TeamPhotoTable for the #__ttclub_team_photos table
    - Validate JPEG/PNG format and max 5 MB size
    - _Requirements: 5.9, 5.10_

  - [x] 4.6 Write property test for team required field validation (Property 5)
    - **Property 5: Team required field validation**
    - Generate random combinations of missing/present required fields; verify rejection with field identification
    - **Validates: Requirements 5.5, 5.7, 5.11**

  - [x] 4.7 Write property test for team deletion guard (Property 6)
    - **Property 6: Team deletion is prevented iff roster assignments exist**
    - Generate teams with/without roster entries; verify deletion logic
    - **Validates: Requirements 5.6**

  - [x] 4.8 Write property test for league immutability (Property 7)
    - **Property 7: Team league immutability**
    - Generate existing teams with attempted league_id changes; verify rejection
    - **Validates: Requirements 5.8**

- [x] 5. Implement Age Class and League management (backend)
  - [x] 5.1 Create AgeclassTable, AgeclassModel, AgeclassesModel, and controllers
    - Implement table class with `delete()` override preventing deletion when teams reference the age class
    - Implement model/list model and singular/plural controllers
    - Create `administrator/forms/ageclass.xml` and views
    - _Requirements: 4.1, 4.2_

  - [x] 5.2 Create LeagueTable, LeagueModel, LeaguesModel, and controllers
    - Implement table class with unique name enforcement (case-insensitive) in `check()`
    - Implement `delete()` override preventing deletion when teams reference the league
    - Implement model/list model and singular/plural controllers
    - Create `administrator/forms/league.xml` and views
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_

  - [x] 5.3 Write property test for age class deletion guard (Property 8)
    - **Property 8: Age class deletion is prevented iff teams reference it**
    - Generate age classes with/without team references; verify deletion logic
    - **Validates: Requirements 4.2**

  - [x] 5.4 Write property test for league name uniqueness (Property 9)
    - **Property 9: League name uniqueness**
    - Generate random league names including duplicates; verify case-insensitive rejection
    - **Validates: Requirements 3.7**

  - [x] 5.5 Write property test for league deletion guard (Property 10)
    - **Property 10: League deletion is prevented iff teams reference it**
    - Generate leagues with/without team references; verify deletion logic
    - **Validates: Requirements 3.6**

- [x] 6. Implement Season management (backend)
  - [x] 6.1 Create HalfSeasonTable and SeasonTable with validation
    - Implement `SeasonTable` with `check()` enforcing start_year 1900–2100 and unique (start_year, label) constraint
    - Implement `HalfSeasonTable` for the half_seasons records (no start_date/end_date fields)
    - Implement `delete()` override preventing deletion when rosters or schedule cache entries reference the season
    - _Requirements: 2.1, 2.5, 2.6, 2.7, 2.10, 2.11, 2.12_

  - [x] 6.2 Create Season model with half-season management
    - Implement `SeasonModel` extending AdminModel; override `save()` to create/update both half-season records atomically
    - Season form accepts start_year (SMALLINT) and optional label (VARCHAR 50)
    - Display name derived algorithmically: `label ? label + ' ' + YYYY/YY : YYYY/YY`
    - Implement `SeasonsModel` extending ListModel showing derived display names
    - _Requirements: 2.1, 2.2, 2.3, 2.7, 2.10, 2.13, 2.14_

  - [x] 6.3 Create Season controllers, views, and form XML
    - Implement `SeasonController` and `SeasonsController`
    - Create `administrator/forms/season.xml` with fields for start_year and optional label
    - Implement admin views and templates showing derived display names
    - _Requirements: 2.1, 2.2, 2.5, 2.10_

  - [x] 6.4 Implement HalfSeasonResolver service
    - Create `Administrator\Service\HalfSeasonResolver` class
    - Logic: Month 8–12 → first half (start_year = current year); Month 1–7 → second half (start_year = previous year)
    - Fallback: if no matching season exists, use most recent season's latest half-season
    - Make available to both admin and site models via service provider
    - _Requirements: 2.8, 2.9_

  - [x] 6.5 Write property test for season structure invariant (Property 11)
    - **Property 11: Season structure invariant**
    - Generate random start_years; verify exactly two half-season records exist (half=1 and half=2) per season
    - **Validates: Requirements 2.7**

  - [x] 6.6 Write property test for season display name derivation (Property 12)
    - **Property 12: Season display name derivation**
    - Generate random start_years (1900–2100) + random labels; verify format matches `label ? label + ' ' + YYYY/YY : YYYY/YY`
    - **Validates: Requirements 2.2, 2.13**

  - [x] 6.7 Write property test for half-season resolution by calendar month (Property 13)
    - **Property 13: Half-season resolution by calendar month**
    - Generate random months (1–12) + random years; verify half=1 for months 8–12 with start_year=year, half=2 for months 1–7 with start_year=year-1
    - **Validates: Requirements 2.8, 11.7, 12.2**

  - [x] 6.8 Write property test for half-season resolution fallback (Property 14)
    - **Property 14: Half-season resolution fallback**
    - Generate random season sets missing target year; verify fallback to most recent season's latest half-season
    - **Validates: Requirements 2.9**

  - [x] 6.9 Write property test for season deletion guard (Property 15)
    - **Property 15: Season deletion is prevented iff associated data exists**
    - Generate seasons with/without roster or schedule cache references; verify deletion logic
    - **Validates: Requirements 2.6**

  - [x] 6.10 Write property test for season uniqueness on start_year and label (Property 16)
    - **Property 16: Season uniqueness on start_year and label**
    - Generate random (start_year, label) pairs; verify duplicate rejection and that different labels with same start_year are allowed
    - **Validates: Requirements 2.11, 2.12**

- [x] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Implement Roster management (backend)
  - [x] 8.1 Create RosterTable with duplicate rejection and position field
    - Implement `RosterTable` with `check()` method enforcing unique (player_id, team_id, half_season_id) constraint
    - Include optional `position` field (INT UNSIGNED NULL) for manual position entry
    - _Requirements: 6.1, 6.9_

  - [x] 8.2 Create RosterModel with assignment and copy logic
    - Implement `RosterModel` extending AdminModel with assign/remove methods
    - Support optional position number on roster entry creation/update
    - Implement roster display ordering: `ORDER BY position IS NULL, position ASC` (NULLS LAST)
    - Implement roster copy logic: duplicate all assignments to next half-season (second half of same season, or first half of next season)
    - Implement merge/replace confirmation handling for target half-seasons that already have assignments
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.7, 6.8_

  - [x] 8.3 Create RosterController and admin view
    - Implement `RosterController` handling assignment, removal, and copy operations
    - Create `administrator/forms/roster.xml` with position field and admin view showing players ordered by position ASC NULLS LAST
    - Create admin template for roster management interface
    - _Requirements: 6.1, 6.2, 6.3, 6.7, 6.8_

  - [x] 8.4 Write property test for roster duplicate rejection (Property 17)
    - **Property 17: Roster duplicate rejection**
    - Generate existing roster entries + duplicate attempts; verify rejection
    - **Validates: Requirements 6.9**

  - [x] 8.5 Write property test for roster position ordering (Property 18)
    - **Property 18: Roster position ordering**
    - Generate random position values (int/null); verify ascending order with NULL-position entries appearing last
    - **Validates: Requirements 6.3**

  - [x] 8.6 Write property test for roster copy (Property 19)
    - **Property 19: Roster copy produces identical assignments**
    - Generate random rosters, execute copy, verify target half-season has same player IDs
    - **Validates: Requirements 6.7**

- [x] 9. ~~Implement Schedule management (backend)~~ — SUPERSEDED
  - [x] 9.1 ~~Create ScheduleTable with validation~~ — Superseded: schedule data is now fetched live from click-tt.de via ScheduleService, no local `#__ttclub_schedules` table
    - _Previously: Requirements 6.5, 6.6 (old numbering) — these requirements no longer exist_

  - [x] 9.2 ~~Create Schedule model, list model, controllers, views, and form XML~~ — Superseded: replaced by ScheduleService with live fetch and caching
    - _Previously: Requirements 6.1–6.8 (old numbering) — these requirements no longer exist_

  - [x] 9.3 ~~Write property test for schedule ordering (Property 20 old)~~ — Superseded: Property 20 is now "Schedule cache validity" (see task 20.4)
    - _Previously: Requirements 6.2, 10.2, 15.5 (old numbering) — these requirements no longer exist or have been renumbered_

- [x] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Implement Import Service (click-tt.de)
  - [x] 11.1 Create ClickTtUrlBuilder and ClickTtParser classes
    - Implement `Administrator\Service\ClickTtUrlBuilder` constructing URLs with pattern: `https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/{action}?club={clubId}`
    - Methods: `clubPools(int $clubId)`, `clubPortraitTT(int $clubId)`, `clubTeams(int $clubId)`
    - Implement `Administrator\Service\ClickTtParser` with methods: `parseSeasonOverview`, `parseRoster`, `parseLeagueAssignments`, `parseTeams`, `parsePositionNotation`, `parseRankingTable`, `parseScheduleForTeam`, `isCupCompetition`
    - Position notation parsing: "X.Y" → team number X, position Y
    - _Requirements: 8.10, 8.14_

  - [x] 11.2 Create ImportService class
    - Implement `Administrator\Service\ImportService` with methods: `importRosters`, `importLeagueAssignments`, `importTeams`, `executeFullImport`, `validateClubConnection`
    - Use `ClickTtUrlBuilder` for URL construction and `ClickTtParser` for HTML parsing
    - Iterate over all configured club IDs from `#__ttclub_club_ids` table
    - Implement player matching by first_name + last_name combination (case-insensitive) across all club IDs
    - Set `club_id_source` on imported teams to track origin
    - Auto-create seasons and half-seasons during import if they do not exist (using start_year + label)
    - Create separate parallel seasons for cup competitions (identified by "Pokal" in championship name)
    - Does NOT create local schedule records — schedule data is fetched live via ScheduleService
    - _Requirements: 8.1, 8.2, 8.3, 8.6, 8.12, 8.13, 8.15, 8.16, 1.2, 1.3, 1.4, 2.16_

  - [x] 11.3 Create ImportModel and ImportController
    - Implement `ImportModel` with selective import support (players, rosters individually or combined)
    - Implement `ImportController` orchestrating the import workflow iterating over all configured club IDs
    - Handle conflict detection and administrator confirmation for updates
    - _Requirements: 8.7, 8.13, 1.2_

  - [x] 11.4 Create ImportLogTable and logging
    - Implement `ImportLogTable` for the #__ttclub_import_logs table
    - Log every import operation with timestamp, type, record counts, and status
    - _Requirements: 8.11_

  - [x] 11.5 Create Import admin view and configuration
    - Create `administrator/forms/import.xml` with club ID list display and import trigger
    - Implement `Import\HtmlView` with import trigger interface and summary display
    - Show warning if no club IDs are configured
    - Add component configuration parameters (federation abbreviation, ranking_cache_duration)
    - _Requirements: 8.7, 8.8, 8.9, 1.6_

  - [x] 11.6 Write property test for import player matching (Property 26)
    - **Property 26: Import player matching by name**
    - Generate random imported names with partial matches to existing players; verify update-not-duplicate behavior across all configured club IDs
    - **Validates: Requirements 8.12, 1.4**

  - [x] 11.7 Write property test for import URL construction (Property 27)
    - **Property 27: Import URL construction**
    - Generate random federation strings + club IDs + action names; verify URL matches pattern `https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/{action}?club={clubId}`
    - **Validates: Requirements 8.10**

  - [x] 11.8 Write property test for import position notation parsing (Property 28)
    - **Property 28: Import position notation parsing**
    - Generate random "X.Y" strings with valid positive integers; verify extraction of team number X and position Y
    - **Validates: Requirements 8.14**

  - [x] 11.9 Write property test for import auto-creates missing seasons (Property 29)
    - **Property 29: Import auto-creates missing seasons**
    - Generate random discovered start_years + existing season sets; verify only new (start_year, label) combos created, existing not duplicated
    - **Validates: Requirements 8.15**

  - [x] 11.10 Write property test for import audit logging (Property 30)
    - **Property 30: Import audit logging**
    - Generate random import operations (success and failure); verify log record creation with timestamp, type, and counts
    - **Validates: Requirements 8.11**

  - [x] 11.11 Write property test for import summary arithmetic (Property 31)
    - **Property 31: Import summary arithmetic**
    - Generate random import counts; verify created + updated + unchanged = total processed
    - **Validates: Requirements 8.7**

  - [x] 11.12 Write property test for import team club_id_source association (Property 32)
    - **Property 32: Import team club_id_source association**
    - Generate random teams from different configured club IDs; verify each team's club_id_source references the correct club ID entry
    - **Validates: Requirements 1.3**

- [x] 12. Implement Historical Import Service
  - [x] 12.1 Create SeasonParserInterface and parser implementations
    - Implement `SeasonParserInterface` with methods: parseSeasonArchive, parseTeams, parseRoster, parseSchedule
    - Implement `MyTischtennisParser` for mytischtennis.de HTML structure
    - Implement `ClickTtParser` for click-tt.de HTML structure (extends existing ClickTtParser)
    - _Requirements: 9.1, 9.3, 9.4, 9.5, 9.12_

  - [x] 12.2 Create HistoricalImportService class
    - Implement `HistoricalImportService` with discoverSeasons, executeFullImport, hasExistingData, importSeason methods
    - Implement season deduplication using (start_year, label) combination
    - Implement player match-or-create logic using first_name + last_name
    - Implement per-season commit and logging
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7, 9.11_

  - [x] 12.3 Create HistoricalImportController and admin view
    - Implement `HistoricalImportController` with existing-data warning gate, data source selection, and confirmation flow
    - Create admin view/template for historical import UI with data source selector and trigger button
    - Display summary after successful import
    - _Requirements: 9.7, 9.8, 9.9, 9.12_

  - [x] 12.4 Create supporting DTOs (DiscoveredSeason, SeasonImportResult, HistoricalImportResult)
    - Implement readonly data transfer objects as defined in the design
    - DiscoveredSeason includes startYear, label, archiveUrl, dataSource
    - _Requirements: 9.7_

  - [x] 12.5 Write property test for season discovery completeness (Property 35)
    - **Property 35: Season discovery completeness**
    - Generate random HTML with varying season link counts; verify complete extraction with no omissions or fabrications
    - **Validates: Requirements 9.1**

  - [x] 12.6 Write property test for season deduplication (Property 36)
    - **Property 36: Season deduplication on historical import**
    - Generate random discovered + existing (start_year, label) pairs; verify only new ones created
    - **Validates: Requirements 9.2**

  - [x] 12.7 Write property test for team data extraction (Property 37)
    - **Property 37: Team data extraction completeness**
    - Generate random HTML team listings; verify team count + field extraction accuracy
    - **Validates: Requirements 9.3**

  - [x] 12.8 Write property test for roster entry creation (Property 38)
    - **Property 38: Roster entry creation from scraped data**
    - Generate random player-team-halfseason combos; verify exactly one roster entry per combination
    - **Validates: Requirements 9.4**

  - [x] 12.9 Write property test for schedule entry completeness (Property 39)
    - **Property 39: Schedule entry completeness from scraped data**
    - Generate random schedule HTML; verify required fields non-null + count matches source
    - **Validates: Requirements 9.5**

  - [x] 12.10 Write property test for player match-or-create (Property 40)
    - **Property 40: Historical import player match-or-create**
    - Generate random imported + existing player sets; verify dedup logic and final count
    - **Validates: Requirements 9.6, 9.11**

  - [x] 12.11 Write property test for summary accuracy (Property 41)
    - **Property 41: Historical import summary accuracy**
    - Generate random import operations; verify reported counts match actual DB inserts
    - **Validates: Requirements 9.7**

  - [x] 12.12 Write property test for per-season audit logging (Property 42)
    - **Property 42: Historical import per-season audit logging**
    - Generate random season counts; verify N log entries created with correct content
    - **Validates: Requirements 9.10**

- [x] 13. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. Implement Frontend views
  - [x] 14.1 Create site Player models and views
    - Implement `Site\Model\PlayersModel` with visibility filtering and alphabetical ordering by last name
    - Implement `Site\Model\PlayerModel` for detail view with visibility enforcement
    - Implement `Site\View\Players\HtmlView` and `Site\View\Player\HtmlView`
    - Create site templates for player grid (overview) and player detail
    - Display player image for current half-season (via HalfSeasonResolver); show placeholder if none exists
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

  - [x] 14.2 Create site Team models and views
    - Implement `Site\Model\TeamsModel` with current half-season resolution (via HalfSeasonResolver) and team_number ordering
    - Implement `Site\Model\TeamModel` with roster loading for selected half-season, ordered by position ASC NULLS LAST
    - Implement `Site\View\Teams\HtmlView` and `Site\View\Team\HtmlView`
    - Create site templates for teams overview and team detail (roster display)
    - Support half-season switching and season navigation (including parallel seasons with labels)
    - Display team photo for selected half-season; show placeholder if none exists
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8, 2.15_

  - [x] 14.3 ~~Create site Schedule model and view from local DB~~ — Superseded: replaced by ScheduleService integration in team detail page (see task 20.2)
    - _Previously referenced old Requirements 10.1–10.7 which no longer exist. Schedule is now fetched live via ScheduleService (Req 14)_

  - [x] 14.4 Write property test for frontend team ordering (Property 23)
    - **Property 23: Frontend team ordering**
    - Generate random team sets; verify team_number ascending order
    - **Validates: Requirements 12.1**

  - [x] 14.5 Write property test for frontend player visibility (Property 24)
    - **Property 24: Frontend player visibility filtering**
    - Generate random fields + visibility configs; verify only visible fields in output
    - **Validates: Requirements 11.3**

  - [x] 14.6 Write property test for frontend player ordering (Property 25)
    - **Property 25: Frontend player ordering**
    - Generate random player sets; verify alphabetical order by last name
    - **Validates: Requirements 11.1**

- [x] 15. Implement ACL and menu registration
  - [x] 15.1 Implement ACL enforcement in controllers
    - Add permission checks in all admin controllers: core.create, core.edit, core.delete, core.edit.state, core.manage
    - Implement core.admin bypass granting full access
    - Add access denied redirect with descriptive message for unauthorized users
    - Register component ACL rules in access.xml
    - _Requirements: 16.1, 16.2, 16.4, 16.5_

  - [x] 15.2 Register menu items for backend and frontend
    - Register backend admin menu items for Players, Teams, Leagues, Seasons, Age Classes, Import
    - Register frontend menu item types for Players, Teams views
    - Ensure frontend views are publicly accessible without authentication
    - _Requirements: 15.5, 16.3_

  - [x] 15.3 Write property test for ACL enforcement (Property 34)
    - **Property 34: ACL enforcement**
    - Generate random operations + permission sets; verify access granted/denied correctly including core.admin bypass
    - **Validates: Requirements 16.1, 16.2**

- [x] 16. Implement media assets and component configuration
  - [x] 16.1 Create CSS, JS assets and placeholder image
    - Create `media/com_ttclub/css/` with base component styles
    - Create `media/com_ttclub/js/` with any required client-side scripts
    - Create `media/com_ttclub/images/placeholder.png` default image
    - Add component configuration fields (federation abbreviation, ranking_cache_duration, player_visible_fields, default_placeholder_image)
    - Club IDs stored in `#__ttclub_club_ids` table (not component params)
    - _Requirements: 11.5, 12.7, 15.7, 13.8, 1.8_

- [x] 17. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 18. Implement Multiple Club IDs management
  - [x] 18.1 Create ClubIdTable, ClubIdModel, and admin CRUD interface
    - Add `#__ttclub_club_ids` table to install.sql with columns: id, click_tt_club_id (INT UNSIGNED NOT NULL), legacy_club_id (INT UNSIGNED NULL), club_name (VARCHAR 200 NOT NULL DEFAULT ''), federation (VARCHAR 20 NOT NULL DEFAULT ''), label (VARCHAR 100 NOT NULL), ordering (INT UNSIGNED NOT NULL DEFAULT 0)
    - Implement `Administrator\Table\ClubIdTable` for the `#__ttclub_club_ids` table with validation (click_tt_club_id required)
    - Implement `ClubIdModel` and `ClubIdsModel` with CRUD operations for club ID entries
    - Create `administrator/forms/clubid.xml` form definition with fields: click_tt_club_id, legacy_club_id, club_name, federation, label, ordering
    - Create `ClubIdController` and `ClubIdsController` for singular/list operations
    - Create admin views/templates: list view showing all club entries, edit form for setting/updating club information
    - _Requirements: 1.1, 1.5, 1.7, 1.8, 1.9, 1.10_

  - [x] 18.2 Integrate club ID iteration into ImportService
    - Update `ImportService.executeFullImport()` to load all club IDs from `#__ttclub_club_ids` and iterate
    - For each club ID entry: use the entry's `federation` field when constructing URLs via `ClickTtUrlBuilder` (rather than a global federation setting)
    - For each club ID: fetch clubPools, clubPortraitTT, clubTeams; set `club_id_source` on created teams
    - Merge players across club IDs using first_name + last_name (case-insensitive) — no duplicates
    - Log per-club-ID import results
    - _Requirements: 1.2, 1.3, 1.4, 1.9_

  - [x] 18.3 Display club ID source on team admin list
    - Show `club_id_source` label in Teams admin list view for imported teams
    - Show "Manual" or empty for teams without club_id_source
    - _Requirements: 1.3_

- [x] 19. Implement League Ranking Table Display
  - [x] 19.1 Create RankingService and RankingCacheTable
    - Implement `Administrator\Service\RankingService` with methods: `getRanking`, `isCacheValid`, `invalidateCache`
    - Implement `Administrator\Table\RankingCacheTable` for the `#__ttclub_ranking_cache` table
    - Cache validity check: `fetched_at + cache_duration > NOW()` → serve cached; otherwise re-fetch
    - Use `ClickTtUrlBuilder` and `ClickTtParser.parseRankingTable()` for fetching/parsing
    - _Requirements: 13.1, 13.5, 13.6_

  - [x] 19.2 Integrate ranking display into frontend Team detail view
    - Update `Site\Model\TeamModel` to call `RankingService.getRanking()` for the team's league and half-season
    - Render ranking table as HTML in team detail template with columns: position, team name, matches, wins, draws, losses, points
    - Highlight club's own team row in the ranking table
    - Graceful degradation: if fetch fails, show "ranking temporarily unavailable" message without breaking page
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.7_

  - [x] 19.3 Add ranking cache duration configuration
    - Add `ranking_cache_duration` parameter to component configuration (default: 3600 seconds / 1 hour)
    - Wire configuration value into `RankingService` constructor
    - _Requirements: 13.5, 13.8_

  - [x] 19.4 Write property test for ranking cache validity (Property 33)
    - **Property 33: Ranking cache validity**
    - Generate random timestamps + TTL values; verify served/expired decision matches `fetched_at + cache_duration > current_time`
    - **Validates: Requirements 13.5, 13.6**

- [x] 20. Implement Team Page Match Schedule (ScheduleService — live fetch from click-tt.de)
  - [x] 20.1 Create ScheduleService and ScheduleCacheTable
    - Implement `Administrator\Service\ScheduleService` with methods: `getSchedule`, `isCacheValid`, `invalidateScheduleCache`
    - Implement `Administrator\Table\ScheduleCacheTable` for the `#__ttclub_schedule_cache` table (team_id, half_season_id, schedule_data TEXT, fetched_at DATETIME)
    - Cache validity check: `fetched_at + cache_duration > NOW()` → serve cached; otherwise re-fetch from click-tt.de
    - Fetch logic: POST to `clubMeetings` endpoint with the team's club_id and season date range, filter results by team name using `ClickTtParser.parseScheduleForTeam()`
    - Default cache duration: 3 days (259200 seconds)
    - _Requirements: 14.1, 14.2, 14.8, 14.9_

  - [x] 20.2 Integrate schedule display into frontend Team detail view
    - Update `Site\Model\TeamModel` to call `ScheduleService.getSchedule()` for the team's current/selected half-season
    - Render schedule as HTML table on team detail page with columns: date, time, home team, guest team, result
    - Past matches with results show the score; future matches leave result column empty
    - Order by match_date ascending
    - Graceful degradation: if fetch fails, show "schedule temporarily unavailable" message without breaking page
    - Show "no schedule data available" message if no entries exist
    - _Requirements: 14.1, 14.3, 14.5, 14.6, 14.7_

  - [x] 20.3 Add schedule cache duration configuration
    - Add `schedule_cache_duration` parameter to component configuration (default: 259200 seconds / 3 days)
    - Wire configuration value into `ScheduleService` constructor
    - _Requirements: 14.8_

  - [x] 20.4 Write property test for schedule cache validity (Property 20)
    - **Property 20: Schedule cache validity**
    - Generate random timestamps + TTL values; verify served/expired decision matches `fetched_at + cache_duration > current_time`
    - **Validates: Requirements 14.8, 14.9**

  - [x] 20.5 Write property test for schedule fetch graceful degradation (Property 21)
    - **Property 21: Schedule fetch graceful degradation**
    - Simulate fetch failures (connection error, invalid response); verify team detail page still renders (photo, roster, ranking) and shows informational "schedule temporarily unavailable" message
    - **Validates: Requirements 14.7**

  - [x] 20.6 Write property test for schedule entry ordering (Property 22)
    - **Property 22: Schedule entry ordering**
    - Generate random schedule entry sets with various dates; verify ascending order by match date in displayed output
    - **Validates: Requirements 14.6**

- [x] 21. Implement Parallel Seasons support
  - [x] 21.1 Update season form and validation for optional label
    - Ensure season form XML includes optional label field (VARCHAR 50, default empty string)
    - Update `SeasonTable.check()` to enforce unique (start_year, label) constraint
    - Update season display in admin list to show derived name including label
    - _Requirements: 2.10, 2.11, 2.12, 2.13_

  - [x] 21.2 Update frontend season navigation for parallel seasons
    - Update frontend season selectors to display both main and parallel seasons with their labels
    - Allow visitors to navigate between league and cup/Pokal seasons
    - _Requirements: 2.14, 2.15_

  - [x] 21.3 Update import to create parallel seasons for cup competitions
    - Update `ClickTtParser.isCupCompetition()` to detect "Pokal" in championship names
    - Update `ImportService` to create seasons with label "Pokal" when cup competition detected
    - Ensure main season and Pokal season with same start_year coexist correctly
    - _Requirements: 2.16_

- [x] 22. Implement Import Parallel Seasons via URL
  - [x] 22.1 Create parallel season import UI and controller logic
    - Add input field on import page for pasting a click-tt.de URL
    - Implement URL parsing via `ClickTtParser.parseClickTtUrl()` to extract federation, club ID, and season/competition identifiers
    - Implement `ImportService.importFromUrl()` to import teams and rosters from the linked page
    - Derive season label from championship name in source data
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8_

- [x] 23. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties using `eris/eris`
- Unit tests validate specific examples and edge cases
- All PHP files use strict typing (`declare(strict_types=1)`) and PHP 8.4+ features
- Database operations use Joomla's database abstraction layer exclusively
- All form submissions include CSRF token validation
- Half-season resolution uses calendar-month logic via `HalfSeasonResolver` (no date fields on half_seasons)
- Import uses `ClickTtUrlBuilder` + `ClickTtParser` for click-tt.de endpoints (clubPools, clubPortraitTT, clubTeams)
- Multiple club IDs supported via `#__ttclub_club_ids` table; import iterates over all configured IDs
- Seasons identified by start_year + optional label; display name derived algorithmically
- Roster entries include optional position field with ordering: `ORDER BY position IS NULL, position ASC`
- Schedule data is fetched LIVE from click-tt.de via `ScheduleService` with caching in `#__ttclub_schedule_cache` (default 3 days) — NO local `#__ttclub_schedules` table
- Section 9 tasks are marked as superseded: local schedule CRUD has been replaced by live fetching via ScheduleService

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.5"] },
    { "id": 1, "tasks": ["1.2", "1.3"] },
    { "id": 2, "tasks": ["1.4", "5.1", "5.2"] },
    { "id": 3, "tasks": ["2.1", "4.1", "6.1"] },
    { "id": 4, "tasks": ["2.2", "4.2", "5.3", "5.4", "5.5", "6.2"] },
    { "id": 5, "tasks": ["2.3", "2.4", "2.5", "4.3", "4.4", "4.5", "6.3", "6.4"] },
    { "id": 6, "tasks": ["2.6", "2.7", "2.8", "2.9", "4.6", "4.7", "4.8", "6.5", "6.6", "6.7", "6.8", "6.9", "6.10"] },
    { "id": 7, "tasks": ["8.1"] },
    { "id": 8, "tasks": ["8.2", "8.3"] },
    { "id": 9, "tasks": ["8.4", "8.5", "8.6"] },
    { "id": 10, "tasks": ["11.1", "11.4"] },
    { "id": 11, "tasks": ["11.2", "11.3", "11.5"] },
    { "id": 12, "tasks": ["11.6", "11.7", "11.8", "11.9", "11.10", "11.11", "11.12", "12.1", "12.4"] },
    { "id": 13, "tasks": ["12.2"] },
    { "id": 14, "tasks": ["12.3", "12.5", "12.6", "12.7", "12.8", "12.9", "12.10", "12.11", "12.12"] },
    { "id": 15, "tasks": ["14.1", "14.2"] },
    { "id": 16, "tasks": ["14.4", "14.5", "14.6", "15.1", "15.2"] },
    { "id": 17, "tasks": ["15.3", "16.1"] },
    { "id": 18, "tasks": ["18.1", "19.1"] },
    { "id": 19, "tasks": ["18.2", "18.3", "19.2", "19.3"] },
    { "id": 20, "tasks": ["19.4", "21.1", "20.1"] },
    { "id": 21, "tasks": ["21.2", "21.3", "20.2", "20.3"] },
    { "id": 22, "tasks": ["20.4", "20.5", "20.6", "22.1"] }
  ]
}
```

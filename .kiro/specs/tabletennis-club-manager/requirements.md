# Requirements Document

## Introduction

The Table Tennis Club Manager is a Joomla 6 component that enables table tennis clubs to manage and display their players, teams, and team schedules. The component provides a backend administration interface for CRUD operations and a frontend site view for public display. A key concept is that team rosters are managed per half-season, allowing player assignments to change between the first and second halves of a season.

## Glossary

- **Component**: A Joomla 6 extension following MVC architecture that provides both backend (administrator) and frontend (site) functionality
- **Player**: A registered member of the table tennis club who can be assigned to teams
- **Team**: A named group of players competing together in a specific season half. Teams are identified by an administrator-assigned team number.
- **Age_Class**: A category that defines the maximum age of players eligible for a team (e.g., "Herren" for open/adult, "Jugend U19" for players no older than 19, "Jugend U15" for players no older than 15)
- **Season**: A competitive period consisting of exactly two halves (first half and second half). The second half season is executed in the year following on the first half season. Seasons are named after their years like 2025/26 when first half season is in 2025 and second half in 2026.
- **Half_Season**: One of two periods within a season (first half or second half) during which team rosters remain fixed
- **Roster**: The set of players assigned to a specific team for a specific half-season. A player can be assigned to multiple rosters per half season.
- **Schedule**: A collection of matches or fixtures assigned to a team within a season
- **League**: A competitive division or tier in which a team plays during a season (e.g., "Kreisliga", "Bezirksliga"); a team's league assignment is fixed for the entire season
- **mytischtennis.de**: An external website that publishes official table tennis data including player information, team compositions, and match schedules; used as a data source for import via web scraping
- **Backend**: The Joomla administrator area accessible only to authorized administrators
- **Frontend**: The Joomla site area accessible to public visitors
- **Administrator**: A Joomla user with permissions to manage the component data in the backend

## Requirements

### Requirement 1: Manage Players

**User Story:** As an administrator, I want to create, read, update, and delete player records, so that I can maintain an accurate list of club members.

#### Acceptance Criteria

1. WHEN an administrator submits a new player form with valid data (first name 1–50 characters, last name 1–50 characters), THE Component SHALL create a new player record and store it in the database
2. WHEN an administrator requests the player list, THE Component SHALL display all player records showing at minimum first name, last name, and current roster assignment status
3. WHEN an administrator submits an updated player form with valid data, THE Component SHALL update the corresponding player record in the database
4. WHEN an administrator requests deletion of a player record, THE Component SHALL remove the player record from the database
5. IF a player form is submitted with a missing or empty first name or last name, THEN THE Component SHALL display a validation error message indicating which required fields are missing
6. IF an administrator requests deletion of a player who is assigned to a roster, THEN THE Component SHALL prevent deletion and display a message indicating the player is still assigned to a team; the deletion prevention SHALL apply regardless of whether the message is successfully displayed
7. THE Component SHALL only display the roster assignment prevention message when the player is actually assigned to a roster at the time of the deletion attempt
7. WHEN an administrator uploads a player image (JPEG or PNG, max 2 MB) for a specific half-season, THE Component SHALL store the image and associate it with the player and the selected half-season
8. THE Component SHALL allow a different player image per half-season
9. THE Component SHALL support searching and filtering the player list by last name

### Requirement 2: Manage Teams

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
12. THE Component SHALL allow the administrator to manage age classes (create, update, delete)
13. IF an administrator requests deletion of an age class that is assigned to one or more teams, THEN THE Component SHALL prevent deletion and display a message indicating the age class is still in use

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

### Requirement 4: Manage Seasons

**User Story:** As an administrator, I want to create and manage seasons, so that I can organize team competition into defined time periods.

#### Acceptance Criteria

1. WHEN an administrator submits a new season form with valid data, THE Component SHALL create a new season record consisting of a season name in the format "YYYY/YY" (where YY is the year following YYYY), a start date and end date for the first half-season, and a start date and end date for the second half-season
2. WHEN an administrator requests the season list, THE Component SHALL display all season records with their name, first half-season date range, and second half-season date range
3. WHEN an administrator submits an updated season form with valid data, THE Component SHALL update the corresponding season record in the database
4. WHEN an administrator requests deletion of a season record, THE Component SHALL remove the season record and its associated half-season records from the database
5. IF a season form is submitted with missing required fields (season name, first half start date, first half end date, second half start date, second half end date), THEN THE Component SHALL display a validation error message indicating the missing fields
6. IF an administrator requests deletion of a season that has roster assignments or schedule entries, THEN THE Component SHALL prevent deletion and display a message indicating the season has associated data
7. THE Component SHALL enforce that each season consists of exactly two half-seasons (first half and second half)
8. IF a season form is submitted with a first half-season end date that is on or after the second half-season start date, THEN THE Component SHALL display a validation error message indicating that the first half must end before the second half begins
9. THE Component SHALL determine the current half-season as the half-season whose start date and end date encompass today's date, and make this available for frontend display purposes
10. IF no half-season date range encompasses today's date, THEN THE Component SHALL treat the most recently ended half-season as the current half-season

### Requirement 5: Assign Players to Teams per Half-Season

**User Story:** As an administrator, I want to assign players to teams for each half-season independently, so that team rosters can change between the first and second halves of a season.

#### Acceptance Criteria

1. WHEN an administrator assigns a player to a team for a specific half-season, THE Component SHALL create a roster entry linking the player, team, and half-season
2. WHEN an administrator removes a player from a team roster for a specific half-season, THE Component SHALL delete the corresponding roster entry
3. WHEN an administrator views a team roster, THE Component SHALL display the players assigned to that team for the selected half-season
4. THE Component SHALL allow a player to be assigned to different teams in the first half and second half of the same season
5. THE Component SHALL allow a player to be assigned to multiple teams within the same half-season
6. THE Component SHALL allow multiple teams to exist within the same season
7. WHEN an administrator requests to copy a roster to the next half-season, THE Component SHALL duplicate all player assignments from the selected team and half-season to the subsequent half-season (second half of the same season, or first half of the next season if copying from the second half)
8. IF the target half-season of a roster copy already has player assignments for that team, THEN THE Component SHALL prompt the administrator to confirm whether to merge with or replace the existing assignments
9. IF an administrator attempts to assign the same player to the same team and half-season that already has an existing assignment, THEN THE Component SHALL reject the duplicate and display a message indicating the player is already assigned to that team for the selected half-season; duplicate rejection messages SHALL also be displayed during any system operation (including roster loading or validation checks) that detects a duplicate assignment state

### Requirement 6: Manage Team Schedules

**User Story:** As an administrator, I want to manage match schedules for teams, so that players and visitors can see upcoming and past fixtures.

#### Acceptance Criteria

1. WHEN an administrator submits a new schedule entry with valid data, THE Component SHALL create a schedule record associated with a team and season
2. WHEN an administrator requests the schedule list for a team, THE Component SHALL display all schedule entries for that team ordered by date in ascending order
3. WHEN an administrator submits an updated schedule entry with valid data, THE Component SHALL update the corresponding schedule record in the database
4. WHEN an administrator requests deletion of a schedule entry, THE Component SHALL remove the schedule record from the database
5. IF a schedule form is submitted with missing required fields, THEN THE Component SHALL display a validation error message indicating the missing fields
6. THE Component SHALL store for each schedule entry at minimum: date, time, opponent name, venue, home/away indicator, and the associated team and season
7. THE Component SHALL allow the administrator to optionally record a match result (score) for a schedule entry after its initial creation or at any later time
8. THE Component SHALL allow match results to be updated at any time after the schedule entry is created

### Requirement 7: Import Data from mytischtennis.de

**User Story:** As an administrator, I want to import player, team, and schedule data from mytischtennis.de, so that I can populate and update the component data without manual entry.

#### Acceptance Criteria

1. WHEN an administrator triggers a data import and the import succeeds, THE Component SHALL scrape player data from mytischtennis.de and create or update corresponding player records
2. WHEN an administrator triggers a data import, THE Component SHALL scrape team composition data from mytischtennis.de and create or update corresponding roster entries independently of the overall import status; a roster import SHALL be considered successful if its own scraping and update operations complete without error
3. WHEN an administrator triggers a data import and the import succeeds, THE Component SHALL scrape match schedule data from mytischtennis.de and create or update corresponding schedule entries
4. WHEN the import process completes successfully, THE Component SHALL display a summary indicating how many records were created, updated, or unchanged
5. IF the import encounters a connection error or invalid response from mytischtennis.de, THEN THE Component SHALL display an error message and leave existing data unchanged
6. IF imported data conflicts with existing records, THEN THE Component SHALL allow the administrator to review and confirm updates before overwriting; confirmed updates SHALL only be applied when the import operation completes successfully
7. THE Component SHALL provide a dedicated backend configuration page where the administrator can connect the component to a specific club on mytischtennis.de by entering the club identifier or URL
8. THE Component SHALL validate the club connection by verifying that the configured club identifier returns valid data from mytischtennis.de
9. THE Component SHALL log all import operations with timestamps for audit purposes
10. WHEN an administrator triggers an import and selects which data types to import (players, rosters, schedules) individually or in combination, THE Component SHALL require successful import of all selected data types for the operation to be considered complete
11. THE Component SHALL match imported player records to existing records using the player's first name and last name combination as the unique identifier

### Requirement 8: Frontend Player Display

**User Story:** As a site visitor, I want to view the list of club players, so that I can see who is a member of the club.

#### Acceptance Criteria

1. WHEN a visitor accesses the players overview page, THE Component SHALL display a grid of all players who have at least one publicly visible attribute, showing each player's image for the current half-season and their name, ordered alphabetically by last name
2. WHEN a visitor selects a specific player from the overview, THE Component SHALL display the player detail view showing all player fields that the administrator has designated as publicly visible; IF the detail view fails to load due to a technical error, THEN THE Component SHALL display an error message indicating the player details could not be retrieved
3. THE Component SHALL only display player information designated as publicly visible by the administrator
4. THE Component SHALL display the player image corresponding to the current half-season on the overview page and the selected half-season on the detail view
5. IF no image exists for the selected half-season, THEN THE Component SHALL display a default placeholder image
6. IF no players with publicly visible information exist, THEN THE Component SHALL display an informational message indicating that no player data is available
7. IF no current half-season can be determined from the configured seasons, THEN THE Component SHALL display players using the most recent half-season that has ended

### Requirement 9: Frontend Team Display

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

### Requirement 10: Frontend Schedule Display

**User Story:** As a site visitor, I want to view team schedules, so that I can see upcoming matches and past results.

#### Acceptance Criteria

1. WHEN a visitor accesses the schedule page for a team, THE Component SHALL display the schedule entries for that team in the current or selected season
2. THE Component SHALL display schedule entries ordered by date in ascending order
3. THE Component SHALL group schedule entries into two sections: upcoming matches (date is today or in the future) displayed first, followed by past matches (date is before today) with the most recent past match displayed first within the past section
4. THE Component SHALL display for each schedule entry: date, time, opponent name, venue, home/away indicator, and match result (if available)
5. IF a past match has a recorded result, THEN THE Component SHALL display the score alongside the match entry
6. THE Component SHALL allow visitors to select a different season to view historical schedules
7. IF no schedule entries exist for the selected team and season, THEN THE Component SHALL display an informational message indicating that no schedule data is available

### Requirement 11: Joomla 6 Architecture Compliance

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

### Requirement 12: Access Control

**User Story:** As an administrator, I want the component to enforce access control, so that only authorized users can manage data.

#### Acceptance Criteria

1. THE Component SHALL restrict backend CRUD operations by enforcing the following Joomla ACL action mapping: core.create for creating records, core.edit for updating records, core.delete for deleting records, core.edit.state for changing published state, and core.manage for accessing the backend component interface; WHERE a user holds the core.admin permission for the component, THE Component SHALL grant that user automatic access to all operations without requiring individual permissions
2. IF an unauthorized user attempts to access a backend operation, THEN THE Component SHALL deny the request, redirect the user to the Joomla administrator dashboard, and display an access denied message indicating which permission is required
3. THE Component SHALL allow frontend views to be accessible without authentication (public access)
4. THE Component SHALL register its own asset rules with Joomla's ACL system so that permissions are configurable per component through Joomla's global configuration and component options permissions tab
5. WHERE a user holds the core.admin permission for the component, THE Component SHALL allow that user to configure ACL permissions for other user groups through the component's options interface

### Requirement 13: First-Time Historical Data Import

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

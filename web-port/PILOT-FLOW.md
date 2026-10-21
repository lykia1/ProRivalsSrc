# Character → nation → city → maps

`src/PilotFlow.php` is a PDO SQLite service for a PHP 8.1+ application. Apply
`migrations/001_pilots.sql` once before serving requests. The database file must
be outside the public web directory. Use a verified server-side account/session
ID as the first argument; never accept account identity from a form.

The service adds pilot registration, four gear choices, one permanent nation per
account, BCU → Bygeniou (2001), ANI → Arlington (2002), nation city rosters,
server-validated teleportation and sequential mission progression. Each command
requires the last returned revision. Conditional updates prevent double awards
and racing nation choices. A database trigger also prevents replacing or clearing
an already selected nation. Callsigns and pilot IDs are unique. No delete/reset
or nation-change operation is exposed.

Integration: after authentication call `register($accountId, $callsign)` once;
then `command($accountId, $revision, 'gear', 'B-Gear')`, followed by `nation` with
`ANI` or `BCU`. Subsequent actions are `briefing`, `warp` with an integer map ID,
`start`, and `complete`. Protect your HTTP adapter with session authentication and
CSRF validation. The module is a backend integration component, not a standalone
password registration website. The existing stat calculator remains available.

`data/maps.json` contains 76 unique map display nodes extracted from
`Client/InterfaceLib/main/INFWorldMap.cpp::LoadMapInfo` at source commit
`be902d46388463a1533f9385d451a2d98604e457`. Coordinates are original world-map UI
coordinates, not terrain or teleport connections. Original Korean labels are
preserved. Duplicate 3060 display entries are deduplicated. English names for
some maps are readable labels; unverified localizations retain source labels.

The 30-second reconnaissance missions and unlock ordering are **adaptation
rules**, not original AirRivals quests. Original map terrain, NPCs, combat,
`QuestIndexForWarp` values and complete quest chains still require porting. The
original source places nation selection at quest 112; this adaptation deliberately
moves it directly after character creation as requested.

Tests: `php web-port/tests/pilot-flow.php` (PDO SQLite required). The hosted Sites
preview uses equivalent TypeScript/D1 rules and native ChatGPT identity; it is
not a PHP runtime. The preview keeps its existing private audience, so other
players cannot join until the owner explicitly changes access.

## Reynard strategic point

Apply `migrations/002_strategic_points.sql` after the pilot migration. The new
`StrategicPoint` PHP service provides `state($trustedAccountId)` and
`order($trustedAccountId, $eventId)`. The optional clock argument is for tests;
HTTP adapters must use server time, verified session identity and CSRF protection.
`state` materializes the current scheduled event on first access; no cron is
needed. Event results derive from persisted HP and deadline even when offline.

Every 15 minutes a new 10-minute event begins with 1,000 HP. ANI attacks for up
to 100 damage; BCU repairs up to 60 HP. There is a server-enforced 15-second
per-pilot cooldown. Players must have unlocked Reynard through quests, be on
map 3018 and have no active reconnaissance mission. Orders and HP effects commit
in one transaction. ANI wins on destruction; BCU wins on expiry otherwise.
Destroyed/expired targets cannot receive commands. This is a configurable-game
prototype scenario: BCU is defender here, not a claim about original map ownership.
No currency rewards, character PvP, or permanent territory transfer are included.
Run `php web-port/tests/strategic-point.php` with PDO SQLite.

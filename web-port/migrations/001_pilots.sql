CREATE TABLE IF NOT EXISTS pilots (
 id TEXT PRIMARY KEY NOT NULL,
 callsign TEXT NOT NULL UNIQUE,
 gear TEXT CHECK (gear IS NULL OR gear IN ('A-Gear','B-Gear','I-Gear','M-Gear')),
 nation TEXT CHECK (nation IS NULL OR nation IN ('ANI','BCU')),
 location INTEGER,
 progress INTEGER NOT NULL DEFAULT 0,
 mission_ends INTEGER,
 revision INTEGER NOT NULL DEFAULT 0
);
CREATE TRIGGER IF NOT EXISTS immutable_nation
BEFORE UPDATE OF nation ON pilots
WHEN OLD.nation IS NOT NULL AND NEW.nation IS NOT OLD.nation
BEGIN SELECT RAISE(ABORT, 'Nation is permanent'); END;

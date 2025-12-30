-- Create database and tables
CREATE DATABASE IF NOT EXISTS `swim_league` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `swim_league`;

SET sql_notes = 0;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','organizer','coach','athlete') NOT NULL DEFAULT 'athlete',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Clubs
CREATE TABLE IF NOT EXISTS clubs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  city VARCHAR(100) NULL
) ENGINE=InnoDB;

-- Athletes
CREATE TABLE IF NOT EXISTS athletes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  club_id INT NULL,
  gender ENUM('M','F') NOT NULL DEFAULT 'M',
  birthdate DATE NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Seasons
CREATE TABLE IF NOT EXISTS seasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL
) ENGINE=InnoDB;

-- Events (per season)
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Swim events (e.g., 50m Freestyle Male/Female etc.)
CREATE TABLE IF NOT EXISTS swim_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  distance_m INT NOT NULL,
  stroke VARCHAR(20) NOT NULL,
  gender ENUM('M','F','X') NOT NULL DEFAULT 'X'
) ENGINE=InnoDB;

-- Meets (within an event)
CREATE TABLE IF NOT EXISTS meets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  meet_on DATE NOT NULL,
  venue VARCHAR(150) NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Races (heats/rounds for a swim_event)
CREATE TABLE IF NOT EXISTS races (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meet_id INT NOT NULL,
  swim_event_id INT NOT NULL,
  round_name VARCHAR(50) NOT NULL DEFAULT 'Final',
  heat_no INT NOT NULL DEFAULT 1,
  FOREIGN KEY (meet_id) REFERENCES meets(id) ON DELETE CASCADE,
  FOREIGN KEY (swim_event_id) REFERENCES swim_events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Race entries (lane assignment)
CREATE TABLE IF NOT EXISTS race_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  race_id INT NOT NULL,
  athlete_id INT NOT NULL,
  lane INT NOT NULL,
  UNIQUE KEY uq_race_lane (race_id, lane),
  UNIQUE KEY uq_race_athlete (race_id, athlete_id),
  FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
  FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Results
CREATE TABLE IF NOT EXISTS results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  race_id INT NOT NULL,
  athlete_id INT NOT NULL,
  lane INT NOT NULL,
  time_ms INT NOT NULL,
  status ENUM('OK','DQ','DNF') NOT NULL DEFAULT 'OK',
  points INT NOT NULL DEFAULT 0,
  medal ENUM('GOLD','SILVER','BRONZE','FINALIST','NONE') NOT NULL DEFAULT 'NONE',
  medal_points INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_race_lane_result (race_id, lane),
  FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
  FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Splits (optional)
CREATE TABLE IF NOT EXISTS splits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  result_id INT NOT NULL,
  split_no INT NOT NULL,
  time_ms INT NOT NULL,
  FOREIGN KEY (result_id) REFERENCES results(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Activities (athlete training log)
CREATE TABLE IF NOT EXISTS activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  athlete_id INT NOT NULL,
  activity_on DATE NOT NULL,
  distance_m INT NOT NULL,
  duration_ms INT NOT NULL,
  pool_length_m INT NULL,
  stroke_type VARCHAR(20) NULL,
  avg_hr INT NULL,
  max_hr INT NULL,
  calories INT NULL,
  swolf INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Achievements
CREATE TABLE IF NOT EXISTS achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS athlete_achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  athlete_id INT NOT NULL,
  achievement_id INT NOT NULL,
  granted_on DATE NOT NULL,
  UNIQUE KEY uq_ath_ach (athlete_id, achievement_id),
  FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE,
  FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Leaderboards (materialized optional)
CREATE TABLE IF NOT EXISTS leaderboards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_id INT NOT NULL,
  swim_event_id INT NOT NULL,
  athlete_id INT NOT NULL,
  total_points INT NOT NULL,
  best_time_ms INT NOT NULL,
  UNIQUE KEY uq_lead (season_id, swim_event_id, athlete_id),
  FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
  FOREIGN KEY (swim_event_id) REFERENCES swim_events(id) ON DELETE CASCADE,
  FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Optional view: base leaderboard from results
DROP VIEW IF EXISTS v_leaderboard_base;
CREATE VIEW v_leaderboard_base AS
SELECT e.season_id, rc.swim_event_id, r.athlete_id,
       SUM(r.points) AS total_points,
       MIN(CASE WHEN r.status='OK' THEN r.time_ms END) AS best_time_ms
FROM results r
JOIN races rc ON r.race_id = rc.id
JOIN meets m ON rc.meet_id = m.id
JOIN events e ON m.event_id = e.id
GROUP BY e.season_id, rc.swim_event_id, r.athlete_id;

SET sql_notes = 1;

-- Seed data
INSERT INTO users(name,email,password_hash,role) VALUES
('Admin','admin@local.test', '$2y$10$hC5TQ1s2Zf8t5dI2GZk2uegYH7Qb4v5m6eZpQ7j1l8M9n3o2DkQfS', 'admin');
-- Password for admin above is: admin123 (bcrypt). Change after import.

INSERT INTO clubs(name,city) VALUES ('Shark Swim Club','Jakarta');

INSERT INTO seasons(name,start_date,end_date) VALUES ('2025 Season','2025-01-01','2025-12-31');

INSERT INTO events(season_id,name) VALUES (1,'National Series');

INSERT INTO swim_events(name,distance_m,stroke,gender) VALUES
('50m Freestyle',50,'FREE','X'),
('100m Freestyle',100,'FREE','X'),
('200m Freestyle',200,'FREE','X');

-- Two athletes
INSERT INTO users(name,email,password_hash,role) VALUES
('Alice','alice@example.com', '$2y$10$hC5TQ1s2Zf8t5dI2GZk2uegYH7Qb4v5m6eZpQ7j1l8M9n3o2DkQfS', 'athlete'),
('Bob','bob@example.com', '$2y$10$hC5TQ1s2Zf8t5dI2GZk2uegYH7Qb4v5m6eZpQ7j1l8M9n3o2DkQfS', 'athlete');

INSERT INTO athletes(user_id, club_id, gender, birthdate) VALUES
(2, 1, 'F', '2012-05-10'),
(3, 1, 'M', '2010-09-20');

INSERT INTO meets(event_id,name,meet_on,venue) VALUES (1,'Jakarta Open','2025-02-01','GBK');

-- One race (50m Free)
INSERT INTO races(meet_id,swim_event_id,round_name,heat_no) VALUES (1,1,'Final',1);

-- Entries lanes 3 and 4
INSERT INTO race_entries(race_id,athlete_id,lane) VALUES (1,1,3),(1,2,4);

-- Results
INSERT INTO results(race_id,athlete_id,lane,time_ms,status,points,medal,medal_points) VALUES
(1,1,3,32000,'OK', 1000,'GOLD',25),
(1,2,4,35000,'OK', 757,'SILVER',18);

-- Activities example
INSERT INTO activities(athlete_id,activity_on,distance_m,duration_ms,pool_length_m,stroke_type,avg_hr,max_hr,calories,swolf) VALUES
(1,'2025-02-02',1500,1800000,25,'FREE',130,160,500,40),
(2,'2025-02-03',1000,1300000,50,'FREE',120,150,350,38);

-- Example basic leaderboard query
-- Points DESC, then best_time_ms ASC
SELECT u.name AS athlete, l.total_points, l.best_time_ms
FROM v_leaderboard_base l
JOIN athletes a ON l.athlete_id=a.id
JOIN users u ON a.user_id=u.id
WHERE l.season_id=1 AND l.swim_event_id=1
ORDER BY l.total_points DESC, l.best_time_ms ASC;
-- Workouts (coach planned training)
CREATE TABLE IF NOT EXISTS workouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  coach_user_id INT NOT NULL,
  athlete_id INT NOT NULL,
  club_id INT NULL,
  planned_on DATE NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  target_distance_m INT NULL,
  stroke_type VARCHAR(20) NULL,
  target_swolf INT NULL,
  status ENUM('planned','completed') NOT NULL DEFAULT 'planned',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (coach_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

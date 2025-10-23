PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS Bus_Company (
  id            TEXT PRIMARY KEY,
  name          TEXT UNIQUE NOT NULL,
  logo_path     TEXT,
  created_at    TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS User (
  id            TEXT PRIMARY KEY,
  full_name     TEXT,
  email         TEXT NOT NULL UNIQUE,
  password      TEXT NOT NULL,
  role          TEXT NOT NULL,
  company_id    TEXT NULL,
  balance       INTEGER DEFAULT 800,
  created_at    TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

CREATE TABLE IF NOT EXISTS Coupons (
  id            TEXT PRIMARY KEY,
  code          TEXT NOT NULL,
  discount      REAL NOT NULL,
  company_id    TEXT NULL,
  usage_limit   INTEGER NOT NULL,
  expire_date   TEXT NOT NULL,
  created_at    TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

CREATE TABLE IF NOT EXISTS Trips (
  id               TEXT PRIMARY KEY,
  company_id       TEXT NOT NULL,
  destination_city TEXT NOT NULL,
  arrival_time     TEXT NOT NULL,
  departure_time   TEXT NOT NULL,
  departure_city   TEXT NOT NULL,
  price            INTEGER NOT NULL,
  capacity         INTEGER NOT NULL,
  created_at       TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

CREATE TABLE IF NOT EXISTS Tickets (
  id            TEXT PRIMARY KEY,
  trip_id       TEXT NOT NULL,
  user_id       TEXT NOT NULL,
  status        TEXT NOT NULL DEFAULT 'ACTIVE',
  total_price   INTEGER NOT NULL,
  created_at    TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (trip_id) REFERENCES Trips(id),
  FOREIGN KEY (user_id) REFERENCES User(id)
);

CREATE TABLE IF NOT EXISTS User_Coupons (
  id            TEXT PRIMARY KEY,
  coupon_id     TEXT NOT NULL,
  user_id       TEXT NOT NULL,
  created_at    TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (coupon_id) REFERENCES Coupons(id),
  FOREIGN KEY (user_id)   REFERENCES User(id)
);

CREATE TABLE IF NOT EXISTS Booked_Seats (
  id            TEXT PRIMARY KEY,
  ticket_id     TEXT NOT NULL,
  seat_number   INTEGER NOT NULL,
  created_at    TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (ticket_id) REFERENCES Tickets(id)
);

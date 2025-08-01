CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "rag_chunks"(
  "id" integer primary key autoincrement not null,
  "source" varchar,
  "chunk" text not null,
  "embedding" text not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "broadcasts"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" varchar not null,
  "payload" text not null,
  "created_at" datetime
);
CREATE UNIQUE INDEX "broadcasts_uuid_unique" on "broadcasts"("uuid");
CREATE INDEX "broadcasts_connection_index" on "broadcasts"("connection");
CREATE TABLE IF NOT EXISTS "webpages"(
  "id" integer primary key autoincrement not null,
  "webpage_id" varchar not null,
  "url" varchar not null,
  "title" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "webpages_webpage_id_unique" on "webpages"("webpage_id");
CREATE UNIQUE INDEX "webpages_url_unique" on "webpages"("url");
CREATE TABLE IF NOT EXISTS "webpage_chunks"(
  "id" integer primary key autoincrement not null,
  "webpages_id" integer not null,
  "chunk" text not null,
  "embedding" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("webpages_id") references "webpages"("id") on delete cascade
);
CREATE INDEX "webpage_chunks_webpages_id_index" on "webpage_chunks"(
  "webpages_id"
);

INSERT INTO migrations VALUES(1,'0000_00_00_000000_create_sqlite_file',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(4,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(5,'2025_07_02_000001_create_rag_chunks_table',1);
INSERT INTO migrations VALUES(6,'2025_07_09_020530_create_broadcasts_table',1);
INSERT INTO migrations VALUES(7,'2025_07_18_000001_create_webpages_table',2);
INSERT INTO migrations VALUES(8,'2025_07_18_000002_create_webpage_chunks_table',2);

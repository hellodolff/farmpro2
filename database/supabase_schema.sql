-- ============================================================================
-- FarmPro - PostgreSQL / Supabase Database Schema
-- Generated from Laravel migrations for Supabase SQL Editor
-- ============================================================================
-- Catatan:
-- 1. Jalankan script ini di Supabase Dashboard -> SQL Editor -> New Query -> Run.
-- 2. Semua tabel dibuat di schema `public` agar kompatibel dengan Laravel pgsql
--    dan Supabase PostgREST API.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. FONDASI & AUTHENTICATION
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.farm_profiles (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    address     VARCHAR(255) NULL,
    timezone    VARCHAR(40) NOT NULL DEFAULT 'Asia/Jakarta',
    currency    VARCHAR(3) NOT NULL DEFAULT 'IDR',
    created_at  TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at  TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

CREATE TABLE IF NOT EXISTS public.users (
    id                BIGSERIAL PRIMARY KEY,
    name              VARCHAR(255) NOT NULL,
    email             VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    password          VARCHAR(255) NOT NULL,
    remember_token    VARCHAR(100) NULL,
    farm_profile_id   BIGINT NULL REFERENCES public.farm_profiles(id) ON DELETE SET NULL,
    role              VARCHAR(20) NOT NULL DEFAULT 'operator', -- admin | operator | viewer
    created_at        TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at        TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

CREATE TABLE IF NOT EXISTS public.password_reset_tokens (
    email       VARCHAR(255) PRIMARY KEY,
    token       VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

CREATE TABLE IF NOT EXISTS public.sessions (
    id            VARCHAR(255) PRIMARY KEY,
    user_id       BIGINT NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    TEXT NULL,
    payload       TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS sessions_user_id_index ON public.sessions (user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON public.sessions (last_activity);

CREATE TABLE IF NOT EXISTS public.personal_access_tokens (
    id             BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id   BIGINT NOT NULL,
    name           TEXT NOT NULL,
    token          VARCHAR(64) NOT NULL UNIQUE,
    abilities      TEXT NULL,
    last_used_at   TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    expires_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS personal_access_tokens_tokenable_index
    ON public.personal_access_tokens (tokenable_type, tokenable_id);

-- ----------------------------------------------------------------------------
-- 2. CACHE & QUEUE (LARAVEL SYSTEM TABLES)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.cache (
    key         VARCHAR(255) PRIMARY KEY,
    value       TEXT NOT NULL,
    expiration  BIGINT NOT NULL
);
CREATE INDEX IF NOT EXISTS cache_expiration_index ON public.cache (expiration);

CREATE TABLE IF NOT EXISTS public.cache_locks (
    key         VARCHAR(255) PRIMARY KEY,
    owner       VARCHAR(255) NOT NULL,
    expiration  BIGINT NOT NULL
);
CREATE INDEX IF NOT EXISTS cache_locks_expiration_index ON public.cache_locks (expiration);

CREATE TABLE IF NOT EXISTS public.jobs (
    id           BIGSERIAL PRIMARY KEY,
    queue        VARCHAR(255) NOT NULL,
    payload      TEXT NOT NULL,
    attempts     SMALLINT NOT NULL,
    reserved_at  INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at   INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS jobs_queue_index ON public.jobs (queue);

CREATE TABLE IF NOT EXISTS public.job_batches (
    id              VARCHAR(255) PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    total_jobs      INTEGER NOT NULL,
    pending_jobs    INTEGER NOT NULL,
    failed_jobs     INTEGER NOT NULL,
    failed_job_ids  TEXT NOT NULL,
    options         TEXT NULL,
    cancelled_at    INTEGER NULL,
    created_at      INTEGER NOT NULL,
    finished_at     INTEGER NULL
);

CREATE TABLE IF NOT EXISTS public.failed_jobs (
    id          BIGSERIAL PRIMARY KEY,
    uuid        VARCHAR(255) NOT NULL UNIQUE,
    connection  TEXT NOT NULL,
    queue       TEXT NOT NULL,
    payload     TEXT NOT NULL,
    exception   TEXT NOT NULL,
    failed_at   TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS failed_jobs_connection_queue_failed_at_index
    ON public.failed_jobs (connection, queue, failed_at);

-- ----------------------------------------------------------------------------
-- 3. DATA MASTER (KOMODITAS, KANDANG, MITRA, INVENTARIS)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.commodities (
    id                       BIGSERIAL PRIMARY KEY,
    code                     VARCHAR(20) NOT NULL UNIQUE, -- broiler | pejantan | layer
    name                     VARCHAR(50) NOT NULL,
    product_unit             VARCHAR(10) NOT NULL DEFAULT 'kg',
    description              TEXT NULL,
    target_fcr_min           NUMERIC(4, 2) NULL,
    target_fcr_max           NUMERIC(4, 2) NULL,
    target_mortality_pct     NUMERIC(5, 2) NULL,
    target_harvest_weight_kg NUMERIC(6, 3) NULL,
    target_cycle_days        INTEGER NULL,
    effective_from           DATE NULL,
    deleted_at               TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at               TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at               TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

CREATE TABLE IF NOT EXISTS public.coops (
    id               BIGSERIAL PRIMARY KEY,
    code             VARCHAR(20) NOT NULL UNIQUE,
    name             VARCHAR(80) NOT NULL,
    location         VARCHAR(255) NULL,
    capacity         INTEGER NOT NULL,
    type             VARCHAR(30) NULL,                  -- postal | closed house | baterai
    status           VARCHAR(20) NOT NULL DEFAULT 'available', -- available | occupied | maintenance
    sanitation_notes TEXT NULL,
    deleted_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

CREATE TABLE IF NOT EXISTS public.customers_suppliers (
    id          BIGSERIAL PRIMARY KEY,
    type        VARCHAR(10) NOT NULL,                   -- supplier | customer
    name        VARCHAR(255) NOT NULL,
    phone       VARCHAR(30) NULL,
    address     VARCHAR(255) NULL,
    deleted_at  TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at  TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at  TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS customers_suppliers_type_index ON public.customers_suppliers (type);

CREATE TABLE IF NOT EXISTS public.inventory_items (
    id               BIGSERIAL PRIMARY KEY,
    code             VARCHAR(30) NOT NULL UNIQUE,
    name             VARCHAR(255) NOT NULL,
    category         VARCHAR(20) NOT NULL,              -- feed | vaccine | medicine | vitamin | sanitation | equipment
    base_unit        VARCHAR(15) NOT NULL,
    purchase_unit    VARCHAR(15) NULL,
    unit_conversion  NUMERIC(10, 3) NOT NULL DEFAULT 1,
    min_stock        NUMERIC(14, 3) NOT NULL DEFAULT 0,
    stock            NUMERIC(14, 3) NOT NULL DEFAULT 0,
    avg_cost         NUMERIC(15, 2) NOT NULL DEFAULT 0,
    storage_location VARCHAR(255) NULL,
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    deleted_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS inventory_items_category_index ON public.inventory_items (category);

-- ----------------------------------------------------------------------------
-- 4. BATCH & MUTASI POPULASI
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.batches (
    id                 BIGSERIAL PRIMARY KEY,
    code               VARCHAR(30) NOT NULL UNIQUE,
    commodity_id       BIGINT NOT NULL REFERENCES public.commodities(id),
    coop_id            BIGINT NOT NULL REFERENCES public.coops(id),
    supplier_id        BIGINT NULL REFERENCES public.customers_suppliers(id) ON DELETE SET NULL,
    start_date         DATE NOT NULL,
    end_date           DATE NULL,
    seed_type          VARCHAR(10) NOT NULL DEFAULT 'doc', -- doc | pullet
    initial_population INTEGER NOT NULL,
    current_population INTEGER NOT NULL,
    price_per_head     NUMERIC(12, 2) NOT NULL DEFAULT 0,
    initial_cost       NUMERIC(15, 2) NOT NULL DEFAULT 0,
    status             VARCHAR(15) NOT NULL DEFAULT 'active', -- planned | active | completed | cancelled
    notes              TEXT NULL,
    deleted_at         TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at         TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at         TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS batches_status_commodity_id_index ON public.batches (status, commodity_id);

CREATE TABLE IF NOT EXISTS public.batch_population_movements (
    id               BIGSERIAL PRIMARY KEY,
    batch_id         BIGINT NOT NULL REFERENCES public.batches(id) ON DELETE CASCADE,
    type             VARCHAR(15) NOT NULL,              -- death | cull | harvest | transfer_in | transfer_out
    movement_date    DATE NOT NULL,
    quantity         INTEGER NOT NULL,
    reason           VARCHAR(255) NULL,
    related_batch_id BIGINT NULL REFERENCES public.batches(id) ON DELETE SET NULL,
    created_by       BIGINT NULL REFERENCES public.users(id) ON DELETE SET NULL,
    created_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS batch_population_movements_batch_date_index
    ON public.batch_population_movements (batch_id, movement_date);

-- ----------------------------------------------------------------------------
-- 5. OPERASIONAL HARIAN (CATATAN HARIAN, PAKAN, BOBOT, TELUR, PANEN)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.daily_records (
    id               BIGSERIAL PRIMARY KEY,
    batch_id         BIGINT NOT NULL REFERENCES public.batches(id) ON DELETE CASCADE,
    record_date      DATE NOT NULL,
    deaths           INTEGER NOT NULL DEFAULT 0,
    culls            INTEGER NOT NULL DEFAULT 0,
    temperature      NUMERIC(4, 1) NULL,
    humidity         SMALLINT NULL,
    litter_condition VARCHAR(30) NULL,
    notes            TEXT NULL,
    recorded_by      BIGINT NULL REFERENCES public.users(id) ON DELETE SET NULL,
    deleted_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    CONSTRAINT daily_records_batch_date_unique UNIQUE (batch_id, record_date)
);

CREATE TABLE IF NOT EXISTS public.feed_records (
    id                BIGSERIAL PRIMARY KEY,
    batch_id          BIGINT NOT NULL REFERENCES public.batches(id) ON DELETE CASCADE,
    record_date       DATE NOT NULL,
    inventory_item_id BIGINT NULL REFERENCES public.inventory_items(id) ON DELETE SET NULL,
    feed_phase        VARCHAR(30) NULL,                 -- starter | grower | finisher | layer
    feed_kg           NUMERIC(10, 2) NOT NULL,
    cost              NUMERIC(15, 2) NOT NULL DEFAULT 0,
    created_at        TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at        TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS feed_records_batch_date_index ON public.feed_records (batch_id, record_date);

CREATE TABLE IF NOT EXISTS public.weight_records (
    id                    BIGSERIAL PRIMARY KEY,
    batch_id              BIGINT NOT NULL REFERENCES public.batches(id) ON DELETE CASCADE,
    record_date           DATE NOT NULL,
    age_days              INTEGER NOT NULL,
    sample_count          INTEGER NULL,
    total_sample_weight_g NUMERIC(12, 2) NULL,
    avg_weight_g          NUMERIC(8, 2) NOT NULL,
    notes                 TEXT NULL,
    created_at            TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at            TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS weight_records_batch_date_index ON public.weight_records (batch_id, record_date);

CREATE TABLE IF NOT EXISTS public.egg_productions (
    id              BIGSERIAL PRIMARY KEY,
    batch_id        BIGINT NOT NULL REFERENCES public.batches(id) ON DELETE CASCADE,
    record_date     DATE NOT NULL,
    total_eggs      INTEGER NOT NULL,
    sellable_eggs   INTEGER NOT NULL DEFAULT 0,
    cracked_eggs    INTEGER NOT NULL DEFAULT 0,
    dirty_eggs      INTEGER NOT NULL DEFAULT 0,
    reject_eggs     INTEGER NOT NULL DEFAULT 0,
    egg_weight_kg   NUMERIC(10, 3) NOT NULL DEFAULT 0,
    productive_hens INTEGER NOT NULL,
    created_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    CONSTRAINT egg_productions_batch_date_unique UNIQUE (batch_id, record_date)
);

CREATE TABLE IF NOT EXISTS public.harvests (
    id              BIGSERIAL PRIMARY KEY,
    batch_id        BIGINT NOT NULL REFERENCES public.batches(id) ON DELETE CASCADE,
    harvest_date    DATE NOT NULL,
    heads           INTEGER NOT NULL,
    total_weight_kg NUMERIC(12, 2) NOT NULL,
    weight_basis    VARCHAR(10) NOT NULL DEFAULT 'live', -- live | carcass
    notes           TEXT NULL,
    created_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

-- ----------------------------------------------------------------------------
-- 6. KESEHATAN & MUTASI INVENTARIS
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.inventory_movements (
    id                BIGSERIAL PRIMARY KEY,
    inventory_item_id BIGINT NOT NULL REFERENCES public.inventory_items(id) ON DELETE CASCADE,
    direction         VARCHAR(3) NOT NULL,              -- in | out
    reason            VARCHAR(20) NOT NULL,             -- purchase | batch_usage | adjustment | expired | return | transfer
    quantity          NUMERIC(14, 3) NOT NULL,
    unit_cost         NUMERIC(15, 2) NOT NULL DEFAULT 0,
    lot_number        VARCHAR(50) NULL,
    expiry_date       DATE NULL,
    batch_id          BIGINT NULL REFERENCES public.batches(id) ON DELETE SET NULL,
    movement_date     DATE NOT NULL,
    created_at        TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at        TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS inventory_movements_item_date_index
    ON public.inventory_movements (inventory_item_id, movement_date);

CREATE TABLE IF NOT EXISTS public.health_records (
    id                BIGSERIAL PRIMARY KEY,
    batch_id          BIGINT NOT NULL REFERENCES public.batches(id) ON DELETE CASCADE,
    record_date       DATE NOT NULL,
    type              VARCHAR(15) NOT NULL,             -- vaccine | medicine | vitamin | symptom
    product_name      VARCHAR(255) NULL,
    dose              VARCHAR(255) NULL,
    method            VARCHAR(255) NULL,
    lot_number        VARCHAR(50) NULL,
    expiry_date       DATE NULL,
    withdrawal_days   SMALLINT NULL,
    cost              NUMERIC(15, 2) NOT NULL DEFAULT 0,
    performed_by      VARCHAR(255) NULL,
    symptoms          TEXT NULL,
    suspected_disease VARCHAR(255) NULL,
    cases             INTEGER NULL,
    notes             TEXT NULL,
    created_at        TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at        TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS health_records_batch_date_index ON public.health_records (batch_id, record_date);

CREATE TABLE IF NOT EXISTS public.health_schedules (
    id               BIGSERIAL PRIMARY KEY,
    batch_id         BIGINT NOT NULL REFERENCES public.batches(id) ON DELETE CASCADE,
    title            VARCHAR(255) NOT NULL,
    type             VARCHAR(15) NOT NULL DEFAULT 'vaccine',
    due_date         DATE NOT NULL,
    status           VARCHAR(10) NOT NULL DEFAULT 'pending', -- pending | done | skipped
    priority         VARCHAR(10) NOT NULL DEFAULT 'normal',
    health_record_id BIGINT NULL REFERENCES public.health_records(id) ON DELETE SET NULL,
    created_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at       TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS health_schedules_status_due_index ON public.health_schedules (status, due_date);

-- ----------------------------------------------------------------------------
-- 7. KEUANGAN, PERENCANAAN, NOTIFIKASI & AUDIT
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.sales (
    id             BIGSERIAL PRIMARY KEY,
    batch_id       BIGINT NULL REFERENCES public.batches(id) ON DELETE SET NULL,
    harvest_id     BIGINT NULL REFERENCES public.harvests(id) ON DELETE SET NULL,
    customer_id    BIGINT NULL REFERENCES public.customers_suppliers(id) ON DELETE SET NULL,
    sale_date      DATE NOT NULL,
    product        VARCHAR(10) NOT NULL,                -- meat | egg | manure | other
    quantity       NUMERIC(14, 3) NOT NULL,
    unit           VARCHAR(10) NOT NULL,
    price_per_unit NUMERIC(15, 2) NOT NULL,
    deductions     NUMERIC(15, 2) NOT NULL DEFAULT 0,
    transport_cost NUMERIC(15, 2) NOT NULL DEFAULT 0,
    total          NUMERIC(15, 2) NOT NULL,
    payment_status VARCHAR(10) NOT NULL DEFAULT 'paid', -- paid | partial | unpaid
    deleted_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

CREATE TABLE IF NOT EXISTS public.expenses (
    id             BIGSERIAL PRIMARY KEY,
    batch_id       BIGINT NULL REFERENCES public.batches(id) ON DELETE SET NULL,
    supplier_id    BIGINT NULL REFERENCES public.customers_suppliers(id) ON DELETE SET NULL,
    expense_date   DATE NOT NULL,
    category       VARCHAR(30) NOT NULL,
    amount         NUMERIC(15, 2) NOT NULL,
    payment_method VARCHAR(20) NULL,
    receipt_path   VARCHAR(255) NULL,
    notes          TEXT NULL,
    deleted_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

CREATE TABLE IF NOT EXISTS public.financial_transactions (
    id             BIGSERIAL PRIMARY KEY,
    trx_date       DATE NOT NULL,
    type           VARCHAR(10) NOT NULL,                -- income | expense | transfer
    category       VARCHAR(40) NOT NULL,
    amount         NUMERIC(15, 2) NOT NULL,
    payment_method VARCHAR(20) NULL,
    batch_id       BIGINT NULL REFERENCES public.batches(id) ON DELETE SET NULL,
    source_type    VARCHAR(255) NULL,
    source_id      BIGINT NULL,
    description    TEXT NULL,
    reversed_of_id BIGINT NULL REFERENCES public.financial_transactions(id) ON DELETE SET NULL,
    created_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at     TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS financial_transactions_source_index ON public.financial_transactions (source_type, source_id);
CREATE INDEX IF NOT EXISTS financial_transactions_type_date_index ON public.financial_transactions (type, trx_date);

CREATE TABLE IF NOT EXISTS public.budget_simulations (
    id           BIGSERIAL PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    commodity_id BIGINT NULL REFERENCES public.commodities(id) ON DELETE SET NULL,
    inputs       JSONB NOT NULL,
    results      JSONB NOT NULL,
    created_by   BIGINT NULL REFERENCES public.users(id) ON DELETE SET NULL,
    created_at   TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at   TIMESTAMP(0) WITHOUT TIME ZONE NULL
);

CREATE TABLE IF NOT EXISTS public.farm_notifications (
    id           BIGSERIAL PRIMARY KEY,
    type         VARCHAR(30) NOT NULL,                  -- vaccine_due | low_stock | high_mortality | hdp_drop | harvest
    priority     VARCHAR(10) NOT NULL DEFAULT 'normal',
    title        VARCHAR(255) NOT NULL,
    body         TEXT NULL,
    subject_type VARCHAR(255) NULL,
    subject_id   BIGINT NULL,
    read_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at   TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at   TIMESTAMP(0) WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS farm_notifications_subject_index ON public.farm_notifications (subject_type, subject_id);

CREATE TABLE IF NOT EXISTS public.audit_logs (
    id             BIGSERIAL PRIMARY KEY,
    user_id        BIGINT NULL REFERENCES public.users(id) ON DELETE SET NULL,
    action         VARCHAR(30) NOT NULL,
    auditable_type VARCHAR(255) NULL,
    auditable_id   BIGINT NULL,
    old_values     JSONB NULL,
    new_values     JSONB NULL,
    created_at     TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS audit_logs_auditable_index ON public.audit_logs (auditable_type, auditable_id);

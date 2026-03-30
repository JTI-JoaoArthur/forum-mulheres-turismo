-- =============================================
-- Fórum de Mulheres no Turismo — CMS Schema
-- SQLite 3
-- =============================================

PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;

-- Usuários administrativos
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL DEFAULT '',
    name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'editor' CHECK(role IN ('admin', 'editor')),
    recovery_email1 TEXT,
    recovery_email2 TEXT,
    is_active INTEGER DEFAULT 1,
    needs_password INTEGER DEFAULT 0,
    failed_attempts INTEGER DEFAULT 0,
    locked_until TEXT,
    recovery_token_hash TEXT,
    recovery_token_expires TEXT,
    last_login TEXT,
    created_at TEXT DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Palestrantes
CREATE TABLE IF NOT EXISTS speakers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    position TEXT,
    institution TEXT,
    photo TEXT,
    linkedin TEXT,
    instagram TEXT,
    website TEXT,
    display_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Programação
CREATE TABLE IF NOT EXISTS schedule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    day INTEGER NOT NULL CHECK(day IN (1, 2)),
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    title TEXT NOT NULL,
    location TEXT,
    description TEXT,
    display_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Notícias
CREATE TABLE IF NOT EXISTS news (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    summary TEXT NOT NULL,
    body TEXT,
    author TEXT,
    featured_image TEXT,
    published_at TEXT NOT NULL,
    is_featured INTEGER DEFAULT 0,
    is_in_gallery INTEGER DEFAULT 1,
    is_visible INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Galeria de imagens por notícia
CREATE TABLE IF NOT EXISTS news_gallery (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    news_id INTEGER NOT NULL,
    image TEXT NOT NULL,
    display_order INTEGER DEFAULT 0,
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
);

-- Slides manuais do carrossel
CREATE TABLE IF NOT EXISTS carousel (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    image TEXT NOT NULL,
    link TEXT,
    display_order INTEGER DEFAULT 0,
    is_pinned INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Álbum de fotos (uploads manuais além das notícias)
CREATE TABLE IF NOT EXISTS gallery (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    image TEXT NOT NULL,
    caption TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Apoio e Realização (logos)
CREATE TABLE IF NOT EXISTS sponsors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    logo TEXT NOT NULL,
    website TEXT,
    category TEXT NOT NULL CHECK(category IN ('apoio', 'realizacao')),
    display_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Configurações do site (chave-valor)
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Log de auditoria
CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    entity TEXT,
    entity_id INTEGER,
    details TEXT,
    ip_address TEXT,
    created_at TEXT DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Configurações iniciais
INSERT OR IGNORE INTO settings (key, value) VALUES
    ('site_title', 'Fórum de Mulheres no Turismo'),
    ('about_title', 'Fórum de Mulheres no Turismo'),
    ('about_body', ''),
    ('about_image1', ''),
    ('about_image2', ''),
    ('contact_city', 'João Pessoa, Paraíba'),
    ('contact_venue', 'Centro de Convenções de João Pessoa'),
    ('contact_phone', 'Telefone'),
    ('contact_hours', 'Seg. a Sex. das 9h às 18h'),
    ('contact_email', 'contato@turismo.gov.br'),
    ('contact_email_desc', 'Entre em contato conosco!'),
    ('footer_about', 'Fórum de Mulheres no Turismo — uma iniciativa do Ministério do Turismo e ONU Turismo.'),
    ('footer_location', 'Centro de Convenções de João Pessoa'),
    ('footer_date', '3 e 4 de Junho de 2026'),
    ('social_instagram', 'https://www.instagram.com/mturismo/'),
    ('social_facebook', 'https://www.facebook.com/MinisterioDoTurismo'),
    ('social_twitter', 'https://x.com/MTurismo'),
    ('social_youtube', 'https://www.youtube.com/c/MinisteriodoTurismo'),
    ('social_linkedin', 'https://www.linkedin.com/company/mturismo/'),
    ('form_recipient', 'default@turismo.gov.br'),
    ('form_sender', 'noreply@turismo.gov.br'),
    ('event_date', 'June 3, 2026 09:00:00'),
    ('maps_query', 'Centro+de+Convenções+de+João+Pessoa,+PB,+Brasil');

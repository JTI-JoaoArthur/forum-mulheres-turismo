# Changelog

Todas as alteracoes relevantes deste projeto serao documentadas neste arquivo.
Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

## [1.0.0] - 2026-03-30

### Adicionado
- Site institucional completo com 7 paginas publicas (home, sobre, palestrantes, programacao, noticias, noticia, contato)
- Painel administrativo (`/admin`) com CMS completo em PHP/SQLite
- CRUD para noticias, palestrantes, programacao, carrossel, album de fotos, apoiadores e configuracoes
- Sistema de autenticacao com bcrypt, CSRF, lockout e audit log
- Dois perfis de usuario: admin (CGMK) e editor (ASCOM)
- Fluxo de troca de senha no primeiro acesso
- Recuperacao de senha preparada para ativacao via SMTP
- Sanitizacao HTML com HTML Purifier 4.18.0
- Validacao de URLs contra protocol injection
- Rate limiting no formulario de contato (5/IP a cada 15 min)
- Headers de seguranca: CSP, HSTS, X-Frame-Options, Permissions-Policy
- Subresource Integrity (SRI) nos CDNs externos
- CI/CD com GitHub Actions (lint PHP, checagem de segredos)
- Docker e docker-compose para ambiente reproduzivel
- Hero com countdown, carrossel Swiper, album com rotacao sequencial
- Formulario de contato com honeypot, CSRF e validacao server-side
- Redirects .html para .php (compatibilidade com links antigos)

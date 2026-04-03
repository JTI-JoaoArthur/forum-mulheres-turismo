![CI](https://github.com/JTI-JoaoArthur/forum-mulheres-turismo/actions/workflows/ci.yml/badge.svg)
![PHP 8+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)

# Forum de Mulheres no Turismo

Site institucional + painel administrativo (CMS) do Fórum de Mulheres no Turismo, evento organizado pelo Ministério do Turismo em parceria com a ONU Turismo. Centro de Convenções de João Pessoa — PB, junho de 2026.

O projeto inteiro roda em PHP puro com SQLite — sem Composer, sem NPM. Vai ficar em subdomínio próprio (fora do Plone), então quanto menos dependência externa, menos dor de cabeça no deploy.

## Stack

- **Back-end:** PHP 8.2+, SQLite com WAL mode, PDO
- **Front-end:** Bootstrap 4, jQuery, Slick, Swiper, Magnific Popup
- **Servidor:** Apache + `.htaccess` (produção) / `php -S` (dev)
- **CI:** GitHub Actions — lint PHP, scan de secrets, verificação de arquivos sensíveis

## O que o site faz

**Parte pública:**
hero com carrossel (imagem e vídeo), countdown pro evento, programação com abas por dia, grid de palestrantes, notícias com detalhe por slug, álbum de fotos com rotação, apoiadores com logos e links, formulário de contato com mapa.

**Painel admin (`/admin`):**
CRUD de tudo — notícias, palestrantes, programação, carrossel, galeria, apoiadores. Upload de imagens com validação MIME real. Editor rico com sanitização HTML. Configurações gerais do site. Gestão de usuários com dois perfis (Admin tem acesso total, Editor mexe só em conteúdo). Audit log de toda ação.

## Arquitetura

```
PHP 8.2+ (PDO) ──► SQLite (WAL)
     │
     ├── /admin        CMS: Auth, CSRF, RBAC, Audit Log
     │   └── /lib      Classes: Auth, CSRF, Database, Upload
     │
     ├── /includes     Bootstrap da app, helpers, sanitização
     │
     └── /assets       CSS, JS, imagens
```

O banco é SQLite com WAL habilitado pra não travar em leituras concorrentes. Conteúdo nunca é deletado de verdade — tudo usa soft delete via flag `is_visible`, então dá pra reverter qualquer coisa pelo admin.

Uploads ganham nomes aleatórios e passam por validação de MIME type com `finfo` (não confia só na extensão). Arquivos executáveis são barrados.

## Segurança

Esse foi o ponto que mais demandou atenção. O site vai rodar em domínio `.gov.br`, então não dava pra deixar brecha:

- **Senhas** com bcrypt (cost 12), lockout após 5 tentativas erradas (15 min de bloqueio)
- **CSRF** com tokens de uso único, 64 bytes hex, expiração de 2h
- **XSS** coberto por HTMLPurifier + validação de URLs contra `javascript:` e `data:`
- **SQL injection** inexistente — 100% prepared statements via PDO
- **Sessões** com timeout de 30 min, cookies `HttpOnly` + `SameSite=Strict` + `Secure`
- **Headers HTTP** configurados no `.htaccess`: HSTS com preload, CSP com whitelist, X-Frame-Options DENY, nosniff, Permissions-Policy
- **Acesso direto** a `.sqlite`, `.sql`, `/lib/`, `/data/`, `/includes/` bloqueado pelo `.htaccess`
- **Audit log** registra login, logout, todo CRUD, tentativas falhas, alterações de senha

A recuperação de senha funciona por código enviado via SMTP (já implementado, aguardando configuração do servidor de e-mail em produção).

## Rodando local

```bash
git clone https://github.com/JTI-JoaoArthur/forum-mulheres-turismo.git
cd forum-mulheres-turismo

cp .env.example .env
# preencher .env com senhas (mínimo 12 chars)

php -S localhost:8000

# acessar /admin/setup.php pra criar os usuários
# depois /admin/ pra logar
```

O `.env.example` tem todas as variáveis documentadas. As de SMTP só são necessárias se quiser recuperação de senha funcionando.

## Deploy

Requisitos: Apache 2.4+ com `mod_rewrite` e `mod_headers`, PHP 8.0+ com `pdo_sqlite` e `mbstring`, HTTPS.

```bash
git clone https://github.com/JTI-JoaoArthur/forum-mulheres-turismo.git /var/www/forum
cd /var/www/forum && cp .env.example .env

chown -R www-data:www-data admin/data admin/uploads
chmod 750 admin/data admin/uploads
```

```apache
<VirtualHost *:443>
    ServerName forumdeturismo.gov.br
    DocumentRoot /var/www/forum
    <Directory /var/www/forum>
        AllowOverride All
        Require all granted
    </Directory>
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

Backup do SQLite com cron diário:
```bash
sqlite3 /var/www/forum/admin/data/cms.sqlite ".backup '/backups/cms-$(date +%Y%m%d).sqlite'"
```

## Licença

Template base: [Colorlib](https://colorlib.com/) "Event HTML-5". Atribuição mantida no footer.

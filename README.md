![CI](https://github.com/JTI-JoaoArthur/forum-mulheres-turismo/actions/workflows/ci.yml/badge.svg) ![PHP 8+](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)

# Forum de Mulheres no Turismo

Site institucional do **Forum de Mulheres no Turismo** — iniciativa conjunta do Ministerio do Turismo e da ONU Turismo. Evento presencial no Centro de Convencoes de Joao Pessoa, Paraiba.

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Back-end | PHP 8+ / SQLite (PDO, WAL) |
| Front-end | Bootstrap 4, jQuery 1.12.4, Slick, Swiper, Magnific Popup, WOW.js |
| CMS | Painel admin proprio (`/admin`) com autenticacao, CSRF, audit log |
| Servidor | Apache (`.htaccess`) — compativel com PHP built-in server para dev |

## Estrutura

```
/
├── index.php              # Home (hero, carrossel, countdown, sobre resumo)
├── sobre.php              # Sobre o evento + album de fotos
├── palestrantes.php       # Grid de palestrantes
├── programacao.php        # Programacao por dia (abas)
├── noticias.php           # Listagem de noticias
├── noticia.php            # Detalhe da noticia (slug)
├── contato.php            # Formulario de contato + mapa
├── contact_process.php    # Processamento do formulario (SMTP)
├── csrf_token.php         # Endpoint AJAX para token CSRF
│
├── includes/
│   ├── header.php         # Header unificado
│   ├── footer.php         # Footer unificado
│   ├── site.php           # Bootstrap: DB, helpers, sanitizacao
│   └── album.php          # Helper do album de fotos
│
├── admin/
│   ├── index.php          # Login
│   ├── dashboard.php      # Painel principal
│   ├── news.php           # CRUD noticias
│   ├── speakers.php       # CRUD palestrantes
│   ├── schedule.php       # CRUD programacao
│   ├── carousel.php       # CRUD carrossel de destaques
│   ├── gallery.php        # CRUD album de fotos
│   ├── sponsors.php       # CRUD apoiadores/realizadores
│   ├── settings.php       # Configuracoes do site
│   ├── users.php          # Gestao de usuarios (admin)
│   ├── setup.php          # Seed inicial de usuarios
│   ├── change-password.php
│   ├── recover-password.php
│   ├── logout.php
│   ├── lib/               # Auth, CSRF, Database, Upload
│   ├── sql/               # Schema SQLite
│   └── templates/         # Header/footer do admin
│
├── assets/
│   ├── css/               # Bootstrap, plugins, style.css, custom.min.css
│   ├── js/                # jQuery, plugins, main.js, custom.js
│   └── img/
│       ├── destaque/      # Imagens hero/destaque
│       ├── galeria/       # Album de fotos
│       ├── logos/         # Logos parceiros
│       ├── elementos/     # Elementos graficos
│       └── favicons/
│
├── .env.example           # Template de variaveis de ambiente
├── .htaccess              # Rewrites, seguranca, headers
└── CLAUDE.md              # Instrucoes para Claude Code
```

## Instalacao

### Requisitos

- PHP 8.0+
- Extensao PDO SQLite (`php-sqlite3`)
- Apache com `mod_rewrite` e `mod_headers` (producao)

### Setup local

```bash
# 1. Clonar o repositorio
git clone https://github.com/SEU_USUARIO/forum-mulheres-turismo.git
cd forum-mulheres-turismo

# 2. Configurar variaveis de ambiente
cp .env.example .env
# Editar .env com senhas seguras (minimo 12 caracteres)

# 3. Iniciar servidor de desenvolvimento
php -S localhost:8000

# 4. Acessar /admin/setup.php para criar os usuarios iniciais
# 5. Fazer login em /admin/
```

### Variaveis de ambiente

| Variavel | Descricao |
|----------|-----------|
| `SETUP_ADMIN_PASSWORD` | Senha do usuario admin (CGMK) — usado apenas no setup inicial |
| `SETUP_EDITOR_PASSWORD` | Senha do usuario editor (ASCOM) — usado apenas no setup inicial |
| `SMTP_HOST` | Servidor SMTP (necessario para recuperacao de senha) |
| `SMTP_PORT` | Porta SMTP (ex: 587) |
| `SMTP_USER` | Usuario SMTP |
| `SMTP_PASSWORD` | Senha SMTP |
| `SMTP_FROM` | E-mail remetente |

## Seguranca

- Senhas com **bcrypt** (cost 12)
- Tokens CSRF de uso unico (64 bytes hex, expiracao 1h)
- Sessoes com timeout de 30 min, `httponly`, `samesite=Strict`
- Bloqueio de conta apos 5 tentativas (lockout de 15 min)
- Sanitizacao HTML contra Stored XSS (`sanitizeHtml()`)
- Validacao de URLs contra `javascript:`/`data:` protocol injection
- Upload com validacao MIME real (`finfo`), nomes aleatorios, sem extensao executavel
- Headers HTTP: CSP, HSTS, X-Frame-Options DENY, X-Content-Type-Options, Permissions-Policy
- `.htaccess` bloqueia acesso direto a `.sqlite`, `.sql`, `/lib/`, `/data/`, `/includes/`
- Audit log completo (login, CRUD, alteracoes de senha, tentativas falhas)

## Perfis de usuario

| Perfil | Permissoes |
|--------|-----------|
| **Admin** (CGMK) | Acesso total: conteudo, configuracoes, gestao de usuarios, logs |
| **Editor** (ASCOM) | Conteudo: noticias, palestrantes, programacao, carrossel, galeria, patrocinadores |

## CMS

O painel administrativo (`/admin`) gerencia todo o conteudo do site:

- **Noticias** — CRUD com imagem, galeria, destaque para carrossel, campo autor opcional
- **Palestrantes** — foto, cargo, instituicao, redes sociais, ordem de exibicao
- **Programacao** — abas por dia, horario, local, descricao
- **Carrossel** — slides manuais + automaticos (noticias em destaque), fixacao de posicao
- **Album de fotos** — rotacao sequencial nos slots da home e pagina sobre
- **Apoio/Realizacao** — logos de parceiros com link e ordem
- **Configuracoes** — titulo, textos, redes sociais, dados do evento, Google Maps

Todos os itens possuem acoes de editar, ocultar/exibir e excluir.

## Producao

O site sera integrado ao **Plone** (CMS institucional gov.br). A estrutura HTML foi projetada para facilitar essa migracao, com areas de conteudo bem definidas e semantica limpa.

## Licenca

Template base: [Colorlib](https://colorlib.com/) "Event HTML-5". Manter atribuicao no footer conforme termos da licenca.

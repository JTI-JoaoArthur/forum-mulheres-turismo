# Requisitos de Hospedagem

**Projeto:** Fórum de Mulheres no Turismo — Ministério do Turismo / ONU Turismo  
**Tipo:** Site institucional com painel administrativo (CMS)  
**Stack:** PHP + SQLite (sem dependências externas)

---

## Linguagem e Runtime

| Requisito | Versao |
|-----------|--------|
| PHP | 8.0 ou superior (recomendado 8.1+) |

### Extensoes PHP obrigatorias

- `pdo_sqlite` — acesso ao banco de dados
- `mbstring` — manipulacao de strings UTF-8
- `fileinfo` — validacao de tipo de arquivo nos uploads
- `json` — encode/decode de dados
- `session` — sessoes de autenticacao

> Verificar com `php -m` se as extensoes estao habilitadas.

---

## Banco de Dados

- **SQLite 3** (incluso no PHP via `pdo_sqlite`)
- Nao e necessario instalar MySQL, PostgreSQL ou MariaDB
- O banco e um arquivo local em `admin/db/database.sqlite`
- Criado automaticamente no primeiro acesso a `/admin/setup.php`

---

## Servidor Web

### Opcao 1: Apache 2.4+ (recomendado)

- Modulo `mod_rewrite` habilitado
- Diretiva `AllowOverride All` no VirtualHost do site
- O projeto ja inclui `.htaccess` com regras de:
  - Redirecionamento HTTP para HTTPS
  - Bloqueio de acesso a pastas sensiveis (`admin/db/`, `admin/lib/`)
  - Cache de arquivos estaticos
  - Headers de seguranca

### Opcao 2: Nginx

- Sera necessario converter as regras do `.htaccess` para o bloco `server {}` do Nginx
- Regras principais: bloqueio de acesso a `/admin/db/` e redirecionamento HTTPS

---

## Permissoes de Escrita

As seguintes pastas precisam de permissao de escrita para o usuario do servidor web (www-data, apache, nginx):

| Pasta | Finalidade | Permissao |
|-------|-----------|-----------|
| `admin/db/` | Banco de dados SQLite | 755 ou 775 |
| `admin/uploads/` | Imagens e videos enviados pelo CMS | 755 ou 775 |
| `admin/uploads/news/` | Imagens de noticias | criada automaticamente |
| `admin/uploads/speakers/` | Fotos de palestrantes | criada automaticamente |
| `admin/uploads/gallery/` | Fotos do album | criada automaticamente |
| `admin/uploads/sponsors/` | Logos de apoiadores | criada automaticamente |
| `admin/uploads/carousel/` | Imagens do carrossel | criada automaticamente |

> As subpastas de `admin/uploads/` sao criadas automaticamente pelo sistema. Basta garantir escrita na pasta pai.

---

## HTTPS / SSL

- Certificado SSL obrigatorio
- O `.htaccess` ja forca redirecionamento HTTP -> HTTPS
- Portas: 80 (HTTP, redireciona) e 443 (HTTPS)

---

## Espaco em Disco

| Componente | Tamanho estimado |
|-----------|-----------------|
| Codigo-fonte + assets | ~15 MB |
| Banco SQLite | 1-5 MB (cresce com conteudo) |
| Uploads (imagens/videos) | Prever 500 MB - 1 GB |
| **Total recomendado** | **2 GB** |

---

## O que NAO e necessario

- MySQL / PostgreSQL / MariaDB
- Node.js / npm
- Composer
- Redis / Memcached
- Cron jobs
- Acesso SSH (util mas nao obrigatorio)

---

## Configuracao Inicial (pos-deploy)

1. Fazer upload de todos os arquivos para o diretorio raiz do subdominio
2. Garantir permissoes de escrita em `admin/db/` e `admin/uploads/`
3. Acessar `https://[subdominio]/admin/setup.php` no navegador
4. O setup cria o banco de dados e o usuario administrador inicial
5. Fazer login em `https://[subdominio]/admin/`
6. Alterar a senha padrao no primeiro acesso

---

## SMTP (opcional)

O formulario de contato do site necessita de SMTP para enviar e-mails.

- **Sem SMTP:** o formulario funciona mas as mensagens nao sao entregues por e-mail (ficam apenas registradas)
- **Com SMTP:** configurar relay SMTP no servidor (Postfix, Sendmail ou relay externo)
- Configuracoes de e-mail sao gerenciadas pelo CMS em Configuracoes > Formulario

---

## Contato Tecnico

Em caso de duvidas sobre a instalacao, entrar em contato com o desenvolvedor responsavel pelo projeto.

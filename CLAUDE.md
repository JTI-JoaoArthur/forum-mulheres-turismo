# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Site institucional para o **Fórum de Mulheres no Turismo** — evento realizado pelo Ministério do Turismo e ONU Turismo. Datas: 3 e 4 de Junho de 2026, Centro de Convenções de João Pessoa. Baseado no template Colorlib "Event HTML-5", adaptado com HTML/CSS/JS estático. Será integrado ao Plone (CMS institucional) — manter HTML semântico e áreas de conteúdo bem definidas.

## Development

```bash
python3 -m http.server 8000   # ou: npx serve . | php -S localhost:8000
```

O formulário de contato (`contact_process.php`) requer servidor PHP. Não há build, linter ou testes.

## Architecture

- **Pages**: `index.html` (home com hero/carrossel/countdown), `about.html`, `spakers.html` (palestrantes), `schedule.html` (programação), `blog.html`, `blog_details.html`, `contact.html`, `elements.html`.
- **Styling**: Bootstrap 4 + `assets/css/style.css` (tema original) + `assets/css/custom.css` (estilos do header fixo, hero, e customizações do evento). Ícones: FontAwesome, Themify Icons, Flaticon.
- **JS**: jQuery 1.12.4 + plugins (Slick, Owl Carousel, SlickNav, WOW.js, Magnific Popup, CounterUp). Homepage usa Swiper (CDN) com countdown inline.
- **Header unificado**: Todas as páginas usam o mesmo header com classe `.header-custom` (definido em `custom.css`). Duas logos (ONU Turismo à esquerda, Ministério do Turismo à direita) com `max-height: 45px` e `vertical-align: middle` para alinhamento perfeito.
- **Footer unificado**: Todas as páginas compartilham o mesmo footer com informações do evento.

## Key Conventions

- Conteúdo e comunicação em Português do Brasil (pt-BR).
- Header fixo (80px de altura), `body` com `padding-top: 80px` (via `custom.css`).
- Countdown aponta para `new Date("June 3, 2026 09:00:00")` em `index.html`.
- Template Colorlib — manter copyright no footer caso não haja licença.
- Pensado para CMS: estrutura HTML limpa para facilitar migração ao Plone (pré, durante e pós-evento).

- Comunique-se comigo e explique todas as alterações exclusivamente em Português do Brasil (pt-BR).

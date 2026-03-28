# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Site institucional para o **Fórum de Mulheres no Turismo** — evento realizado pelo Ministério do Turismo e ONU Turismo. Datas: 3 e 4 de Junho de 2026, Centro de Convenções de João Pessoa. Baseado no template Colorlib "Event HTML-5", adaptado com HTML/CSS/JS estático. Será integrado ao Plone (CMS institucional gov.br) — manter HTML semântico e áreas de conteúdo bem definidas.

## Development

```bash
python3 -m http.server 8000   # ou: npx serve . | php -S localhost:8000
```

O formulário de contato (`contact_process.php`) requer servidor PHP. Não há build, linter ou testes.

## Architecture

- **Pages**: `index.html` (home com hero/carrossel/countdown), `about.html`, `spakers.html` (palestrantes), `schedule.html` (programação), `blog.html` (listagem de notícias), `blog_details.html` (detalhe da notícia), `contact.html`.
- **Styling**: Bootstrap 4 + `assets/css/style.css` (tema original, cor padrão #64428c) + `assets/css/custom.css` (estilos do header fixo, hero, e customizações do evento). Ícones: FontAwesome, Themify Icons, Flaticon.
- **JS**: jQuery 1.12.4 + plugins (Slick, Owl Carousel, SlickNav, WOW.js, Magnific Popup, CounterUp). Homepage e about usam Magnific Popup para álbum de fotos com rotação sequencial. Homepage usa Swiper (CDN) com countdown inline.
- **Header unificado**: Todas as páginas usam o mesmo header com classe `.header-custom` (definido em `custom.css`). Duas logos (ONU Turismo à esquerda, Ministério do Turismo à direita) com `max-height: 45px` e `vertical-align: middle` para alinhamento perfeito.
- **Footer unificado**: Todas as páginas compartilham o mesmo footer com informações do evento (fixo, não editável via CMS).

## CMS (Plone)

Todas as seções de conteúdo são moduladas para integração com Plone:
- **Notícias** (antigo "Blog"): listagem em `blog.html`, detalhe em `blog_details.html`. Sem categorias nem comentários.
- **Palestrantes**: grid de 3 colunas, com campo de ordem para priorização.
- **Programação**: abas por dia (Dia 1, Dia 2), itens com nome, local, horário, descrição.
- **Carrossel de destaques**: slides automáticos (de notícias) ou manuais, com ordenação e fixação de posição.
- **Álbum de fotos**: alimentado por notícias + uploads diretos, rotação sequencial nos slots.
- **Apoio e Realização**: logos de apoiadores/realizadores, gerenciáveis individualmente.
- **Sobre o Evento**: fonte única no CMS, reflete em `index.html` (resumo) e `about.html` (completo).
- Todos os itens CMS possuem ações: editar, ocultar (visível: sim/não) e excluir.
- Itens default (classe `*-default`) desaparecem quando conteúdo real é inserido.

## Key Conventions

- Conteúdo e comunicação em Português do Brasil (pt-BR).
- Cor padrão do evento: `#64428c`.
- Header fixo (80px de altura), `body` com `padding-top: 80px` (via `custom.css`).
- Countdown aponta para `new Date("June 3, 2026 09:00:00")` em `index.html`.
- Template Colorlib — manter copyright no footer caso não haja licença.
- Pensado para CMS: estrutura HTML limpa para facilitar migração ao Plone (pré, durante e pós-evento).

- Comunique-se comigo e explique todas as alterações exclusivamente em Português do Brasil (pt-BR).

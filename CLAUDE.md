# PSP-Website — Contexto do Projeto

## Visão geral
Site estático da **PSP - Partes e Peças Automação** (filipe@pentasis.com.br).
Sem build process — deploy direto para hospedagem estática.

## Arquivos principais
| Arquivo | Função |
|---|---|
| `index.html` | Página única (~960 linhas) |
| `style.css` | Estilos com CSS variables |
| `script.js` | Classe `App` com toda a lógica |
| `manifest.json` | PWA básico |
| `robots.txt` / `sitemap.xml` | SEO/indexação |

## Stack
- Bootstrap 5.3, Font Awesome 6.4, AOS 2.3 (CDN)
- Google Fonts: Poppins + Montserrat
- FormSubmit.co (formulário sem backend) → filipe@pentasis.com.br
- Google Analytics GA4: `G-XQBJ002YQC` com Consent Mode v2 (LGPD)

## Estrutura da página
1. Navbar — Início | Sobre | Produtos | Contato + toggle dark mode
2. `#inicio` — Hero + contadores animados
3. Diferenciais — 4 cards
4. `#sobre` — Sobre Nós (texto placeholder, aguarda conteúdo real)
5. `#produtos` — 6 cards com filtro de categorias + modais de detalhe
6. `#contato` — Formulário + info de contato
7. Footer + botão WhatsApp flutuante + back-to-top

## Produtos e categorias
| Produto | `data-category` |
|---|---|
| Motoredutor Porta | `motorizacao` |
| Motoredutor de Tração | `motorizacao` |
| Mini Motor | `robotica` |
| Leitor Biométrico | `acesso` |
| Trava Eletrônica (Mitra) | `acesso` |
| Trava Eletrônica BLE | `bluetooth` |

## Classe App (script.js)
- `setupScrollHandlers()` — scroll spy + back-to-top unificados com `requestAnimationFrame`
- `setupThemeToggle()` — dark/light, persiste em `localStorage('psp_theme')`
- `setupProductFilter()` — filtra `.product-col` por `data-category`
- `setupLGPD()` — banner de cookies, persiste em `localStorage('psp_lgpd_consent')`
- `setupContactForm()` → `submitForm()` — fetch para FormSubmit.co
- `setupCounters()` — Intersection Observer nos `.stat-number`

## Dark mode
- Classe `body.dark-mode` (não media query)
- Anti-FOUC: script inline logo após `<body>` aplica a classe antes do primeiro render
- Fallback: `prefers-color-scheme` se não houver preferência salva

## CSS variables principais
```css
--primary: #274185
--accent: #0d6efd
--text-dark / --text-muted / --bg-light / --bg-white
--shadow-sm / --shadow-md / --radius: 12px
```

## Placeholders pendentes
- `SEU_DOMINIO.com.br` — em `og:url`, `og:image`, `canonical`, JSON-LD, `robots.txt`, `sitemap.xml`
- `SEU_NUMERO` — WhatsApp (2 ocorrências: botão hero + botão flutuante)
- Ícones PWA reais (192×192 e 512×512 PNG) para `manifest.json`
- Texto da seção "Sobre Nós" com história real da empresa

## Preferências
- Abordagem não agressiva: melhorar sem reescrever seções inteiras
- Mudanças centralizadas no CSS (não inline styles)
- Comunicação em português (pt-BR)

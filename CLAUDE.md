# PSP-Website — Contexto do Projeto

## Visão geral
Site estático da **PSP - Partes e Peças Automação** (filipe@pentasis.com.br).
Sem build process — deploy direto para hospedagem estática.
Em processo de evolução para e-commerce com pagamentos via **Mercado Pago** e **área admin PHP**.

## Arquivos principais
| Arquivo | Função |
|---|---|
| `index.html` | Página única (~1080 linhas) |
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
5. `#produtos` — 6 cards com filtro de categorias + modais de detalhe + **preços + botão Comprar**
6. `#contato` — Formulário + info de contato
7. Footer + botão WhatsApp flutuante + back-to-top
8. **Modal de Checkout** (`#checkoutModal`) — dados do comprador + controle de quantidade
9. **Modal de Redirecionamento MP** (`#paymentRedirectModal`) — spinner enquanto redireciona
10. **Lightbox de imagem** (`#lightboxOverlay`) — overlay customizado para ampliar imagens

## Produtos e categorias
| Produto | `data-category` | `data-product-id` | Preço exemplo |
|---|---|---|---|
| Motoredutor Porta | `motorizacao` | 1 | R$ 189,90 |
| Motoredutor de Tração | `motorizacao` | 2 | R$ 249,90 |
| Mini Motor | `robotica` | 3 | R$ 59,90 |
| Leitor Biométrico | `acesso` | 4 | R$ 179,90 |
| Trava Eletrônica (Mitra) | `acesso` | 5 | R$ 219,90 |
| Trava Eletrônica BLE | `bluetooth` | 6 | R$ 329,90 |

> **Os preços acima são exemplos para aprovação de layout.** Os valores reais serão definidos via área admin após integração com o banco de dados (Fase 1).

## Classe App (script.js)
- `setupScrollHandlers()` — scroll spy + back-to-top unificados com `requestAnimationFrame`
- `setupThemeToggle()` — dark/light, persiste em `localStorage('psp_theme')`
- `setupProductFilter()` — filtra `.product-col` por `data-category`
- `setupLGPD()` — banner de cookies, persiste em `localStorage('psp_lgpd_consent')`
- `setupContactForm()` → `submitForm()` — fetch para FormSubmit.co
- `setupCounters()` — Intersection Observer nos `.stat-number`
- `setupModalButtons()` — botão "Solicitar Orçamento" preenche formulário de contato
- `setupCheckout()` — abre modal de checkout ao clicar em `.btn-buy`, controla quantidade, valida campos inline, simula redirecionamento MP
- `setupImageLightbox()` — lightbox customizado para `img.card-img-top`, `img.carousel-img`, `img.modal-product-image`

## Fluxo de compra (frontend — sem backend ainda)
1. Clicar em **botão verde de carrinho** (card) ou **"Comprar Agora"** (modal de produto)
2. Modal de checkout abre com nome do produto e preço
3. Ajustar quantidade (−/+), preencher nome, e-mail e telefone
4. Clicar em **"Ir para pagamento"** → validação inline nos campos
5. Spinner "Redirecionando para o Mercado Pago..." por 2,5s
6. Mensagem "Em breve" (placeholder até integração real do MP)

## Lightbox de imagem
- Overlay customizado (`z-index: 9999`) — não usa Bootstrap Modal, evita conflito com modais abertos
- Abre ao clicar em `img.card-img-top`, `img.carousel-img` ou `img.modal-product-image`
- Fecha: clique no fundo, botão ✕ ou tecla `Escape`
- Cursor `zoom-in` nas imagens, `zoom-out` no fundo

## Validação de checkout
- Inline via classe `is-invalid` do Bootstrap + `<div class="invalid-feedback">` em cada campo
- **Nunca usar `showErrorModal()` dentro do checkout** — modal de erro abre atrás do modal de checkout
- Foco automático no primeiro campo inválido

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

## Roadmap de e-commerce (planejado)
| Fase | Descrição | Status |
|---|---|---|
| Fase 5 | Frontend — preços, checkout modal, lightbox | ✅ Concluído (aguarda aprovação interna) |
| Fase 1 | Banco de dados SQL Server (schema + setup.php) | Pendente |
| Fase 2 | Config PHP + API (products, payment, webhook) | Pendente |
| Fase 3 | Integração Mercado Pago Checkout Pro | Pendente |
| Fase 4 | Área Admin (dashboard, CRUD produtos, pedidos) | Pendente |

## Decisões técnicas
- **Backend:** PHP (familiaridade do desenvolvedor)
- **Banco de dados:** SQL Server (local, conexão PDO com driver SQLSRV)
- **Pagamentos:** Mercado Pago **Checkout Pro** (redirect), SDK `mercadopago/dx-php` via Composer
- **TypeScript:** descartado — JS puro suficiente para o escopo do projeto
- **Conta MP:** ainda não criada — credenciais serão adicionadas em `config/mercadopago.php`

## Placeholders pendentes
- `SEU_DOMINIO.com.br` — em `og:url`, `og:image`, `canonical`, JSON-LD, `robots.txt`, `sitemap.xml`
- `SEU_NUMERO` — WhatsApp (2 ocorrências: botão hero + botão flutuante)
- Ícones PWA reais (192×192 e 512×512 PNG) para `manifest.json`
- Texto da seção "Sobre Nós" com história real da empresa
- Preços reais dos produtos (definidos via admin após Fase 1)

## Preferências
- Abordagem não agressiva: melhorar sem reescrever seções inteiras
- Mudanças centralizadas no CSS (não inline styles)
- Comunicação em português (pt-BR)
- Sem commit até solicitação explícita

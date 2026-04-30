# PSPart-Website — Contexto do Projeto

## Visão geral
Site estático da **PSPart - Partes e Peças Automação** (filipe@pentasis.com.br).
Sem build process — deploy direto para hospedagem estática.
Em processo de evolução para e-commerce com pagamentos via **Mercado Pago** e **área admin PHP**.

## Arquivos principais
| Arquivo | Função |
|---|---|
| `index.html` | Página única (~1080 linhas) |
| `acompanhar.html` | Página de acompanhamento de pedido (standalone) |
| `style.css` | Estilos com CSS variables |
| `script.js` | Classe `App` com toda a lógica |
| `manifest.json` | PWA básico |
| `robots.txt` / `sitemap.xml` | SEO/indexação |

## Stack
- Bootstrap 5.3, Font Awesome 6.4, AOS 2.3 (CDN)
- Google Fonts: Poppins + Montserrat
- **marked.js** (CDN) — renderiza Markdown da Especificação Técnica no frontend
- FormSubmit.co (formulário sem backend) → filipe@pentasis.com.br
- Google Analytics GA4: `G-XQBJ002YQC` com Consent Mode v2 (LGPD)

## Estrutura da página
1. Navbar — Início | Sobre | Produtos | Contato + toggle dark mode
2. `#inicio` — Hero + contadores animados
3. Diferenciais — 4 cards
4. `#sobre` — Sobre Nós (texto placeholder, aguarda conteúdo real)
5. `#produtos` — cards dinâmicos (renderizados via API) + filtro de categorias + modais de detalhe
6. `#contato` — Formulário + info de contato
7. Footer + botão WhatsApp flutuante + back-to-top
8. **Modal de Checkout** (`#checkoutModal`, `modal-lg`) — dados do comprador + endereço com ViaCEP + controle de quantidade
9. **Modal de Redirecionamento MP** (`#paymentRedirectModal`) — spinner enquanto redireciona
10. **Lightbox de imagem** (`#lightboxOverlay`) — overlay customizado para ampliar imagens

## Página de Acompanhamento (`acompanhar.html`)
- Standalone — não depende de `index.html`
- **Busca por token** (link do e-mail): `acompanhar.html?pedido=X&token=abc123`
- **Busca manual**: formulário com pedido_id + e-mail
- Exibe: status badge, timeline visual (4 etapas), itens do pedido, endereço, dados do comprador
- API: `GET /backend/api/acompanhar.php?token=...` ou `?pedido_id=X&email=Y`

## Produtos e categorias
| Produto | `data-category` | `data-product-id` | Preço exemplo |
|---|---|---|---|
| Motoredutor Porta | `motorizacao` | 1 | R$ 189,90 |
| Motoredutor de Tração | `motorizacao` | 2 | R$ 249,90 |
| Mini Motor | `robotica` | 3 | R$ 59,90 |
| Leitor Biométrico | `acesso` | 4 | R$ 179,90 |
| Trava Eletrônica (Mitra) | `acesso` | 5 | R$ 219,90 |
| Trava Eletrônica BLE | `bluetooth` | 6 | R$ 329,90 |

> **Os preços acima são exemplos para aprovação de layout.** Os valores reais serão definidos via área admin após integração com o banco de dados.

## E-mails transacionais (`backend/helpers/email.php`)
- `emailPedidoCriado($pedido, $itens, $token)` — disparado em `pedidos.php` ao criar o pedido; inclui link de acompanhamento
- `emailPagamentoAprovado($pedido, $itens, $token)` — disparado em `webhook.php` na primeira transição para `aprovado`
- Envio via `mail()` nativo do PHP — **não funciona em localhost**, funciona em hospedagem compartilhada
- Logs de envio em `logs/emails.log`
- `MP_BASE_URL` (de `mercadopago.php`) é usado como base dos links nos e-mails

## Classe App (script.js)
- `setupScrollHandlers()` — scroll spy + back-to-top unificados com `requestAnimationFrame`
- `setupThemeToggle()` — dark/light, persiste em `localStorage('psp_theme')`
- `setupProductFilter()` — filtra `.product-col` por `data-category`; chamado após `renderProducts()`
- `setupLGPD()` — banner de cookies, persiste em `localStorage('psp_lgpd_consent')`
- `setupContactForm()` → `submitForm()` — fetch para FormSubmit.co
- `setupCounters()` — Intersection Observer nos `.stat-number`
- `setupModalButtons()` — botão "Solicitar Orçamento" preenche formulário de contato
- `setupCheckout()` — checkout completo: dados pessoais + endereço ViaCEP, valida inline, grava pedido, redireciona para MP via `init_point`
- `setupImageLightbox()` — lightbox customizado para `img.card-img-top`, `img.carousel-img`, `img.modal-product-image`
- `renderProducts()` — busca produtos da API, gera cards e modais dinamicamente em `#products-grid` / `#product-modals`; desabilita botão se estoque = 0
- `_buscarCep()` — consulta ViaCEP e preenche campos de endereço automaticamente

## Fluxo de compra
1. Clicar em **botão verde de carrinho** (card) ou **"Comprar Agora"** (modal de produto)
2. Modal de checkout (`modal-lg`) abre com resumo do produto + controle de quantidade
3. Preencher: nome, e-mail, telefone, CEP (auto-preenche endereço via ViaCEP), número, complemento (opcional)
4. Clicar em **"Ir para pagamento"** → validação inline (campos obrigatórios: nome, e-mail, telefone, CEP, número)
5. Spinner de redirecionamento → `window.location.href = mp.init_point`
6. Pagamento processado no Mercado Pago → webhook atualiza status do pedido no banco

## Lightbox de imagem
- Overlay customizado (`z-index: 9999`) — não usa Bootstrap Modal, evita conflito com modais abertos
- Abre ao clicar em `img.card-img-top`, `img.carousel-img` ou `img.modal-product-image`
- Fecha: clique no fundo, botão ✕ ou tecla `Escape`
- Cursor `zoom-in` nas imagens, `zoom-out` no fundo

## Validação de checkout
- Inline via classe `is-invalid` do Bootstrap + `<div class="invalid-feedback">` em cada campo
- **Nunca usar `showErrorModal()` dentro do checkout** — modal de erro abre atrás do modal de checkout
- Foco automático no primeiro campo inválido
- Erros da API exibidos em `#checkout-error-msg` (alerta no topo do modal, some após 6s)
- Produto com `estoque = 0` tem botão desabilitado ("Fora de estoque") — não abre checkout

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

## Schema do banco (SQLite — `database.db`)

### Tabela `produtos`
| Coluna | Tipo | Observação |
|---|---|---|
| id | INTEGER PK | |
| nome | TEXT NOT NULL | |
| descricao | TEXT | |
| preco | REAL | |
| categoria | TEXT | motorizacao / robotica / acesso / bluetooth |
| imagem | TEXT | campo legado — fallback quando `produto_imagens` estiver vazio |
| estoque | INTEGER | |
| ativo | INTEGER | 0 ou 1 |
| codigo_interno | TEXT | formato `SE.02.00002` — único, opcional |
| datasheet | TEXT | caminho relativo ex: `docs/ds_1_xxx.pdf` |
| especificacao_tecnica | TEXT | conteúdo em Markdown |
| criado_em | TEXT | |

### Tabela `produto_imagens`
| Coluna | Tipo | Observação |
|---|---|---|
| id | INTEGER PK | |
| produto_id | INTEGER FK | ON DELETE CASCADE |
| caminho | TEXT | ex: `img/prod_1_xxx.png` |
| ordem | INTEGER | usado para ordenação (drag-and-drop no admin) |
| principal | INTEGER | 1 = imagem principal (thumbnail) |
| criado_em | TEXT | |

### Tabela `pedidos`
- id, nome/email/telefone_comprador, endereço completo, total, status, mp_preferencia_id, token_acompanhamento, criado_em

### Tabela `itens_pedido`
- id, pedido_id (CASCADE), produto_id (RESTRICT), quantidade, preco_unitario

### Tabela `usuarios`
- id, nome, email (UNIQUE), senha_hash, ativo

## Módulo Admin de Produtos

### Arquivos admin relevantes
| Arquivo | Função |
|---|---|
| `backend/admin/produtos.php` | Listagem — usa subquery para pegar imagem principal de `produto_imagens` com fallback para `imagem` legado; cache busting via `?v=filemtime()` |
| `backend/admin/produto-novo.php` | Criação em duas fases na mesma página — fase 1: formulário; fase 2 (após salvar): seção de imagens múltiplas com AJAX + SortableJS |
| `backend/admin/produto-editar.php` | Edição — inclui EasyMDE + gerenciamento de imagens múltiplas via AJAX |
| `backend/admin/ajax-imagens.php` | AJAX para imagens: `upload`, `set-principal`, `reorder`, `delete` |
| `backend/admin/_layout.php` | Layout base — aceita `$extra_head` como segundo parâmetro para injetar CSS no `<head>` |

### Fluxo de produto-novo.php
Página em duas fases sem redirect entre elas:
- **Fase 1 (GET ou POST com erro):** formulário completo com todos os campos, incluindo EasyMDE para especificação técnica
- **Fase 2 (POST com sucesso):** `$produtoCriado` recebe o `lastInsertId()`; o formulário é substituído por banner de confirmação + seção de imagens idêntica à de editar; links "Criar outro produto" e "Ver todos os produtos"
- EasyMDE só é carregado na fase 1 (não desperdiça CDN na fase 2)

### Campo Código Interno
- Formato fixo: `SE.02.00002` (2 letras maiúsculas + ponto + 2 dígitos + ponto + 5 dígitos)
- Regex backend: `/^[A-Z]{2}\.\d{2}\.\d{5}$/`
- Validação de unicidade via SELECT antes de INSERT/UPDATE
- Auto-uppercase no input; validação visual `is-invalid` no blur

### Upload de Data Sheet (PDF)
- Armazenado em `docs/ds_{produto_id}_{uniqid}.pdf`
- Diretório `docs/` criado automaticamente na primeira vez
- Validação MIME via `finfo` + limite de 10 MB
- Admin: visualizar PDF atual, remover (checkbox) ou substituir
- Frontend: botão "Data Sheet" no footer do modal de produto

### Múltiplas Imagens (`produto_imagens`)
- AJAX via `backend/admin/ajax-imagens.php`
- Drag-and-drop para reordenar usando **SortableJS** (CDN)
- Primeira imagem enviada é automaticamente a principal (estrela dourada)
- Ao deletar a principal, a próxima na ordem assume automaticamente
- Validação MIME real (`finfo`) + limite 5 MB por imagem
- Imagens armazenadas em `img/prod_{produto_id}_{uniqid}.{ext}`
- **Importante**: ao deletar um produto via `produto-excluir.php`, os registros em `produto_imagens` são removidos por CASCADE, mas os arquivos físicos em `img/` ficam órfãos (não são deletados automaticamente)

### Especificação Técnica
- Campo Markdown editado com **EasyMDE** (CDN) no admin
- Toolbar: negrito, itálico, listas, preview
- Renderizado com **marked.js** no frontend dentro do modal de detalhes do produto
- Campo opcional (NULL permitido no banco)

### API de Produtos (`backend/api/produtos.php`)
Retorna por produto: `id`, `nome`, `descricao`, `preco`, `categoria`, `imagem`, `estoque`, `codigo_interno`, `datasheet`, `especificacao_tecnica`, `imagens[]`

O array `imagens` contém objetos `{caminho, ordem, principal}` ordenados por `ordem ASC`.

### renderProducts() — lógica de imagem no frontend
```javascript
// Prioridade: imagem principal de produto_imagens → qualquer imagem do array → campo legado imagem
const imgPrincipal = imagens.find(img => img.principal) || imagens[0];
const imgSrc = imgPrincipal ? imgPrincipal.caminho : (p.imagem || '');

// Modal: carousel Bootstrap se imagens.length > 1, imagem simples se = 1
```

## Roadmap de e-commerce (planejado)
| Fase | Descrição | Status |
|---|---|---|
| Fase 5 | Frontend — preços, checkout modal, lightbox | ✅ Concluído |
| Fase 1 | Banco de dados SQLite (schema + setup.php) | ✅ Concluído |
| Fase 2 | Backend PHP + API (produtos, pedidos, pagamento stub, webhook stub) | ✅ Concluído |
| Fase 3 | Integração Mercado Pago Checkout Pro | ✅ Concluído |
| Fase 4 | Área Admin (dashboard, CRUD produtos, pedidos, login) | ✅ Concluído |
| Fase 6 | Acompanhamento de pedido — página + e-mails transacionais | ✅ Concluído |
| Fase 7 | Admin produtos: código interno, data sheet, múltiplas imagens, especificação técnica | ✅ Concluído |

## Decisões técnicas
- **Backend:** PHP (familiaridade do desenvolvedor)
- **Banco de dados:** SQLite (arquivo `.db` junto ao projeto, PDO nativo, sem driver extra, compatível com hospedagem compartilhada)
- **Pagamentos:** Mercado Pago **Checkout Pro** (redirect), SDK `mercadopago/dx-php` via Composer
- **TypeScript:** descartado — JS puro suficiente para o escopo do projeto
- **SQL Server:** descartado — requer driver `pdo_sqlsrv` e servidor dedicado, inviável em hospedagem compartilhada
- **`init_point` em vez de `sandbox_init_point`:** evita `ERR_TOO_MANY_REDIRECTS` no subdomínio `sandbox.mercadopago.com.br`; com credenciais de teste o pagamento ainda é processado como teste
- **`auto_return` removido:** causava loop de redirect no sandbox do MP
- **Webhook:** suporta formato v1 (`type/data.id`) e formato IPN antigo (`topic/resource`) — MP pode enviar qualquer um dos dois
- **E-mail via `mail()` nativo:** sem dependência externa; compatível com hospedagem compartilhada; não funciona em localhost
- **Token de acompanhamento:** `bin2hex(random_bytes(16))` — 32 chars hex, gerado na criação do pedido, armazenado em `pedidos.token_acompanhamento`; permite acesso direto sem login
- **PIX em sandbox:** não aparece no Checkout Pro de teste — limitação do MP; validar em produção com R$ 0,01
- **Campo `imagem` (legado):** mantido na tabela `produtos` para compatibilidade com produtos do seed; não é mais editável pelo admin — gerenciamento exclusivo via `produto_imagens`
- **Cache busting de imagens no admin:** `?v=filemtime()` — parâmetro muda apenas quando o arquivo no disco é alterado
- **EasyMDE + marked.js:** usados respectivamente no admin (edição Markdown) e no frontend (renderização); ambos via CDN, sem build process
- **SortableJS:** drag-and-drop para reordenação de imagens no admin; CDN carregado em `produto-editar.php` e na fase 2 de `produto-novo.php`
- **Migrations via sqlite3 CLI:** colunas adicionadas após o setup inicial (`especificacao_tecnica`) foram aplicadas diretamente com `sqlite3 database.db "ALTER TABLE produtos ADD COLUMN ..."` — não requerem re-executar setup.php

## Placeholders pendentes (necessários antes do deploy)
- `SEU_NUMERO` — WhatsApp (2 ocorrências: botão hero + botão flutuante)
- Ícones PWA reais (192×192 e 512×512 PNG) para `manifest.json`
- Texto da seção "Sobre Nós" com história real da empresa
- Preços reais dos produtos (definidos via admin)
- Credenciais de produção do MP em `backend/config/mercadopago.php` (trocar tokens TEST- pelos de produção)
- `MP_BASE_URL` atualizado com domínio real (sem ngrok)
- Senha do admin trocada (padrão: `admin123`)
- `setup.php` removido ou bloqueado após deploy

## Acessos do ambiente de desenvolvimento

### Servidor local
```bash
cd "D:\Aréa de Trabalho\DEV\PSP-Website"
php -S localhost:8000
```

| URL | Descrição |
|---|---|
| `http://localhost:8000` | Site principal |
| `http://localhost:8000/setup.php` | Setup do banco (senha: `psp@setup2025`) |
| `http://localhost:8000/backend/admin/` | Área administrativa |

### Admin
| Campo | Valor |
|---|---|
| E-mail | `admin@pspart.com.br` |
| Senha | `admin123` |
| Alterar senha | Menu lateral → **Senha** |

### ngrok (Mercado Pago em dev local)
O Mercado Pago exige uma URL pública acessível para `back_urls` e `notification_url` do webhook. Em desenvolvimento local, usar ngrok para expor o servidor.

**Passos:**
1. Subir o servidor: `php -S localhost:8000`
2. Em outro terminal: `ngrok http 8000`
3. Copiar a URL HTTPS gerada (ex: `https://xxxx.ngrok-free.app`)
4. Atualizar `MP_BASE_URL` em `backend/config/mercadopago.php`:
```php
define('MP_BASE_URL', 'https://xxxx.ngrok-free.app');
```

> A URL muda a cada vez que o ngrok é reiniciado — lembrar de atualizar o arquivo.
> Conta ngrok configurada para: Filipe Rodrigues dos Santos (Plan: Free)

### Banco de dados
- Arquivo: `database.db` na raiz do projeto
- Visualizar: abrir no **DB Browser for SQLite**

## Preferências
- Abordagem não agressiva: melhorar sem reescrever seções inteiras
- Mudanças centralizadas no CSS (não inline styles)
- Comunicação em português (pt-BR)
- Sem commit até solicitação explícita

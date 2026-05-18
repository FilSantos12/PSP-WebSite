# PSPart-Website — Contexto do Projeto

## Visão geral
Site estático da **PSPart - Partes e Peças Automação** (filipe@pentasis.com.br).
Sem build process — deploy direto para hospedagem estática.
Em processo de evolução para e-commerce com pagamentos via **Mercado Pago** e **área admin PHP**.

## Arquivos principais
| Arquivo | Função |
|---|---|
| `index.html` | Página única |
| `acompanhar.html` | Página de acompanhamento de pedido (standalone, acesso só por token) |
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
5. `#produtos` — cards dinâmicos (renderizados via API) + filtro de categorias dinâmico + modais de detalhe
6. `#contato` — Formulário + info de contato
7. Footer + botão WhatsApp flutuante + back-to-top
8. **Modal de Checkout** (`#checkoutModal`, `modal-lg`) — dados do comprador + endereço com ViaCEP + controle de quantidade
9. **Modal de Redirecionamento MP** (`#paymentRedirectModal`) — spinner enquanto redireciona
10. **Lightbox de imagem** (`#lightboxOverlay`) — overlay customizado para ampliar imagens

## Página de Acompanhamento (`acompanhar.html`)
- Standalone — não depende de `index.html`
- **Acesso via token**: `acompanhar.html?token=abc123` ou `acompanhar.html?pedido=X&token=abc123`
- **Formulário de busca manual**: quando não há token na URL, exibe form com `pedido_id` + e-mail; ao submeter chama `buscarManual()` que chama `buscar({ pedido_id, email })`
- **Timeline unificada de 6 etapas** (`#unifiedTimeline`):
  1. Pedido Recebido — sempre ativo
  2. Pagamento Confirmado — ativo se status `aprovado/em_processamento` (erro se `recusado/cancelado/reembolsado/contestado`)
  3. Em Preparação — controlado por `order_tracking.status >= 0`
  4. Embalado — `order_tracking.status >= 1`
  5. Enviado — `order_tracking.status >= 2`
  6. Código de Rastreio — `order_tracking.status >= 3` (exibe código e link dos Correios)
- Exibe: status badge, timeline, itens do pedido, endereço, dados do comprador
- **CSS bug crítico**: nunca esconder seções via stylesheet (`#id { display:none }`) — JS usa `element.style.display = ''` que não sobrescreve regras CSS. Usar `style="display:none;"` inline no HTML para que o JS consiga mostrar/esconder.
- API: `GET /backend/api/acompanhar.php?token=...` ou `?pedido_id=X&email=Y`

## Categorias
- Gerenciadas na tabela `categorias` do banco — **não são mais hardcoded**
- Categorias iniciais: `motorizacao`, `robotica`, `acesso`, `bluetooth`
- Admin: `backend/admin/categorias.php` — criar, listar, excluir (com proteção: não exclui se há produtos vinculados)
- API pública: `GET /backend/api/categorias.php` — retorna `[{slug, nome}]` usada pelo frontend
- Os selects de categoria em `produto-novo.php` e `produto-editar.php` carregam do banco automaticamente
- Novos filtros do catálogo e badges de produto refletem novas categorias sem alterar código

## E-mails transacionais (`backend/helpers/email.php`)
- `emailPedidoCriado($pedido, $itens, $token)` — disparado em `pedidos.php` ao criar o pedido; inclui botão "Acompanhar Pedido" com link `{MP_BASE_URL}/acompanhar.html?pedido={id}&token={token}`
- `emailPagamentoAprovado($pedido, $itens, $token)` — disparado em `webhook.php` e `processar-pagamento.php` na primeira transição para `aprovado`; inclui mesmo link de acompanhamento
- `emailPedidoEnviado()` — existe no código mas não é mais chamado (status 'enviado' removido do fluxo)
- Envio via `mail()` nativo do PHP — **não funciona em localhost** (DEV-SKIP no log), funciona em hospedagem compartilhada
- Logs de envio em `logs/emails.log`
- `MP_BASE_URL` (de `mercadopago.php`) é usado como base dos links nos e-mails

## Classe App (script.js)
- `setupScrollHandlers()` — scroll spy + back-to-top unificados com `requestAnimationFrame`
- `setupThemeToggle()` — dark/light, persiste em `localStorage('psp_theme')`
- `setupProductFilter()` — registra listener com **event delegation** no `#product-filters`; consulta `.product-col` no momento do clique (sem NodeList stale); chamado uma vez em `init()`
- `setupLGPD()` — banner de cookies, persiste em `localStorage('psp_lgpd_consent')`
- `setupContactForm()` → `submitForm()` — fetch para FormSubmit.co
- `setupCounters()` — Intersection Observer nos `.stat-number`
- `setupModalButtons()` — botão "Solicitar Orçamento" preenche formulário de contato
- `setupCheckout()` — registra listeners dos dois botões de pagamento (redirect e Bricks), link de fallback redirect dentro do Brick, e limpeza do Brick ao fechar o modal
- `setupImageLightbox()` — lightbox customizado para `img.card-img-top`, `img.carousel-img`, `img.modal-product-image`
- `renderProducts()` — busca categorias e produtos em paralelo (`Promise.all`); gera botões de filtro dinamicamente em `#product-filters`; gera cards e modais em `#products-grid` / `#product-modals`; desabilita botão se estoque = 0
- `handleRetornoMP()` — detecta `?pagamento=recusado|pendente` na URL após retorno do MP; limpa o param com `history.replaceState`; exibe modal apropriado
- `_validateCheckoutForm()` — valida campos obrigatórios com `is-invalid`, foca no primeiro inválido
- `_doCheckoutSubmit(mode)` — fluxo unificado: valida, grava pedido, cria preference; bifurca por `mode`: `'redirect'` abre `paymentRedirectModal` e redireciona; `'bricks'` esconde form/footer e renderiza o Brick
- `_renderBrick(preferenceId, email, amount)` — busca Public Key em `public-config.php`, inicializa `MercadoPago` SDK com locale pt-BR e tema dark/light; cria Payment Brick no `#bricks-container`; no `onSubmit` chama `processar-pagamento.php` e exibe `_showBricksResult()`
- `_showBricksResult(result)` — fecha o modal de checkout e abre `#bricksResultModal` com ícone, mensagem personalizada (nome + e-mail do comprador) e botão "Acompanhar pedido" (apenas para `approved`)
- `_unmountBrick()` — desmonta instância do Brick, reseta estado (`_bricksInstance`, `_currentPedidoId`, `_currentToken`, `_currentInitPoint`), restaura visibilidade do form e footer
- `_openCheckoutModal()` — chama `_unmountBrick()` antes de abrir para garantir estado limpo
- `_buscarCep()` — consulta ViaCEP e preenche campos de endereço automaticamente

## Fluxo de compra
1. Clicar em **botão verde de carrinho** (card) ou **"Comprar Agora"** (modal de produto)
2. Modal de checkout (`modal-lg`) abre com resumo do produto + controle de quantidade
3. Preencher: nome, e-mail, telefone, CEP (auto-preenche via ViaCEP), número, complemento (opcional)
4. Validação inline (campos obrigatórios: nome, e-mail, telefone, CEP, número)
5. Escolher forma de pagamento:

**Opção A — Pagar no Mercado Pago (redirect)**
- Spinner de redirecionamento → `window.location.href = mp.init_point`
- Pagamento processado no site do MP → webhook atualiza status no banco
- `aprovado` → redireciona para `acompanhar.html?pedido=X&token=Y`
- `recusado` → homepage com modal de erro
- `pendente` → homepage com modal informando análise

**Opção B — Pagar aqui mesmo (Checkout Bricks)**
- Form e footer somem; Payment Brick renderiza inline (cartão, débito, PIX, boleto)
- Usuário preenche dados de pagamento no próprio site
- `onSubmit` do Brick chama `processar-pagamento.php` → MP Payments API
- Resultado exibido em `#bricksResultModal` com nome e e-mail do comprador:
  - `approved` → ✅ "Pagamento aprovado!" + botão "Acompanhar pedido"
  - `pending` → ⏳ "Pagamento em análise" + instrução de aguardar e-mail
- Link "Pagar pelo site do MP" dentro do Brick permite trocar para Opção A sem reiniciar

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
| categoria | TEXT | slug da tabela `categorias` |
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

### Tabela `categorias`
| Coluna | Tipo | Observação |
|---|---|---|
| id | INTEGER PK | |
| slug | TEXT UNIQUE | chave usada em `produtos.categoria` e `data-category` no frontend |
| nome | TEXT | label exibido nos filtros e badges |
| ordem | INTEGER | ordem de exibição dos botões de filtro |
| criado_em | TEXT | |

> Migration inicial: acessar `migrate-categorias.php` uma vez via browser e apagar o arquivo.

### Tabela `pedidos`
- id, nome/email/telefone_comprador, endereço completo, total, status, mp_preferencia_id, token_acompanhamento, criado_em
- Status válidos: `pendente`, `aprovado`, `em_analise`, `recusado`, `cancelado`, `reembolsado`, `contestado`, `em_processamento` — **`enviado` foi removido** (rastreamento físico é gerenciado exclusivamente em `tracking-admin.php`)

### Tabela `order_tracking`
| Coluna | Tipo | Observação |
|---|---|---|
| order_id | TEXT UNIQUE | FK para `pedidos.id` (como string) |
| status | INTEGER | 0 = Em Preparação, 1 = Embalado, 2 = Enviado, 3 = Código de Rastreio |
| tracking_code | TEXT | Código dos Correios (formato `AA000000000BR`) |
| carrier | TEXT | Transportadora |
| notes | TEXT | Observações internas |
| updated_at | TEXT | Timestamp no fuso de Brasília |

- Criada automaticamente via `INSERT OR IGNORE` com `status = 0` quando o pagamento é aprovado (em `webhook.php` e `processar-pagamento.php`)
- Gerenciada exclusivamente em `backend/admin/tracking-admin.php`
- API pública: `GET /backend/api/tracking.php?order_id=X`

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
| `backend/admin/categorias.php` | CRUD de categorias — listar com contagem de produtos, criar (auto-slug), excluir (bloqueado se há produtos vinculados) |
| `backend/admin/_layout.php` | Layout base — aceita `$extra_head` como segundo parâmetro para injetar CSS no `<head>` |

### Fluxo de produto-novo.php
Página em duas fases sem redirect entre elas:
- **Fase 1 (GET ou POST com erro):** formulário completo com todos os campos, incluindo EasyMDE para especificação técnica
- **Fase 2 (POST com sucesso):** `$produtoCriado` recebe o `lastInsertId()`; o formulário é substituído por banner de confirmação + seção de imagens idêntica à de editar; links "Criar outro produto" e "Ver todos os produtos"
- EasyMDE só é carregado na fase 1 (não desperdiça CDN na fase 2)
- Imagem enviada pelo formulário é automaticamente inserida em `produto_imagens` com `principal = 1`

### Campo Código Interno
- Formato fixo: `SE.02.00002` (2 letras maiúsculas + ponto + 2 dígitos + ponto + 5 dígitos)
- Regex backend: `/^[A-Z]{2}\.\d{2}\.\d{5}$/`
- Validação de unicidade via SELECT antes de INSERT/UPDATE
- Auto-uppercase no input; validação visual `is-invalid` no blur

### Upload de Data Sheet (PDF)
- Armazenado em `docs/ds_{produto_id}_{uniqid}.pdf`
- Diretório `docs/` criado automaticamente na primeira vez
- Validação via **magic bytes** (`%PDF` nos primeiros 4 bytes) — mais confiável que `finfo` no Windows
- Erros de upload reportados com mensagem descritiva por código (`UPLOAD_ERR_INI_SIZE`, etc.)
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

### renderProducts() — lógica de imagem e filtros no frontend
```javascript
// Busca categorias e produtos em paralelo
const [prodRes, catRes] = await Promise.all([
    fetch('backend/api/produtos.php'),
    fetch('backend/api/categorias.php'),
]);

// Prioridade de imagem: imagem principal de produto_imagens → qualquer imagem do array → campo legado
const imgPrincipal = imagens.find(img => img.principal) || imagens[0];
const imgSrc = imgPrincipal ? imgPrincipal.caminho : (p.imagem || '');

// Modal: carousel Bootstrap se imagens.length > 1, imagem simples se = 1

// Filtros: botões gerados dinamicamente em #product-filters a partir de categorias[]
// setupProductFilter() usa event delegation — um listener no container, não por botão
```

## Roadmap de e-commerce
| Fase | Descrição | Status |
|---|---|---|
| Fase 1 | Banco de dados SQLite (schema + setup.php) | ✅ Concluído |
| Fase 2 | Backend PHP + API (produtos, pedidos, pagamento stub, webhook stub) | ✅ Concluído |
| Fase 3 | Integração Mercado Pago Checkout Pro | ✅ Concluído |
| Fase 4 | Área Admin (dashboard, CRUD produtos, pedidos, login) | ✅ Concluído |
| Fase 5 | Frontend — preços, checkout modal, lightbox | ✅ Concluído |
| Fase 6 | Acompanhamento de pedido — página + e-mails transacionais | ✅ Concluído |
| Fase 7 | Admin produtos: código interno, data sheet, múltiplas imagens, especificação técnica | ✅ Concluído |
| Fase 8 | Gestão de categorias + filtros dinâmicos + retorno MP pós-pagamento | ✅ Concluído |
| Fase 9 | Checkout Bricks (pagamento inline) + modal pós-pagamento + simplificação de acompanhamento | ✅ Concluído |
| Fase 10 | Rastreamento de entrega (order_tracking) + timeline unificada + correção de timezone | ✅ Concluído |

## Módulo Admin de Pedidos

- `backend/admin/pedidos.php` — listagem com filtro de status, busca por nome/e-mail
- `backend/admin/pedido-detalhe.php` — exibe dados do comprador, itens e status; permite atualizar apenas o status do pedido; **não gerencia rastreamento** (link para `tracking-admin.php`)
- `backend/admin/tracking-admin.php` — única interface para atualizar `order_tracking` (status de envio, código de rastreio, transportadora, notas)

## Timezone

- **Fuso horário:** `America/Sao_Paulo` em todas as camadas PHP
- `backend/api/_core.php` — `date_default_timezone_set('America/Sao_Paulo')` para todos os endpoints da API
- `backend/admin/_auth.php` — `date_default_timezone_set('America/Sao_Paulo')` para todas as páginas admin
- **`CURRENT_TIMESTAMP` do SQLite é sempre UTC** — todos os inserts/updates que gravam timestamps usam PHP `date('Y-m-d H:i:s')` como parâmetro PDO (não `CURRENT_TIMESTAMP`) para garantir o fuso correto
- `criado_em` de pedidos: passado explicitamente no INSERT de `pedidos.php`
- `updated_at` de `order_tracking`: passado como `:now` em `tracking.php`, `webhook.php` e `processar-pagamento.php`

## Módulo de Pagamento — Arquivos Backend

| Arquivo | Função |
|---|---|
| `backend/api/pagamento.php` | Cria preference no MP (usada por ambos os modos); retorna `preference_id` e `init_point` |
| `backend/api/processar-pagamento.php` | Processa pagamento via MP Payments API (modo Bricks); recebe `{ pedido_id, form_data }` do frontend; `transaction_amount` sempre vem do banco (não do cliente) |
| `backend/api/public-config.php` | Retorna `{ mp_public_key }` para o frontend inicializar o SDK do MP com segurança |

### SDK do MP no frontend
- Carregado via CDN: `https://sdk.mercadopago.com/js/v2`
- Inicializado em `_renderBrick()` com a Public Key de teste (`backend/api/public-config.php`)
- Tema automático: `dark` se `body.dark-mode` estiver ativo, `default` caso contrário
- Locale: `pt-BR`

### Credenciais do MP (desenvolvimento vs produção)
- **Teste:** Public Key e Access Token do app de teste do MP (`APP_USR-` ou `TEST-`)
- **Produção:** trocar ambas as chaves em `backend/config/mercadopago.php` antes do deploy
- **Importante:** Public Key e Access Token devem ser do **mesmo ambiente** (ambas teste ou ambas produção) — misturar resulta em erro 401 `"Unauthorized use of live credentials"`
- **ngrok em Windows:** antivírus com inspeção SSL (Kaspersky, Avast, Bitdefender etc.) bloqueia a autenticação do ngrok com erro `x509: certificate is not valid for any names` — desativar inspeção SSL ou o antivírus temporariamente durante desenvolvimento

## Decisões técnicas
- **Backend:** PHP (familiaridade do desenvolvedor)
- **Banco de dados:** SQLite (arquivo `.db` junto ao projeto, PDO nativo, sem driver extra, compatível com hospedagem compartilhada)
- **Pagamentos:** Mercado Pago **Checkout Pro** (redirect) + **Checkout Bricks** (inline), SDK `mercadopago/dx-php` via Composer
- **TypeScript:** descartado — JS puro suficiente para o escopo do projeto
- **SQL Server:** descartado — requer driver `pdo_sqlsrv` e servidor dedicado, inviável em hospedagem compartilhada
- **`init_point` em vez de `sandbox_init_point`:** evita `ERR_TOO_MANY_REDIRECTS` no subdomínio `sandbox.mercadopago.com.br`; com credenciais de teste o pagamento ainda é processado como teste
- **`auto_return` removido:** causava loop de redirect no sandbox do MP
- **`back_url.success` aponta para `acompanhar.html`:** token incluído na URL para o usuário ver o pedido sem login imediatamente após pagar
- **Webhook:** suporta formato v1 (`type/data.id`) e formato IPN antigo (`topic/resource`) — MP pode enviar qualquer um dos dois
- **E-mail via `mail()` nativo:** sem dependência externa; compatível com hospedagem compartilhada; não funciona em localhost
- **Token de acompanhamento:** `bin2hex(random_bytes(16))` — 32 chars hex, gerado na criação do pedido, armazenado em `pedidos.token_acompanhamento`; permite acesso direto sem login
- **PIX em sandbox:** não aparece no Checkout Pro de teste — limitação do MP; validar em produção com R$ 0,01
- **Campo `imagem` (legado):** mantido na tabela `produtos` para compatibilidade com produtos do seed; não é mais editável pelo admin — gerenciamento exclusivo via `produto_imagens`
- **Cache busting de imagens no admin:** `?v=filemtime()` — parâmetro muda apenas quando o arquivo no disco é alterado
- **EasyMDE + marked.js:** usados respectivamente no admin (edição Markdown) e no frontend (renderização); ambos via CDN, sem build process
- **SortableJS:** drag-and-drop para reordenação de imagens no admin; CDN carregado em `produto-editar.php` e na fase 2 de `produto-novo.php`
- **Validação de PDF por magic bytes:** `finfo_open` retorna MIME incorreto em algumas builds PHP/Windows; substituído por leitura dos primeiros 4 bytes (`%PDF`)
- **Categorias dinâmicas:** tabela `categorias` é a fonte de verdade; filtros do catálogo e selects do admin sempre refletem o banco sem alteração de código
- **Filtro com event delegation:** `setupProductFilter()` usa um único listener no container `#product-filters` e consulta `.product-col` no momento do clique — evita NodeList stale após re-render
- **Migrations via sqlite3 CLI:** colunas adicionadas após o setup inicial foram aplicadas diretamente com `sqlite3 database.db "ALTER TABLE ..."` — não requerem re-executar setup.php
- **Checkout Bricks:** Payment Brick inicializado com `preferenceId` (para pré-carregar valor e métodos da preference) + `payer.email` + `amount`; `transaction_amount` sobrescrito no backend pelo valor do banco via `array_merge($formData, [...])`
- **Modal pós-Bricks (`#bricksResultModal`):** exibido após `onSubmit` do Brick resolver; fecha o modal de checkout (desmontando o Brick via `hidden.bs.modal`) e abre o modal de resultado 450ms depois; exibe nome/e-mail do comprador armazenados em `_buyerName`/`_buyerEmail`
- **Acompanhar.html com busca dupla:** acesso via token na URL (e-mail + modal pós-pagamento) ou via formulário manual (pedido_id + e-mail); sem token na URL o formulário fica visível por padrão (não usa `style="display:none;"` no HTML — o JS esconde apenas quando há token)
- **Timeline unificada em `acompanhar.html`:** 6 etapas combinam status do pedido (`pedidos.status`) e status de entrega (`order_tracking.status`) em uma única barra de progresso — substituiu a dupla timeline anterior (status badge separado + rastreamento separado)

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

## .gitignore — o que é ignorado
- `database.db` — banco de dados runtime
- `img/prod_*` — imagens de produtos enviadas pelo admin
- `docs/` — data sheets PDF enviados pelo admin
- `config/` — diretório de config gerado em runtime na raiz
- `backend/config/mercadopago.php` — credenciais sensíveis do MP
- `vendor/` — dependências Composer
- `logs/` — logs do webhook

## Preferências
- Abordagem não agressiva: melhorar sem reescrever seções inteiras
- Mudanças centralizadas no CSS (não inline styles)
- Comunicação em português (pt-BR)
- Sem commit até solicitação explícita

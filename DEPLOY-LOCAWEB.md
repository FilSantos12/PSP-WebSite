# Deploy — Locaweb (Hospedagem Compartilhada)

> Consulta registrada em: 2026-06-15
> Domínio já reservado na Locaweb.

---

## Plano

Hospedagem compartilhada é suficiente. Requisitos mínimos:

- PHP 8.1 ou superior (`match`, `str_contains`, `str_starts_with` são usados no código)
- Extensão PDO SQLite habilitada (disponível por padrão na Locaweb)
- Acesso FTP ou gerenciador de arquivos cPanel

---

## Estrutura de Upload

Document root na Locaweb: `public_html/` (ou subpasta com nome do domínio).

Subir **tudo** para dentro dessa pasta:

```
public_html/
├── index.html
├── acompanhar.html
├── style.css
├── script.js
├── manifest.json
├── robots.txt
├── sitemap.xml
├── backend/
├── vendor/          ← subir a pasta vendor/ local (não rodar Composer no servidor)
├── img/
├── docs/
├── database.db      ← proteger via .htaccess (ver abaixo)
└── .htaccess        ← criar antes do upload (ver abaixo)
```

---

## Passo a Passo

### 1. Configurar versão do PHP

cPanel → **Selecionar versão PHP** → escolher **8.1** ou **8.2**.

### 2. Ativar SSL (obrigatório para o MP)

cPanel → **Let's Encrypt** → ativar para o domínio.
O Mercado Pago exige HTTPS nos `back_urls` e `notification_url` do webhook.

### 3. Criar `.htaccess` na raiz

Criar o arquivo `public_html/.htaccess` para bloquear acesso direto ao banco:

```apache
# Bloqueia download direto do banco SQLite
<FilesMatch "\.db$">
    Require all denied
</FilesMatch>
```

### 4. Criar `backend/config/mercadopago.php` no servidor

O arquivo está no `.gitignore` e não sobe com o FTP — criar manualmente via gerenciador de arquivos do cPanel:

```php
<?php
define('MP_ACCESS_TOKEN', 'APP_USR-...');  // chave de PRODUÇÃO
define('MP_PUBLIC_KEY',   'APP_USR-...');  // chave de PRODUÇÃO
define('MP_BASE_URL', 'https://seudominio.com.br');
```

> Ambas as chaves devem ser de **produção** (prefixo `APP_USR-`).
> Misturar chaves de teste + produção causa erro 401 `"Unauthorized use of live credentials"`.
> Onde encontrar: mercadopago.com.br/developers → Suas integrações → Credenciais.

### 5. Ajustar permissões de arquivo (via FTP)

| Caminho | Permissão |
|---|---|
| `database.db` | `664` |
| `logs/` (criada automaticamente pelo PHP) | `755` |
| `img/` | `755` |
| `docs/` | `755` |

---

## Checklist Pós-Upload

- [ ] Acessar `https://seudominio.com.br/setup.php` com a senha `psp@setup2025` → cria as tabelas
- [ ] **Deletar `setup.php`** do servidor imediatamente após executar
- [ ] Acessar o admin → trocar a senha padrão `admin123` por uma senha forte
- [ ] Registrar a URL do webhook no painel do Mercado Pago:
      `https://seudominio.com.br/backend/api/webhook.php`
- [ ] Atualizar os dois números de WhatsApp no `index.html` (botão hero + botão flutuante)
- [ ] Substituir os ícones PWA placeholder (`manifest.json`) por PNGs reais (192×192 e 512×512)
- [ ] Preencher a seção "Sobre Nós" com o texto real da empresa
- [ ] Testar uma compra completa com cartão de teste do MP antes de ir para produção

---

## Pontos de Atenção

### `database.db` acessível via browser
Sem o `.htaccess` da raiz, qualquer pessoa pode fazer download do banco completo (pedidos, e-mails, dados de compradores). Criar o arquivo antes do primeiro upload.

### `vendor/` — não rodar Composer no servidor
Subir a pasta `vendor/` do ambiente local. Hospedagem compartilhada normalmente não tem Composer disponível via SSH.

### `backend/.htaccess` — regra ineficaz
A diretiva `DirectoryMatch` usada no `backend/.htaccess` atual não funciona dentro de `.htaccess` (só vale em configuração de servidor). Os arquivos PHP em `config/` executam mas não expõem conteúdo diretamente, então o risco é baixo. Pode ser corrigido substituindo por `<Files>`.

### E-mail via `mail()` nativo
Não funciona em localhost (DEV-SKIP no log). Funciona normalmente em hospedagem compartilhada Locaweb. Logs em `logs/emails.log`.

### Webhook do Mercado Pago
Registrar no painel MP a URL pública de produção. Sem isso, pagamentos via Checkout Pro (redirect) não atualizam o status do pedido automaticamente.

---

## Pendências Antes do Deploy (da documentação do projeto)

- `SEU_NUMERO` — substituir nos 2 lugares em `index.html` (botão hero + botão flutuante)
- Ícones PWA reais (192×192 e 512×512 PNG) para `manifest.json`
- Texto real da seção "Sobre Nós"
- Preços dos produtos definidos via admin
- Senha do admin trocada (padrão: `admin123`)
- `setup.php` removido após execução

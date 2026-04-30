/**
 * ATMParts - Script principal
 * Gerencia interações da interface, modais e formulários
 */

class App {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupScrollHandlers();
        this.setupCounters();
        this.setupLGPD();
        this.setupThemeToggle();
        this.setupCheckout();
        this.setupImageLightbox();
        this.renderProducts();
    }

    setupEventListeners() {
        this.setupContactForm();
        this.setupModalButtons();
        this.setupNavigation();
    }

    setupContactForm() {
        const contactForm = document.getElementById('contactForm');
        if (!contactForm) return;

        contactForm.addEventListener('submit', (e) => {
            e.preventDefault(); // Impede o envio padrão e redirecionamento
            
            if (this.validateForm()) {
                this.showLoadingState(true);
                
                // Cria um formulário temporário para submissão
                this.submitForm(contactForm)
                    .then(() => {
                        // Sucesso: mostra o modal de confirmação
                        this.showConfirmationModal();
                        contactForm.reset();
                    })
                    .catch((error) => {
                        console.error('Erro no envio:', error);
                        this.showErrorModal('Erro no envio. Tente novamente ou entre em contato diretamente.');
                    })
                    .finally(() => {
                        this.showLoadingState(false);
                    });
            } else {
                this.showErrorModal('Por favor, preencha todos os campos obrigatórios.');
            }
        });
    }

    submitForm(form) {
        return fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' }
        }).then(response => {
            if (!response.ok) throw new Error('Falha no envio do formulário');
        });
    }

    showLoadingState(show) {
        const submitButton = document.querySelector('#contactForm button[type="submit"]');
        
        if (submitButton) {
            if (show) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
            } else {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Enviar Mensagem';
            }
        }
    }

    showConfirmationModal() {
        // Mostra o modal de confirmação
        const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        confirmationModal.show();
    }

    showErrorModal(message) {
        // Atualiza a mensagem de erro se fornecida
        if (message) {
            const errorText = document.querySelector('#errorModal .modal-body p');
            if (errorText) {
                errorText.textContent = message;
            }
        }
        
        // Mostra o modal de erro
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
    }

    validateForm() {
        const requiredFields = [
            document.getElementById('nome'),
            document.getElementById('email'), 
            document.getElementById('assunto'),
            document.getElementById('mensagem')
        ];
        
        return requiredFields.every(field => field && field.value.trim() !== '');
    }

    setupModalButtons() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('solicitar-orcamento')) {
                this.handleQuoteRequest(e.target);
            }
        });
    }

    handleQuoteRequest(button) {
        const modalElement = button.closest('.modal');
        const productName = button.dataset.product || this.extractProductName(modalElement);
        
        this.closeModal(modalElement);
        this.fillContactForm(productName);
        this.scrollToContactForm();
    }

    extractProductName(modalElement) {
        const titleElement = modalElement?.querySelector('.modal-title');
        return titleElement ? titleElement.textContent.replace(' - Detalhes', '') : 'Produto';
    }

    closeModal(modalElement) {
        if (!modalElement) return;
        
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal?.hide();
    }

    fillContactForm(productName) {
        const assuntoField = document.getElementById('assunto');
        if (assuntoField) {
            assuntoField.value = `Orçamento: ${productName}`;
        }
    }

    scrollToContactForm() {
        setTimeout(() => {
            const contactSection = document.getElementById('contato');
            if (contactSection) {
                contactSection.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        }, 300);
    }

    setupNavigation() {
        const verProdutosBtn = document.querySelector('.hero-section .btn-primary');
        if (verProdutosBtn) {
            verProdutosBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.scrollToSection('produtos');
            });
        }
    }

    scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
    }

    setupScrollHandlers() {
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        const backToTopBtn = document.getElementById('backToTop');

        if (!sections.length && !backToTopBtn) return;

        let ticking = false;
        window.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                if (sections.length && navLinks.length) {
                    this.updateActiveNavLink(sections, navLinks);
                }
                if (backToTopBtn) {
                    backToTopBtn.classList.toggle('visible', window.pageYOffset > 300);
                }
                ticking = false;
            });
        });

        backToTopBtn?.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    updateActiveNavLink(sections, navLinks) {
        let currentSection = '';
        const scrollPosition = window.pageYOffset;

        sections.forEach(section => {
            if (scrollPosition >= (section.offsetTop - 100)) {
                currentSection = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').substring(1) === currentSection) {
                link.classList.add('active');
            }
        });
    }

    setupThemeToggle() {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;

        const icon = btn.querySelector('i');

        // Sincroniza o ícone com o estado atual (definido pelo anti-FOUC no <body>)
        const updateIcon = (isDark) => {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        };

        updateIcon(document.body.classList.contains('dark-mode'));

        btn.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('dark-mode');
            updateIcon(isDark);
            localStorage.setItem('psp_theme', isDark ? 'dark' : 'light');
        });
    }

    setupProductFilter() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const productCols = document.querySelectorAll('.product-col');
        if (!filterBtns.length) return;

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filter = btn.dataset.filter;
                productCols.forEach(col => {
                    const match = filter === 'all' || col.dataset.category === filter;
                    col.classList.toggle('hidden', !match);
                });
            });
        });
    }

    setupLGPD() {
        const banner = document.getElementById('lgpdBanner');
        if (!banner) return;

        const consent = localStorage.getItem('psp_lgpd_consent');
        if (consent === 'accepted') {
            gtag('consent', 'update', { 'analytics_storage': 'granted' });
            return;
        }
        if (consent === 'rejected') return;

        banner.classList.add('visible');

        document.getElementById('lgpdAccept')?.addEventListener('click', () => {
            localStorage.setItem('psp_lgpd_consent', 'accepted');
            gtag('consent', 'update', { 'analytics_storage': 'granted' });
            banner.classList.remove('visible');
        });

        document.getElementById('lgpdReject')?.addEventListener('click', () => {
            localStorage.setItem('psp_lgpd_consent', 'rejected');
            banner.classList.remove('visible');
        });
    }

    setupCounters() {
        const counters = document.querySelectorAll('.stat-number');
        if (counters.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.dataset.animated) {
                    entry.target.dataset.animated = 'true';
                    this.animateCounter(entry.target, parseInt(entry.target.dataset.target));
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    }

    animateCounter(el, target) {
        const duration = 1500;
        const start = performance.now();

        const update = (time) => {
            const elapsed = time - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(eased * target);
            if (progress < 1) requestAnimationFrame(update);
        };

        requestAnimationFrame(update);
    }

    // ── CHECKOUT ──────────────────────────────────────────────────────────────

    setupCheckout() {
        this._checkoutPrice     = 0;
        this._checkoutProductId = 0;

        // Abre o modal de checkout ao clicar em qualquer .btn-buy
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-buy');
            if (!btn) return;

            const productName  = btn.dataset.productName;
            const productPrice = parseFloat(btn.dataset.productPrice);

            // Fecha modal de produto se estiver aberto
            const parentModal = btn.closest('.modal');
            if (parentModal) {
                bootstrap.Modal.getInstance(parentModal)?.hide();
            }

            this._checkoutPrice     = productPrice;
            this._checkoutProductId = parseInt(btn.dataset.productId) || 0;
            this._openCheckoutModal(productName, productPrice, !!parentModal);
        });

        // Controles de quantidade
        document.getElementById('checkout-qty-minus')?.addEventListener('click', () => {
            const qtyEl = document.getElementById('checkout-qty');
            const val   = Math.max(1, parseInt(qtyEl.value) - 1);
            qtyEl.value = val;
            this._updateCheckoutTotal(this._checkoutPrice, val);
        });

        document.getElementById('checkout-qty-plus')?.addEventListener('click', () => {
            const qtyEl = document.getElementById('checkout-qty');
            const val   = Math.min(99, parseInt(qtyEl.value) + 1);
            qtyEl.value = val;
            this._updateCheckoutTotal(this._checkoutPrice, val);
        });

        document.getElementById('checkout-qty')?.addEventListener('input', () => {
            const val = Math.max(1, parseInt(document.getElementById('checkout-qty').value) || 1);
            this._updateCheckoutTotal(this._checkoutPrice, val);
        });

        // Limpa estado de erro ao digitar
        ['checkout-nome', 'checkout-email', 'checkout-telefone', 'checkout-cep', 'checkout-numero'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => {
                document.getElementById(id)?.classList.remove('is-invalid');
            });
        });

        // Máscara de CEP (00000-000)
        document.getElementById('checkout-cep')?.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, '').slice(0, 8);
            if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
            e.target.value = v;
        });

        // Busca ViaCEP ao sair do campo ou clicar no botão
        const buscarCep = () => this._buscarCep();
        document.getElementById('checkout-cep')?.addEventListener('blur', buscarCep);
        document.getElementById('checkout-cep-btn')?.addEventListener('click', buscarCep);

        // Submissão do checkout
        document.getElementById('checkout-submit')?.addEventListener('click', async () => {
            const required = [
                { id: 'checkout-nome',     value: document.getElementById('checkout-nome')?.value.trim() },
                { id: 'checkout-email',    value: document.getElementById('checkout-email')?.value.trim() },
                { id: 'checkout-telefone', value: document.getElementById('checkout-telefone')?.value.trim() },
                { id: 'checkout-cep',      value: document.getElementById('checkout-cep')?.value.replace(/\D/g,'') },
                { id: 'checkout-numero',   value: document.getElementById('checkout-numero')?.value.trim() },
            ];

            let valid = true;
            required.forEach(({ id, value }) => {
                const el = document.getElementById(id);
                if (!value) { el.classList.add('is-invalid'); valid = false; }
            });

            if (!valid) {
                document.querySelector('#checkoutForm .is-invalid')?.focus();
                return;
            }

            const submitBtn = document.getElementById('checkout-submit');
            submitBtn.disabled = true;

            const qty = parseInt(document.getElementById('checkout-qty').value) || 1;

            const g = id => document.getElementById(id)?.value.trim() ?? '';

            try {
                // 1. Grava o pedido no banco
                const rPedido = await fetch('backend/api/pedidos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        nome_comprador:     g('checkout-nome'),
                        email_comprador:    g('checkout-email'),
                        telefone_comprador: g('checkout-telefone'),
                        cep:                g('checkout-cep'),
                        endereco:           g('checkout-endereco'),
                        numero:             g('checkout-numero'),
                        complemento:        g('checkout-complemento'),
                        bairro:             g('checkout-bairro'),
                        cidade:             g('checkout-cidade'),
                        estado:             g('checkout-estado'),
                        produto_id:         this._checkoutProductId,
                        quantidade:         qty,
                    }),
                });

                if (!rPedido.ok) {
                    const err = await rPedido.json().catch(() => ({}));
                    throw new Error(err.erro || 'Erro ao criar pedido.');
                }
                const pedido = await rPedido.json();

                // 2. Fecha checkout e exibe spinner
                bootstrap.Modal.getInstance(document.getElementById('checkoutModal'))?.hide();
                await new Promise(r => setTimeout(r, 400));
                new bootstrap.Modal(document.getElementById('paymentRedirectModal')).show();

                // 3. Cria a preference no Mercado Pago
                const rPagamento = await fetch('backend/api/pagamento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: pedido.pedido_id }),
                });

                if (!rPagamento.ok) throw new Error('Erro ao criar pagamento.');
                const mp = await rPagamento.json();

                // 4. Redireciona para o Mercado Pago
                const mpUrl = mp.init_point || mp.sandbox_url;
                if (mpUrl) {
                    window.location.href = mpUrl;
                }

            } catch (err) {
                console.error('Checkout error:', err);
                submitBtn.disabled = false;
                bootstrap.Modal.getInstance(document.getElementById('paymentRedirectModal'))?.hide();

                // Mostra o erro no topo do modal de checkout
                const alertEl = document.getElementById('checkout-error-msg');
                if (alertEl) {
                    alertEl.textContent = err.message || 'Erro inesperado. Tente novamente.';
                    alertEl.classList.remove('d-none');
                    setTimeout(() => alertEl.classList.add('d-none'), 6000);
                }
            }
        });
    }

    async _buscarCep() {
        const cepEl = document.getElementById('checkout-cep');
        const cep   = cepEl?.value.replace(/\D/g, '');
        if (!cep || cep.length !== 8) return;

        const btn = document.getElementById('checkout-cep-btn');
        if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const r    = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await r.json();

            if (data.erro) {
                cepEl.classList.add('is-invalid');
                cepEl.nextElementSibling?.nextElementSibling || (cepEl.closest('.col-sm-5')?.querySelector('.invalid-feedback') && (cepEl.closest('.col-sm-5').querySelector('.invalid-feedback').textContent = 'CEP não encontrado.'));
            } else {
                document.getElementById('checkout-endereco').value = data.logradouro || '';
                document.getElementById('checkout-bairro').value   = data.bairro     || '';
                document.getElementById('checkout-cidade').value   = data.localidade || '';
                document.getElementById('checkout-estado').value   = data.uf         || '';
                cepEl.classList.remove('is-invalid');
                document.getElementById('checkout-numero')?.focus();
            }
        } catch (_) {
            // falha silenciosa — usuário pode preencher manualmente
        } finally {
            if (btn) btn.innerHTML = '<i class="fas fa-search"></i>';
        }
    }

    _openCheckoutModal(productName, price, withDelay) {
        document.getElementById('checkout-product-name').textContent = productName;
        document.getElementById('checkout-qty').value = 1;
        this._updateCheckoutTotal(price, 1);

        // Limpa campos e erros anteriores
        const allFields = ['checkout-nome','checkout-email','checkout-telefone',
                           'checkout-cep','checkout-numero','checkout-complemento',
                           'checkout-endereco','checkout-bairro','checkout-cidade','checkout-estado'];
        allFields.forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.value = ''; el.classList.remove('is-invalid'); }
        });

        setTimeout(() => {
            new bootstrap.Modal(document.getElementById('checkoutModal')).show();
        }, withDelay ? 450 : 0);
    }

    _updateCheckoutTotal(price, qty) {
        const total     = price * qty;
        const formatted = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        document.getElementById('checkout-total').textContent     = formatted;
        document.getElementById('checkout-btn-total').textContent = formatted;
    }

    // ── LIGHTBOX ─────────────────────────────────────────────────────────────

    setupImageLightbox() {
        const overlay   = document.getElementById('lightboxOverlay');
        const lbImg     = document.getElementById('lightbox-img');
        const lbCaption = document.getElementById('lightbox-caption');
        if (!overlay) return;

        const open = (src, alt) => {
            lbImg.src           = src;
            lbImg.alt           = alt;
            lbCaption.textContent = alt || '';
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        const close = () => {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        };

        // Clique em imagem de card, carrossel ou modal de produto
        document.addEventListener('click', (e) => {
            const img = e.target.closest(
                'img.card-img-top, img.carousel-img, img.modal-product-image'
            );
            if (!img) return;
            open(img.src, img.alt);
        });

        // Fecha ao clicar no fundo escuro
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close();
        });

        // Fecha pelo botão X
        document.getElementById('lightboxClose')?.addEventListener('click', close);

        // Fecha com Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('active')) close();
        });
    }

    // ── PRODUTOS — renderiza cards e modais a partir da API ──────────────────

    async renderProducts() {
        const categoryLabels = {
            motorizacao: 'Motorização',
            robotica:    'Robótica',
            acesso:      'Controle de Acesso',
            bluetooth:   'Bluetooth',
        };

        try {
            const r       = await fetch('backend/api/produtos.php');
            const produtos = await r.json();

            const grid   = document.getElementById('products-grid');
            const modals = document.getElementById('product-modals');
            if (!grid || !modals) return;

            let cardsHtml  = '';
            let modalsHtml = '';

            produtos.forEach((p, i) => {
                const label  = categoryLabels[p.categoria] || p.categoria;
                const preco  = p.preco.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                const delay  = (i % 3) * 100;

                // Imagem principal: prioriza array imagens, cai para campo imagem legado
                const imagens     = Array.isArray(p.imagens) ? p.imagens : [];
                const imgPrincipal = imagens.find(img => img.principal) || imagens[0];
                const imgSrc      = imgPrincipal ? imgPrincipal.caminho : (p.imagem || '');

                const imgTag = imgSrc
                    ? `<img src="${imgSrc}" class="card-img-top" alt="${p.nome}" loading="lazy">`
                    : `<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:180px"><i class="fas fa-image fa-3x text-muted"></i></div>`;

                // Área de mídia do modal: carousel se múltiplas imagens, senão imagem simples
                let modalMedia;
                if (imagens.length > 1) {
                    const carouselId = `carousel-prod-${p.id}`;
                    const slides = imagens.map((img, idx) => `
                        <div class="carousel-item ${idx === 0 ? 'active' : ''}">
                            <img src="${img.caminho}" class="d-block w-100 carousel-img" alt="${p.nome}" loading="lazy"
                                 style="max-height:280px;object-fit:contain;background:#f8f9fa;border-radius:6px;">
                        </div>`).join('');
                    const indicators = imagens.map((_, idx) => `
                        <button type="button" data-bs-target="#${carouselId}" data-bs-slide-to="${idx}"
                                class="${idx === 0 ? 'active' : ''}" style="background-color:#274185"></button>`).join('');
                    modalMedia = `
                        <div id="${carouselId}" class="carousel slide" data-bs-ride="false">
                            <div class="carousel-indicators">${indicators}</div>
                            <div class="carousel-inner">${slides}</div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#${carouselId}" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#${carouselId}" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        </div>`;
                } else if (imgSrc) {
                    modalMedia = `<img src="${imgSrc}" class="modal-product-image" alt="${p.nome}" loading="lazy">`;
                } else {
                    modalMedia = '';
                }

                // Código interno
                const codigoHtml = p.codigo_interno
                    ? `<div class="text-muted small mb-2"><i class="fas fa-barcode me-1"></i>${p.codigo_interno}</div>`
                    : '';

                // Link data sheet
                const datasheetHtml = p.datasheet
                    ? `<a href="${p.datasheet}" target="_blank" class="btn btn-sm btn-outline-secondary me-1">
                           <i class="fas fa-file-pdf me-1 text-danger"></i> Data Sheet
                       </a>`
                    : '';

                // Especificação técnica (Markdown → HTML)
                const especHtml = p.especificacao_tecnica && typeof marked !== 'undefined'
                    ? `<hr class="my-3">
                       <h6 class="fw-semibold mb-2"><i class="fas fa-list-ul me-1 text-muted"></i> Especificação Técnica</h6>
                       <div class="spec-content small">${marked.parse(p.especificacao_tecnica)}</div>`
                    : '';

                cardsHtml += `
                <div class="col-md-4 mb-4 product-col" data-category="${p.categoria}" data-aos="fade-up" data-aos-delay="${delay}">
                    <div class="card product-card">
                        ${imgTag}
                        <div class="card-body">
                            <span class="product-badge badge-${p.categoria}">${label}</span>
                            <h5 class="card-title">${p.nome}</h5>
                            <p class="card-text">${p.descricao || ''}</p>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="product-price">${preco}</span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#productModal${p.id}">
                                        Detalhes
                                    </button>
                                    ${p.estoque > 0
                                        ? `<button type="button" class="btn btn-success btn-sm btn-buy"
                                                data-product-id="${p.id}"
                                                data-product-name="${p.nome}"
                                                data-product-price="${p.preco}"
                                                title="Comprar">
                                            <i class="fas fa-cart-shopping"></i>
                                           </button>`
                                        : `<button type="button" class="btn btn-secondary btn-sm" disabled title="Sem estoque">
                                            <i class="fas fa-ban"></i>
                                           </button>`}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

                modalsHtml += `
                <div class="modal fade" id="productModal${p.id}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${p.nome} — Detalhes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 modal-image-container">${modalMedia}</div>
                                    <div class="col-md-6">
                                        <h4>${p.nome}</h4>
                                        ${codigoHtml}
                                        <span class="product-badge badge-${p.categoria} mb-2 d-inline-block">${label}</span>
                                        <p>${p.descricao || ''}</p>
                                        ${p.estoque > 0
                                            ? `<span class="badge bg-success">Em estoque (${p.estoque} un.)</span>`
                                            : `<span class="badge bg-danger">Fora de estoque</span>`}
                                        ${especHtml}
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <span class="me-auto product-price-modal">${preco}</span>
                                ${datasheetHtml}
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <button type="button" class="btn btn-outline-primary solicitar-orcamento" data-product="${p.nome}">
                                    Solicitar Orçamento
                                </button>
                                ${p.estoque > 0
                                    ? `<button type="button" class="btn btn-success btn-buy"
                                            data-product-id="${p.id}"
                                            data-product-name="${p.nome}"
                                            data-product-price="${p.preco}">
                                        <i class="fas fa-cart-shopping me-1"></i> Comprar Agora
                                       </button>`
                                    : `<button type="button" class="btn btn-secondary" disabled>
                                        <i class="fas fa-ban me-1"></i> Fora de estoque
                                       </button>`}
                            </div>
                        </div>
                    </div>
                </div>`;
            });

            grid.innerHTML   = cardsHtml   || '<p class="text-center text-muted col-12">Nenhum produto disponível.</p>';
            modals.innerHTML = modalsHtml;

            this.setupProductFilter();
            if (typeof AOS !== 'undefined') AOS.refresh();

        } catch (_) { /* mantém conteúdo estático do HTML se API falhar */ }
    }

    _showPaymentComingSoon() {
        const modal   = document.getElementById('confirmationModal');
        const header  = modal.querySelector('.modal-header');
        const title   = modal.querySelector('.modal-title');
        const icon    = modal.querySelector('.modal-body .fa-check-circle');
        const heading = modal.querySelector('.modal-body h4');
        const text    = modal.querySelector('.modal-body p');
        const footerBtn = modal.querySelector('.modal-footer .btn');

        header.className    = 'modal-header bg-primary text-white';
        title.innerHTML     = '<i class="fas fa-rocket me-2"></i>Em breve!';
        if (icon)    icon.className    = 'fas fa-rocket text-primary';
        if (heading) { heading.textContent = 'Pagamento online em breve!'; heading.className = 'text-primary'; }
        if (text)    text.textContent  = 'Estamos integrando o Mercado Pago. Em breve você poderá comprar direto pelo site!';
        if (footerBtn) { footerBtn.className = 'btn btn-primary'; footerBtn.innerHTML = '<i class="fas fa-thumbs-up me-2"></i>Entendi'; }

        new bootstrap.Modal(modal).show();
    }
}

// Inicialização da aplicação quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new App();
    AOS.init({ duration: 600, once: true, offset: 80 });
});
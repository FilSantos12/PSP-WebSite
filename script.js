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
        this.setupProductFilter();
        this.setupThemeToggle();
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
}

// Inicialização da aplicação quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new App();
    AOS.init({ duration: 600, once: true, offset: 80 });
});
# ATMParts v.2

Página web voltada para a comercialização de peças do segmento **mecatrônico** e **controle de acesso**.  
Desenvolvido com **HTML, CSS e JavaScript** puro no Front-End.

---

## Funcionalidades

- **Hero Section com Estatísticas Animadas**  
  Apresentação da empresa com contadores animados (produtos, anos de mercado, clientes e suporte).

- **Seção de Diferenciais**  
  Cards destacando os diferenciais competitivos: Qualidade Técnica, Suporte Especializado, Produtos Certificados e Entrega Rápida.

- **Catálogo de Produtos**  
  6 produtos com badges de categoria, animações de entrada e cards responsivos com efeito hover.

- **Modal de Especificações**  
  Exibe detalhes técnicos, imagens (com carrossel) e vídeo de demonstração de cada produto.

- **Solicitação de Orçamento Integrada**  
  Botão nos modais que fecha o produto, preenche o campo de assunto e rola automaticamente até o formulário de contato.

- **Seção de Contato com Split Layout**  
  Informações de contato (WhatsApp, e-mail, localização, horário) ao lado do formulário de envio.

- **Botão Flutuante de WhatsApp**  
  Acesso rápido ao WhatsApp fixado no canto da tela.

- **Botão Voltar ao Topo**  
  Aparece após rolar 300px e retorna suavemente ao início da página.

- **Animações de Scroll (AOS)**  
  Elementos entram com fade-in escalonado conforme o usuário navega pela página.

---

## Tecnologias Utilizadas

- **HTML5** → Estrutura semântica da página
- **CSS3** → Estilização, variáveis customizadas e responsividade
- **JavaScript ES6+** → Classe App com orientação a objetos, Intersection Observer, animações
- **Bootstrap 5.3** → Grid, componentes e modais
- **Font Awesome 6.4** → Ícones
- **Google Fonts** → Poppins e Montserrat
- **AOS 2.3** → Animate On Scroll
- **FormSubmit.co** → Envio de formulário sem backend
- **Google Analytics (GA4)** → Rastreamento de visitantes

---

## Estrutura do Projeto

```
AtmParts_v.2/
├── index.html      # Estrutura HTML principal
├── style.css       # Estilos e responsividade
├── script.js       # Lógica JavaScript (classe App)
├── img/            # Imagens dos produtos
├── video/          # Vídeos de demonstração
└── README.md
```

---

## Configuração

Antes de publicar, substitua `SEU_NUMERO` pelo número de WhatsApp real (com DDI+DDD, sem espaços ou símbolos):

```
https://wa.me/5511999999999
```

Ocorre em 3 lugares no `index.html`: hero, seção de contato e botão flutuante.

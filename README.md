# Radiance Joias · E-commerce

Loja virtual completa de joias em prata 925, do catálogo ao pagamento, com painel administrativo para a gestão da loja.

🔗 **No ar:** [radiance.gsstack.com.br](https://radiance.gsstack.com.br)

![Página inicial da loja](.github/screenshot.jpg)

## Funcionalidades

**Loja**
- Catálogo por categorias, busca e página de produto com variações e galeria de imagens
- Carrinho persistente e cupons de desconto
- Checkout com cálculo de frete, Pix e cartão de crédito
- Área do cliente: cadastro, endereços, histórico e acompanhamento de pedidos
- Páginas legais (privacidade, termos, trocas e devoluções) e aviso de cookies

**Painel administrativo**
- Dashboard com indicadores de vendas
- Produtos com variações, upload de imagens e lixeira
- Pedidos, clientes, categorias, cupons, tabela de frete e configurações da loja

**Regras de negócio no backend**
- Reserva de estoque durante o checkout, liberada automaticamente se o pedido expira, para nunca vender o mesmo item duas vezes
- Confirmação de pagamento verificada no servidor, por webhook e por rotina agendada
- Marcação de carrinhos abandonados
- Conversão automática das imagens de produto para WebP

## Stack

| Camada | Tecnologias |
|---|---|
| Backend | PHP 8.3, Laravel 13, Laravel Sanctum, MySQL |
| Frontend | Nuxt 4 (Vue 3), TypeScript, Tailwind CSS |
| Infra | Linux VPS, Nginx, PHP-FPM, Let's Encrypt |

## Estrutura

```
backend/    API REST em Laravel
  app/Http/Controllers/Api/         endpoints da loja
  app/Http/Controllers/Api/Admin/   endpoints do painel
  app/Services/                     regras de negócio (carrinho, checkout, cupons, frete, pagamentos)
  app/Console/Commands/             rotinas agendadas (reservas, pagamentos pendentes, carrinhos)
frontend/   loja e painel em Nuxt
  app/pages/                        rotas da loja e do painel (/admin)
```

## Rodando localmente

```bash
# API
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan serve

# Loja
cd frontend
npm install
cp .env.example .env   # NUXT_PUBLIC_API_BASE aponta para a API
npm run dev
```

---

Desenvolvido por **Giovani Storani Jacon** · [LinkedIn](https://www.linkedin.com/in/giovani-jacon) · [Portfólio](https://giovani.gsstack.com.br)

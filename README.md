# Tripz - Integração PayTour com Togo Framework v1.3.0

Plugin WordPress para integração entre a API PayTour v2 e o Togo Framework, permitindo sincronização automática de viagens e sistema de reservas com autenticação via `app_key` e `app_secret`.

## 🆕 Novidades da Versão 1.3.0

- ✅ **Autenticação atualizada**: Implementação do novo método de autenticação PayTour v2
- ✅ **App Key/Secret**: Suporte completo a credenciais de aplicativo
- ✅ **Token Management**: Gerenciamento automático de `access_token` e `refresh_token`
- ✅ **API v2**: Migração completa para a nova API PayTour v2
- ✅ **Logs melhorados**: Sistema de logs mais detalhado para debug

## 📋 Índice

- [Características](#características)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Autenticação PayTour](#autenticação-paytour)
- [Uso](#uso)
- [Shortcodes](#shortcodes)
- [API](#api)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)

## ✨ Características

### Integração Completa
- ✅ Sincronização automática de viagens da PayTour API v2
- ✅ Sistema de verificação de disponibilidade em tempo real
- ✅ Processamento de reservas integrado
- ✅ Sincronização bidirecional de status
- ✅ Cache inteligente para performance
- ✅ Gerenciamento automático de tokens de acesso

### Autenticação Avançada
- ✅ Suporte a credenciais de aplicativo (app_key/app_secret)
- ✅ Codificação Base64 automática
- ✅ Renovação automática de tokens
- ✅ Fallback para nova autenticação quando necessário
- ✅ Logs detalhados de autenticação

### Compatibilidade
- ✅ Totalmente integrado com Togo Framework
- ✅ Utiliza Custom Post Types `togo_trip` e `togo_booking`
- ✅ Compatível com Elementor
- ✅ Responsivo e mobile-friendly
- ✅ Suporte a múltiplos idiomas

## 🔧 Requisitos

### WordPress
- WordPress 5.0 ou superior
- PHP 7.4 ou superior
- MySQL 5.6 ou superior

### Plugins Obrigatórios
- **Togo Framework** (versão 1.0.0+)
- **Togo Theme** (ativo)

### API PayTour v2
- Credenciais de aplicativo (app_key e app_secret)
- Acesso aos endpoints da API v2

## 📦 Instalação

### Método 1: Upload Manual

1. Faça download do plugin
2. Acesse **Plugins > Adicionar Novo > Enviar Plugin**
3. Selecione o arquivo ZIP do plugin
4. Clique em **Instalar Agora**
5. Ative o plugin

### Método 2: FTP/SSH

```bash
# Na VPS
cd /www/wwwroot/ilhabela.bidooh.com.br/wp-content/plugins/
unzip tripz-paytour-plugin-v2.zip
chown -R www:www tripz-paytour-plugin-v2/
chmod -R 755 tripz-paytour-plugin-v2/
```

### Verificação da Instalação

Após a ativação, verifique se:
- O menu **Configurações > Tripz PayTour** está disponível
- Não há mensagens de erro no painel
- O Togo Framework está ativo

## ⚙️ Configuração

### 1. Configuração Básica

Acesse **Configurações > Tripz PayTour** e configure:

#### App Key
```
Sua chave de aplicativo PayTour (app_key)
Obtida no painel administrativo da PayTour
```

#### App Secret
```
Sua chave secreta de aplicativo PayTour (app_secret)
Mantenha em segurança - nunca compartilhe
```

#### URL da API
```
https://api.paytour.com.br/v2
(normalmente não precisa ser alterada)
```

#### Intervalo de Sincronização
- A cada hora
- Duas vezes por dia
- Diariamente (recomendado)
- Semanalmente

### 2. Teste de Conexão

1. Clique em **Testar Conexão**
2. Verifique se o status mostra "Conexão estabelecida com sucesso"
3. Se houver erro, verifique suas credenciais

### 3. Sincronização Inicial

1. Clique em **Sincronizar Trips**
2. Aguarde a conclusão do processo
3. Verifique as estatísticas atualizadas

## 🔐 Autenticação PayTour

### Como Funciona

O plugin implementa o fluxo de autenticação oficial da PayTour v2:

1. **Concatenação**: `app_key:app_secret`
2. **Codificação**: Base64 da string concatenada
3. **Login**: `POST /lojas/login?grant_type=application`
4. **Header**: `Authorization: Basic [base64_string]`
5. **Resposta**: `access_token` e `refresh_token`
6. **Uso**: `Authorization: Bearer [access_token]`

### Renovação Automática

- O plugin monitora a expiração do token
- Renova automaticamente usando `refresh_token`
- Faz novo login se a renovação falhar
- Logs detalhados para debug

### Obtenção das Credenciais

1. Acesse o painel administrativo da PayTour
2. Vá para a seção "Desenvolvedor" ou "Integrações"
3. Gere ou visualize suas credenciais de aplicativo
4. Copie o **app_key** e **app_secret**

## 🚀 Uso

### Sincronização de Viagens

#### Automática
- Configurada no intervalo escolhido
- Executa via WP-Cron
- Atualiza trips existentes
- Cria novos trips automaticamente

#### Manual
- Botão **Sincronizar Trips** no painel
- Útil para atualizações imediatas
- Mostra progresso em tempo real

### Verificação de Disponibilidade

#### No Frontend
Use o shortcode em qualquer página ou post:

```php
[tripz_trip_availability id="123"]
```

#### Em Templates
```php
<?php
$paytour_id = get_post_meta(get_the_ID(), 'paytour_id', true);
if ($paytour_id) {
    echo do_shortcode('[tripz_trip_availability id="' . get_the_ID() . '"]');
}
?>
```

## 📝 Shortcodes

### [tripz_trip_availability]

Exibe formulário de verificação de disponibilidade.

#### Parâmetros

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `id` | int | 0 | ID do post togo_trip |
| `show_price` | bool | true | Exibir preço |
| `show_duration` | bool | true | Exibir duração |
| `button_text` | string | "Verificar Disponibilidade" | Texto do botão |

#### Exemplos

```php
// Básico
[tripz_trip_availability id="123"]

// Customizado
[tripz_trip_availability id="123" show_price="false" button_text="Consultar Datas"]

// Sem duração
[tripz_trip_availability id="123" show_duration="false"]
```

## 🔗 API

### Endpoints Utilizados

#### Autenticação
```
POST /lojas/login?grant_type=application
POST /lojas/login?grant_type=refresh_token
```

#### Trips
```
GET /trips
GET /trips/availability
```

#### Reservas
```
POST /bookings
PUT /bookings/{id}/status
```

### JavaScript Frontend

#### TripzPayTour.checkAvailability()

```javascript
TripzPayTour.checkAvailability(123, {
    checkin: '2024-01-15',
    checkout: '2024-01-20',
    adults: 2,
    children: 0
}).done(function(response) {
    console.log('Disponibilidade:', response);
});
```

## 🔍 Troubleshooting

### Problemas de Autenticação

#### Erro: "Falha na autenticação"

**Possíveis causas:**
- App Key ou App Secret incorretos
- Credenciais não configuradas no painel PayTour
- Problema na codificação Base64

**Soluções:**
1. Verifique se as credenciais estão corretas
2. Teste manualmente:
```bash
echo -n "app_key:app_secret" | base64
curl -X POST "https://api.paytour.com.br/v2/lojas/login?grant_type=application" \
     -H "Authorization: Basic [base64_result]"
```

#### Erro: "Token expirado"

**Soluções:**
1. O plugin deve renovar automaticamente
2. Se persistir, limpe os tokens salvos:
   - Vá para Configurações > Tripz PayTour
   - Salve as configurações novamente
3. Verifique logs para detalhes

### Problemas de Sincronização

#### Trips não aparecem após sincronização

**Soluções:**
1. Verifique se a autenticação está funcionando
2. Consulte logs em **Configurações > Tripz PayTour**
3. Teste a conexão manualmente

### Debug Avançado

#### Ativar Logs Detalhados

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

#### Modo Debug do Plugin

Adicione `?debug=1` à URL da página de configurações para acessar:
- Limpar cache
- Exportar logs
- Reset do plugin

## 📊 Estrutura de Dados

### Tokens de Autenticação

| Opção | Descrição |
|-------|-----------|
| `tripz_paytour_access_token` | Token de acesso atual |
| `tripz_paytour_refresh_token` | Token para renovação |
| `tripz_paytour_token_expires_at` | Timestamp de expiração |

### Meta Fields do togo_trip

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `paytour_id` | string | ID único na PayTour |
| `trip_price` | float | Preço base do trip |
| `trip_duration` | string | Duração do trip |
| `trip_location` | string | Localização principal |
| `paytour_data` | json | Dados completos da PayTour |

## 🔄 Changelog

### 1.3.0 - 2024-09-13
- ✅ **BREAKING**: Migração para PayTour API v2
- ✅ **BREAKING**: Novo método de autenticação (app_key/app_secret)
- ✅ Gerenciamento automático de tokens
- ✅ Renovação automática de access_token
- ✅ Logs melhorados para debug
- ✅ Interface administrativa atualizada
- ✅ Documentação completa do novo fluxo

### 1.2.0 - 2024-09-13
- ✅ Integração completa com Togo Framework
- ✅ Remoção da dependência do WooCommerce
- ✅ Novos hooks para reservas
- ✅ Interface administrativa melhorada
- ✅ Sistema de cache otimizado

### 1.1.0 - 2024-08-15
- ✅ Adicionado suporte a múltiplas opções de trip
- ✅ Melhorias na interface do usuário
- ✅ Correções de bugs na sincronização

### 1.0.0 - 2024-07-01
- ✅ Versão inicial
- ✅ Integração básica com PayTour
- ✅ Sistema de sincronização
- ✅ Verificação de disponibilidade

## 🚨 Migração da v1.2.0

Se você está atualizando da versão anterior:

1. **Backup**: Faça backup do site antes da atualização
2. **Credenciais**: Obtenha app_key e app_secret no painel PayTour
3. **Configuração**: Reconfigure as credenciais na nova interface
4. **Teste**: Execute teste de conexão após a configuração
5. **Sincronização**: Execute sincronização manual para verificar

## 📄 Licença

Este plugin é licenciado sob GPL v2 ou posterior.

## 👥 Créditos

Desenvolvido por **Maremar Turismo** para integração com a plataforma PayTour v2.

---

**Maremar Turismo** - Especialistas em Ilhabela
- Website: https://maremar.tur.br
- Email: contato@maremar.tur.br
- PayTour API: https://api.paytour.com.br/v2/docs


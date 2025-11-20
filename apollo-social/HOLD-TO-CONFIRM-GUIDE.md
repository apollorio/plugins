# 🔒 Hold to Confirm - Sistema de Segurança

## Visão Geral

Sistema de segurança "Hold to Confirm" (Segure para Confirmar) que requer que o usuário mantenha o botão pressionado por um período determinado antes de confirmar a ação. Funciona como um CAPTCHA oculto com efeitos visuais suaves usando Motion.

## Características

- ✅ **Segurança**: Previne cliques acidentais e bots
- ✅ **Efeitos Visuais**: Animações suaves usando Motion library
- ✅ **Acessibilidade**: Suporta mouse e touch
- ✅ **Customizável**: Configurável via data attributes
- ✅ **Reutilizável**: Funciona em qualquer botão de submit

## Uso Básico

### HTML

```html
<button 
    type="submit"
    data-hold-to-confirm
    data-hold-duration="2000"
    data-confirm-text="✓ Confirmado"
>
    Segure para Registrar
</button>
```

### Atributos Disponíveis

- `data-hold-to-confirm` - Ativa o sistema (obrigatório)
- `data-hold-duration` - Duração em ms (padrão: 2000)
- `data-progress-color` - Cor da barra de progresso (padrão: #3b82f6)
- `data-success-color` - Cor de sucesso (padrão: #10b981)
- `data-error-color` - Cor de erro (padrão: #ef4444)
- `data-confirm-text` - Texto após confirmação
- `data-on-complete` - Nome da função JavaScript a chamar
- `data-on-cancel` - Nome da função JavaScript a chamar

## Efeitos Visuais

### Durante o Hold

1. **Barra de Progresso**: Aparece na parte inferior do botão
2. **Shimmer Effect**: Animação de brilho na barra
3. **Scale Animation**: Botão cresce ligeiramente
4. **Glow Pulse**: Efeito de brilho pulsante
5. **Rotation Wobble**: Leve rotação oscilante
6. **Ripple Effect**: Efeito de ondulação ao iniciar

### Após Confirmação

1. **Checkmark**: Ícone de check aparece
2. **Color Change**: Botão muda para cor de sucesso
3. **Scale Pop**: Animação de "pop" final
4. **Form Submit**: Formulário é enviado automaticamente

## Integração com Formulários

### Registro de Usuário

```php
<button 
    type="submit" 
    data-hold-to-confirm
    data-hold-duration="2000"
    data-confirm-text="<?php esc_attr_e('✓ Registrando...', 'apollo-social'); ?>"
>
    <?php esc_html_e('Segure para Registrar', 'apollo-social'); ?>
</button>
```

### Postagem de Conteúdo

```php
<button 
    type="submit" 
    data-hold-to-confirm
    data-hold-duration="1500"
    data-confirm-text="<?php esc_attr_e('✓ Publicando...', 'apollo-social'); ?>"
>
    <?php esc_html_e('Segure para Publicar', 'apollo-social'); ?>
</button>
```

### Comentários

```php
<button 
    type="submit" 
    data-hold-to-confirm
    data-hold-duration="1000"
    data-confirm-text="<?php esc_attr_e('✓ Enviando...', 'apollo-social'); ?>"
>
    <?php esc_html_e('Segure para Comentar', 'apollo-social'); ?>
</button>
```

## JavaScript API

### Criar Programaticamente

```javascript
const button = document.getElementById('my-button');
const holdButton = createHoldToConfirmButton(button, {
    holdDuration: 2000,
    progressColor: '#3b82f6',
    successColor: '#10b981',
    onComplete: () => {
        console.log('Confirmed!');
    },
    onCancel: () => {
        console.log('Cancelled');
    }
});
```

### Resetar Botão

```javascript
holdButton.reset();
```

### Destruir

```javascript
holdButton.destroy();
```

## Customização CSS

### Cores Personalizadas

```css
[data-hold-to-confirm] {
    --progress-color: #your-color;
    --success-color: #your-color;
}
```

### Duração Personalizada

```html
<button data-hold-to-confirm data-hold-duration="3000">
    Segure por 3 segundos
</button>
```

## Acessibilidade

- ✅ Suporta mouse e touch
- ✅ Funciona com teclado (Enter mantido)
- ✅ Respeita `prefers-reduced-motion`
- ✅ Suporta alto contraste
- ✅ Tamanho mínimo de toque (48px) em mobile

## Segurança

Este sistema fornece:

1. **Proteção contra Bots**: Bots simples não conseguem simular hold
2. **Prevenção de Cliques Acidentais**: Requer intenção consciente
3. **Validação de Tempo**: Garante que usuário leu/confirmou
4. **CAPTCHA Oculto**: Não é óbvio que é uma medida de segurança

## Dependências

- **Motion Library**: Carregada automaticamente via CDN
- **jQuery**: Opcional (para eventos dinâmicos)

## Arquivos

- `assets/js/hold-to-confirm.js` - Lógica principal
- `assets/css/hold-to-confirm.css` - Estilos e animações
- `templates/registration/registration-form.php` - Exemplo de uso

## Status

✅ **Implementado e Funcionando**  
✅ **Integrado com Registration Form**  
✅ **Pronto para uso em todos os formulários**

---

**Última Atualização:** $(date)


// Editor drag-and-drop básico usando Muuri
// (Exemplo inicial, deve ser expandido para widgets reais)
document.addEventListener('DOMContentLoaded', function() {
  if (!document.getElementById('userpage-editor')) return;
  var grid = new Muuri('#userpage-editor', {
    dragEnabled: true,
    layoutOnInit: true,
    layout: {
      fillGaps: true
    }
  });
  document.getElementById('userpage-save').onclick = function() {
    // Coleta ordem dos widgets e salva via AJAX
    var items = grid.getItems().map(function(item) {
      return item.getElement().dataset.widgetId;
    });
    var layout = JSON.stringify({ order: items });
    var user_id = document.body.dataset.userId;
    var nonce = window.apolloUserPageNonce;
    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=apollo_userpage_save&nonce=' + encodeURIComponent(nonce) + '&user_id=' + encodeURIComponent(user_id) + '&layout=' + encodeURIComponent(layout)
    }).then(r => r.json()).then(resp => {
      if (resp.success) alert('Salvo!');
      else alert('Erro: ' + resp.data);
    });
  };
});

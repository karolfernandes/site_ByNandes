function listarBanners() {
  const tbody = document.getElementById("tabelaBanners");
  const url = "../PHP/listar_banners.php?listar=1";

  const esc = s => (s || '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));

  const row = b => `
    <tr>
      <td><img src="${b.imagem || 'placeholder.jpg'}" alt="${esc(b.descricao)}" width="60" height="60"></td>
      <td>${esc(b.descricao)}</td>
      <td>${esc(b.link)}</td>
      <td>${esc(b.categoria)}</td>
      <td>${esc(b.validade)}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-primary">Editar</button>
        <button class="btn btn-sm btn-danger">Excluir</button>
      </td>
    </tr>
  `;

  fetch(url)
    .then(res => res.json())
    .then(data => {
      if (data.ok && data.banners.length > 0) {
        tbody.innerHTML = data.banners.map(row).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="6">Nenhum banner encontrado</td></tr>';
      }
    })
    .catch(err => {
      console.error("Erro ao listar banners:", err);
      tbody.innerHTML = '<tr><td colspan="6">Erro ao carregar banners</td></tr>';
    });
}

// Chamar a função ao carregar a página
document.addEventListener('DOMContentLoaded', listarBanners);

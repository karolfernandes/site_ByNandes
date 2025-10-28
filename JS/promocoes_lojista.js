// frete_lojista.js

// Função para escapar caracteres especiais (evita injeção de HTML)
const esc = s => (s || '').replace(/[&<>"']/g, c => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;'
}[c]));

// Função para listar banners cadastrados
function listarBanners(tbBanners) {
  const tbody = document.getElementById(tbBanners);
  const url = '../php/cadastro_banners.php?listar=1&format=json';

  const row = f => `
    <tr>
      <td>${Number(f.id) || ''}</td>
      <td>${esc(f.descricao || '-')}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-warning" data-id="${f.id}">Editar</button>
        <button class="btn btn-sm btn-danger" data-id="${f.id}">Excluir</button>
      </td>
    </tr>`;

  fetch(url, { cache: 'no-store' })
    .then(r => r.json())
    .then(d => {
      if (!d.ok) throw new Error('Erro ao listar banners');
      const arr = d.banners || []; // agora correto
      tbody.innerHTML = arr.length
        ? arr.map(row).join('')
        : `<tr><td colspan="3" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
    });
}


// Função para listar cupom
function listarCupom(tbCupom) {
  const tbody = document.getElementById(tbCupom);
  const url = '../PHP/cadastro_banners.php?listar=1&format=json';

  const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

  const row = f => `
    <tr>
      <td>${Number(f.id) || ''}</td>
      <td>${esc(f.nome || '-')}</td>
      <td>${esc(f.valor || '-')}</td>
      <td>${esc(f.quantidade || '-')}</td>
      <td class="text-end">${moeda.format(parseFloat(f.valor ?? 0))}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-warning" data-id="${f.id}"><i class="bi bi-pencil"></i> Editar</button>
        <button class="btn btn-sm btn-danger" data-id="${f.id}"><i class="bi bi-trash"></i> Excluir</button>
      </td>
    </tr>`;

  fetch(url, { cache: 'no-store' })
    .then(r => r.json())
    .then(d => {
      if (!d.ok) throw new Error(d.error || 'Erro ao listar cupons');
      const fretes = d.cupons || [];
      tbody.innerHTML = cupons.length
        ? cupons.map(row).join('')
        : `<tr><td colspan="5" class="text-center text-muted">Nenhum frete cadastrado.</td></tr>`;
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
    });
}

// Executa ao carregar o DOM
document.addEventListener('DOMContentLoaded', () => {
  listarBanners("tbBanners");  // chama a função ajustada
  listarCupom("tbCupons"); 
});

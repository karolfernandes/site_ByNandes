document.addEventListener("DOMContentLoaded", () => {
  // Seleciona o input do arquivo e o container da prévia
const inputFoto = document.querySelector('input[name="foto"]');
const bannerThumb = document.querySelector('.banner-thumb');

// Adiciona um listener para quando o usuário escolher um arquivo
inputFoto.addEventListener('change', () => {
  const file = inputFoto.files[0];

  if (file) {
    // Cria um objeto FileReader para ler o conteúdo do arquivo
    const reader = new FileReader();

    // Quando a leitura terminar, insere a imagem no container da prévia
    reader.onload = function(e) {
      bannerThumb.innerHTML = `<img src="${e.target.result}" alt="Prévia do banner">`;
    }

    // Lê o arquivo como URL base64
    reader.readAsDataURL(file);
  } else {
    // Se não houver arquivo, restaura o texto padrão
    bannerThumb.innerHTML = '<span class="text-muted">Prévia</span>';
  }
});

});

function listarcategorias(nomeid) {
  (async () => {
    const sel = document.querySelector(nomeid);

    try {
      const r = await fetch("../PHP/cadastro_categorias.php?listar=1");
      if (!r.ok) throw new Error("Falha ao listar categorias!");
      sel.innerHTML = await r.text();
    } catch (e) {
      sel.innerHTML = "<option disable>Erro ao carregar</option>";
    }
  })();
}

// ===============================
// FUNÇÃO PARA LISTAR CUPONS
// ===============================
function listarCupons(tbcupom) {
  const tbody = document.getElementById(tbcupom);
  if (!tbody) return;

  const esc = s => (s || '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));

  const dtbr = iso => {
    if (!iso) return '-';
    const [y,m,d] = String(iso).split('-');
    return (y && m && d) ? `${d}/${m}/${y}` : '-';
  };

  let byId = new Map();

  const row = c => {
    byId.set(String(c.id), c);
    return `
      <tr>
        <td>${c.id}</td>
        <td>${esc(c.nome)}</td>
        <td>${esc(c.codigo)}</td>
        <td>${dtbr(c.data_validade)}</td>
        <td>${esc(c.quantidade)}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-warning btn-edit" data-id="${c.id}">Selecionar</button>
          <button class="btn btn-sm btn-danger btn-delete" data-id="${c.id}">Excluir</button>
        </td>
      </tr>`;
  };

  fetch('../PHP/cadastro_cupons.php?listar=1', { cache: 'no-store' })
    .then(r => r.json())
    .then(d => {
      if (!d.ok) throw new Error(d.error || 'Erro ao listar cupons');
      const arr = d.cupons || [];
      tbody.innerHTML = arr.length
        ? arr.map(row).join('')
        : `<tr><td colspan="6" class="text-center text-muted">Nenhum cupom cadastrado.</td></tr>`;
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
    });

  // ===============================
  // SELECIONAR PARA EDIÇÃO OU EXCLUIR
  // ===============================
  tbody.addEventListener('click', ev => {
    const btn = ev.target.closest('button');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    const cupom = byId.get(String(id));
    if (!cupom) return;

    const form = document.getElementById('formCupom');
    const btnCadastrar = document.getElementById('btnCadastrarCupom');
    const btnExcluir = document.getElementById('btnExcluirCupom');

    if (btn.classList.contains('btn-edit')) {
      // preencher formulário
      form.querySelector('input[name="nome"]').value = cupom.nome || '';
      form.querySelector('input[name="codigo"]').value = cupom.codigo || '';
      form.querySelector('input[name="data_validade"]').value = cupom.data_validade || '';
      form.querySelector('input[name="quantidade"]').value = cupom.quantidade || '';
      form.querySelector('input[name="id"]').value = cupom.id;
      form.querySelector('input[name="acao"]').value = 'atualizar';

      // mudar botão principal
      btnCadastrar.textContent = 'Salvar alterações';
      btnCadastrar.classList.remove('btn-primary');
      btnCadastrar.classList.add('btn-success');

      // ativar botão excluir
      if (btnExcluir) btnExcluir.disabled = false;

      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (btn.classList.contains('btn-delete')) {
      if (!confirm('Tem certeza que deseja excluir este cupom?')) return;

      const fd = new FormData();
      fd.append('acao', 'excluir');
      fd.append('id', id);

      fetch('../PHP/cadastro_cupons.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
          if (!d.ok) throw new Error(d.error || 'Falha na exclusão');
          alert(d.message || 'Cupom excluído com sucesso!');
          form.reset();
          form.querySelector('input[name="acao"]').value = 'cadastrar';
          form.querySelector('input[name="id"]').value = '';
          btnCadastrar.textContent = 'Cadastrar';
          btnCadastrar.classList.remove('btn-success');
          btnCadastrar.classList.add('btn-primary');
          listarCupons(tbcupom);
        })
        .catch(e => alert('Erro: ' + e.message));
    }
  });
}

// ===============================
// CADASTRAR / SALVAR ALTERAÇÕES
// ===============================
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formCupom');
  const btnCadastrar = document.getElementById('btnCadastrarCupom');
  if (!form || !btnCadastrar) return;

  btnCadastrar.addEventListener('click', ev => {
    ev.preventDefault();
    const acao = form.querySelector('input[name="acao"]').value || 'cadastrar';
    const fd = new FormData(form);
    fd.set('acao', acao);

    fetch('../PHP/cadastro_cupons.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Falha na operação');
        alert(d.message || 'Operação realizada com sucesso!');
        form.reset();
        form.querySelector('input[name="acao"]').value = 'cadastrar';
        form.querySelector('input[name="id"]').value = '';
        btnCadastrar.textContent = 'Cadastrar';
        btnCadastrar.classList.remove('btn-success');
        btnCadastrar.classList.add('btn-primary');
        listarCupons('tbCupons');
      })
      .catch(e => alert('Erro: ' + e.message));
  });
});

// ===============================
// INICIALIZAÇÃO
// ===============================
document.addEventListener('DOMContentLoaded', () => {
  listarCupons('tbCupons');
});


// =============================================
// ✅ LISTAR BANNERS (com seleção e modo edição)
// =============================================
function listarBanners(tbbanner) {
  document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById(tbbanner);
    const url   = '../PHP/cadastro_banners.php?listar=1';

    let byId = new Map();

    const esc = s => (s || '').replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));

    const ph = () => 'data:image/svg+xml;base64,' + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="96" height="64">
         <rect width="100%" height="100%" fill="#eee"/>
         <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
               font-family="sans-serif" font-size="12" fill="#999">SEM IMAGEM</text>
       </svg>`
    );

    const dtbr = iso => {
      if (!iso) return '-';
      const [y,m,d] = String(iso).split('-');
      return (y && m && d) ? `${d}/${m}/${y}` : '-';
    };

    const row = b => {
      const src  = b.imagem ? `data:image/*;base64,${b.imagem}` : ph();
      const cat  = b.categoria_nome || '-';
      const link = b.link ? `<a href="${esc(b.link)}" target="_blank" rel="noopener">abrir</a>` : '-';
      byId.set(String(b.id), b);

      return `
        <tr>
          <td><img src="${src}" alt="banner"
                   style="width:96px;height:64px;object-fit:cover;border-radius:6px"></td>
          <td>${esc(b.descricao || '-')}</td>
          <td class="text-nowrap">${dtbr(b.data_validade)}</td>
          <td>${esc(cat)}</td>
          <td>${link}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-warning btn-edit" data-id="${b.id}">Selecionar</button>
          </td>
        </tr>`;
    };

    fetch(url, { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Erro ao listar banners');
        const arr = d.banners || [];
        byId = new Map();
        tbody.innerHTML = arr.length
          ? arr.map(row).join('')
          : `<tr><td colspan="6" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
      });

    // Clicar em "Selecionar" -> preencher formulário
    tbody.addEventListener('click', (ev) => {
      const btn = ev.target.closest('button');
      if (!btn) return;
      if (btn.classList.contains('btn-edit')) {
        const id = btn.getAttribute('data-id');
        const banner = byId.get(String(id));
        if (!banner) {
          alert('Não foi possível localizar os dados deste banner.');
          return;
        }
        preencherFormBanner(banner);
      }
    });
  });
}

// ========= prévia =========
function setPreview(src) {
  const previewBox =
    document.getElementById('previewBanner') ||
    document.querySelector('.banner-thumb');
  if (!previewBox) return;

  const ph = () =>
    'data:image/svg+xml;base64,' + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="320" height="160">
         <rect width="100%" height="100%" fill="#f2f2f2"/>
         <text x="50%" y="50%" font-size="14" fill="#999"
               text-anchor="middle" dominant-baseline="middle">Prévia</text>
       </svg>`
    );

  previewBox.innerHTML = '';
  const img = document.createElement('img');
  img.src = src || ph();
  img.alt = 'Prévia do banner';
  img.className = 'img-fluid';
  img.style.maxHeight = '160px';
  img.style.objectFit  = 'contain';
  previewBox.appendChild(img);
}

// ========= preencher formulário =========
function preencherFormBanner(banner) {
  const form = document.getElementById('formBanner') || document.querySelector('form');
  const acaoInput = form.querySelector('input[name="acao"]');
  const idInput = form.querySelector('input[name="id"]');
  const btnCadastrar = document.getElementById('btnCadastrar');
  const btnExcluir = document.getElementById('btnExcluir');

  form.querySelector('input[name="descricao"]').value = banner.descricao || '';
  form.querySelector('input[name="data"]').value = banner.data_validade || '';
  form.querySelector('input[name="link"]').value = banner.link || '';
  const sel = form.querySelector('select[name="categoriab"]');
  if (sel) sel.value = (banner.categoria_id ?? '') + '';

  idInput.value = banner.id;
  acaoInput.value = 'atualizar';

  const file = form.querySelector('input[name="foto"]');
  if (file) file.value = '';

  setPreview(banner.imagem ? `data:image/*;base64,${banner.imagem}` : null);

  // muda botão principal
  if (btnCadastrar) {
    btnCadastrar.textContent = 'Salvar alterações';
    btnCadastrar.classList.remove('btn-primary');
    btnCadastrar.classList.add('btn-success');
  }

  // ativa botão excluir
  if (btnExcluir) btnExcluir.disabled = false;

  form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ========= editar (salvar alterações) =========
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formBanner');
  const btnCadastrar = document.getElementById('btnCadastrar');
  const acaoInput = form.querySelector('input[name="acao"]');
  const idInput = form.querySelector('input[name="id"]');

  if (!form || !btnCadastrar) return;

  btnCadastrar.addEventListener('click', async (ev) => {
    if (btnCadastrar.textContent !== 'Salvar alterações') return; // só se estiver em modo edição
    ev.preventDefault();

    const id = idInput.value;
    if (!id) {
      alert('Nenhum banner selecionado para editar.');
      return;
    }

    const fd = new FormData(form);
    fd.set('acao', 'atualizar');
    fd.set('id', id);

    try {
      const r = await fetch('../PHP/cadastro_banners.php', { method: 'POST', body: fd });
      if (!r.ok) throw new Error('Falha ao atualizar.');
      alert('Banner atualizado com sucesso!');
      form.reset();
      setPreview(null);
      btnCadastrar.textContent = 'Cadastrar';
      btnCadastrar.classList.remove('btn-success');
      btnCadastrar.classList.add('btn-primary');
      listarBanners('tbBanners');
    } catch (e) {
      alert('Erro ao atualizar: ' + e.message);
    }
  });
});

// ========= excluir =========
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formBanner');
  const btnExcluir = document.getElementById('btnExcluir');
  const idInput = form.querySelector('input[name="id"]');
  const btnCadastrar = document.getElementById('btnCadastrar');

  if (!form || !btnExcluir) return;

  btnExcluir.addEventListener('click', async () => {
    const id = idInput.value;
    if (!id) {
      alert('Selecione um banner para excluir.');
      return;
    }

    if (!confirm('Tem certeza que deseja excluir este banner?')) return;

    const fd = new FormData();
    fd.append('acao', 'excluir');
    fd.append('id', id);

    try {
      const r = await fetch('../PHP/cadastro_banners.php', { method: 'POST', body: fd });
      if (!r.ok) throw new Error('Falha na exclusão.');

      alert('Banner excluído com sucesso!');
      form.reset();
      setPreview(null);
      idInput.value = '';
      btnCadastrar.textContent = 'Cadastrar';
      btnCadastrar.classList.remove('btn-success');
      btnCadastrar.classList.add('btn-primary');
      listarBanners('tbBanners');
     
    } catch (e) {
      alert('Erro ao excluir: ' + e.message);
    }
  });
});

listarBanners('tbBanners');
listarcategorias("#categoriabanner");
listarcategorias("#categoriasPromocoes");

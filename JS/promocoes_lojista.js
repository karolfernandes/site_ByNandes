/* ===============================
   PRÉVIA DO ARQUIVO (CADASTRAR INTACTO)
=================================*/
document.addEventListener("DOMContentLoaded", () => {
  const inputFoto   = document.querySelector('input[name="foto"]');
  const bannerThumb = document.querySelector('.banner-thumb');
  if (!inputFoto || !bannerThumb) return;

  inputFoto.addEventListener('change', () => {
    const file = inputFoto.files && inputFoto.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        bannerThumb.innerHTML = `<img src="${e.target.result}" alt="Prévia do banner">`;
      };
      reader.readAsDataURL(file);
    } else {
      bannerThumb.innerHTML = '<span class="text-muted">Prévia</span>';
    }
  });
});

/* ===============================
   LISTAR CATEGORIAS
=================================*/
function listarcategorias(nomeid) {
  (async () => {
    const sel = document.querySelector(nomeid);
    if (!sel) return;
    try {
      const r = await fetch("../PHP/cadastro_categorias.php?listar=1", { cache: 'no-store' });
      if (!r.ok) throw new Error("Falha ao listar categorias!");
      sel.innerHTML = await r.text();
    } catch (e) {
      sel.innerHTML = '<option disabled>Erro ao carregar</option>';
    }
  })();
}

/* ===============================
   CUPONS (mantido)
=================================*/
function listarCupons(tbcupom) {
  const tbody = document.getElementById(tbcupom);
  if (!tbody) return;

  const esc = s => (s || '').replace(/[&<>"']/g, c => (
    {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]
  ));
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
      form.querySelector('input[name="nome"]').value = cupom.nome || '';
      form.querySelector('input[name="codigo"]').value = cupom.codigo || '';
      form.querySelector('input[name="data_validade"]').value = cupom.data_validade || '';
      form.querySelector('input[name="quantidade"]').value = cupom.quantidade || '';
      form.querySelector('input[name="id"]').value = cupom.id;
      form.querySelector('input[name="acao"]').value = 'atualizar';

      btnCadastrar.textContent = 'Salvar alterações';
      btnCadastrar.classList.remove('btn-primary');
      btnCadastrar.classList.add('btn-success');

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



document.addEventListener('DOMContentLoaded', () => {
  listarCupons('tbCupons');
});

/* ===============================
   HELPERS COMPARTILHADOS (BANNERS)
=================================*/
const __esc = s => (s ?? '').toString().replace(/[&<>"']/g, c => (
  {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]
));
const __dtbr = iso => {
  if (!iso) return '-';
  const [y,m,d] = String(iso).split('-');
  return (y && m && d) ? `${d}/${m}/${y}` : '-';
};
const __phSmall = () => 'data:image/svg+xml;base64,' + btoa(
  `<svg xmlns="http://www.w3.org/2000/svg" width="96" height="64">
     <rect width="100%" height="100%" fill="#eee"/>
     <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
           font-family="sans-serif" font-size="12" fill="#999">SEM IMAGEM</text>
   </svg>`
);
const __phPreview = () => 'data:image/svg+xml;base64,' + btoa(
  `<svg xmlns="http://www.w3.org/2000/svg" width="320" height="160">
     <rect width="100%" height="100%" fill="#f2f2f2"/>
     <text x="50%" y="50%" font-size="14" fill="#999"
           text-anchor="middle" dominant-baseline="middle">Prévia</text>
   </svg>`
);
function setPreview(src) {
  const preview = document.getElementById('previewBanner') || document.querySelector('.banner-thumb');
  if (!preview) return;
  preview.innerHTML = '';
  const img = document.createElement('img');
  img.src = src || __phPreview();
  img.alt = 'Prévia do banner';
  img.className = 'img-fluid';
  img.style.maxHeight = '160px';
  img.style.objectFit = 'contain';
  preview.appendChild(img);
}

/* ===============================
   BANNERS — LISTAGEM / EDIÇÃO / EXCLUSÃO
   (CADASTRAR permanece pelo seu HTML/form)
=================================*/
function listarBanners(tbbanner) {
  const tbody = document.getElementById(tbbanner);
  if (!tbody) return;

  if (!tbody._byId) tbody._byId = new Map();

  const row = (b) => {
    const src  = b.imagem ? `data:image/*;base64,${b.imagem}` : __phSmall();
    const cat  = b.categoria_nome || '-';
    const link = b.link ? `<a href="${__esc(b.link)}" target="_blank" rel="noopener">abrir</a>` : '-';
    tbody._byId.set(String(b.id), b);
    return `
      <tr>
        <td><img src="${src}" alt="banner"
                 style="width:96px;height:64px;object-fit:cover;border-radius:6px"></td>
        <td>${__esc(b.descricao || '-')}</td>
        <td class="text-nowrap">${__dtbr(b.data_validade)}</td>
        <td>${__esc(cat)}</td>
        <td>${link}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-warning btn-edit" data-id="${b.id}">Selecionar</button>
        </td>
      </tr>`;
  };

  const showError = (msg) => {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${__esc(msg)}</td></tr>`;
  };

  fetch('../PHP/cadastro_banners.php?listar=1', { cache: 'no-store' })
    .then(async (r) => {
      const ct = r.headers.get('content-type') || '';
      if (!r.ok) {
        const body = ct.includes('application/json') ? JSON.stringify(await r.json()) : await r.text();
        throw new Error(`HTTP ${r.status} – ${body}`);
      }
      if (ct.includes('application/json')) return r.json();
      const text = await r.text();
      throw new Error(`Resposta não-JSON do servidor: ${text}`);
    })
    .then(d => {
      if (!d || d.ok !== true) {
        const detail = d && (d.error || d.detail) ? ` (${d.error || d.detail})` : '';
        throw new Error(`Erro ao listar banners${detail}`);
      }
      const arr = d.banners || [];
      tbody._byId.clear();
      tbody.innerHTML = arr.length
        ? arr.map(row).join('')
        : `<tr><td colspan="6" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;
    })
    .catch(err => showError(`Falha ao carregar: ${err.message}`));

  // bind único
  if (!tbody.dataset.bannersBound) {
    tbody.dataset.bannersBound = '1';
    tbody.addEventListener('click', ev => {
      const btn = ev.target.closest('button.btn-edit');
      if (!btn) return;
      const id = btn.getAttribute('data-id');
      const banner = tbody._byId.get(String(id));
      if (!banner) return alert('Não foi possível localizar os dados deste banner.');
      preencherFormBanner(banner);
    });
  }
}

function preencherFormBanner(banner) {
  const form         = document.getElementById('formBanner') || document.querySelector('form');
  if (!form) return;

  const acaoInput    = form.querySelector('input[name="acao"]');
  const idInput      = form.querySelector('input[name="id"]');
  const btnCadastrar = document.getElementById('btnCadastrar');
  const btnExcluir   = document.getElementById('btnExcluir');

  (form.querySelector('input[name="descricao"]') || {}).value = banner.descricao || '';
  (form.querySelector('input[name="data"]') || {}).value      = banner.data_validade || '';
  (form.querySelector('input[name="link"]') || {}).value      = banner.link || '';
  const sel = form.querySelector('select[name="categoriab"]');
  if (sel) sel.value = (banner.categoria_id ?? '') + '';

  if (idInput)   idInput.value   = banner.id;
  if (acaoInput) acaoInput.value = 'atualizar';

  const file = form.querySelector('input[name="foto"]');
  if (file) file.value = '';

  setPreview(banner.imagem ? `data:image/*;base64,${banner.imagem}` : null);

  if (btnCadastrar) {
    btnCadastrar.textContent = 'Salvar alterações';
    btnCadastrar.classList.remove('btn-primary');
    btnCadastrar.classList.add('btn-success');
  }
  if (btnExcluir) btnExcluir.disabled = false;

  form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.addEventListener('DOMContentLoaded', () => {
  const form         = document.getElementById('formBanner');
  const btnCadastrar = document.getElementById('btnCadastrar');
  const idInput      = form ? form.querySelector('input[name="id"]') : null;
  if (!form || !btnCadastrar) return;

  // Intercepta apenas quando estiver em modo edição
  btnCadastrar.addEventListener('click', async (ev) => {
    if (btnCadastrar.textContent !== 'Salvar alterações') return;
    ev.preventDefault();

    const id = idInput && idInput.value;
    if (!id) return alert('Nenhum banner selecionado para editar.');

    const fd = new FormData(form);
    fd.set('acao', 'atualizar');
    fd.set('id', id);

    try {
      const r = await fetch('../PHP/cadastro_banners.php', { method: 'POST', body: fd });
      if (!(r.ok || r.redirected)) throw new Error('Falha ao atualizar.');

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

document.addEventListener('DOMContentLoaded', () => {
  const form         = document.getElementById('formBanner');
  const btnExcluir   = document.getElementById('btnExcluir');
  const idInput      = form ? form.querySelector('input[name="id"]') : null;
  const btnCadastrar = document.getElementById('btnCadastrar');
  if (!form || !btnExcluir) return;

  btnExcluir.addEventListener('click', async () => {
    const id = idInput && idInput.value;
    if (!id) return alert('Selecione um banner para excluir.');
    if (!confirm('Tem certeza que deseja excluir este banner?')) return;

    const fd = new FormData();
    fd.append('acao', 'excluir');
    fd.append('id', id);

    try {
      const r = await fetch('../PHP/cadastro_banners.php', { method: 'POST', body: fd });
      if (!(r.ok || r.redirected)) throw new Error('Falha na exclusão.');

      alert('Banner excluído com sucesso!');
      form.reset();
      setPreview(null);
      if (idInput) idInput.value = '';
      if (btnCadastrar) {
        btnCadastrar.textContent = 'Cadastrar';
        btnCadastrar.classList.remove('btn-success');
        btnCadastrar.classList.add('btn-primary');
      }
      listarBanners('tbBanners');
    } catch (e) {
      alert('Erro ao excluir: ' + e.message);
    }
  });
});

/* ===============================
   INICIALIZAÇÃO
=================================*/
document.addEventListener('DOMContentLoaded', () => {
  listarBanners('tbBanners');            // tabela de banners
  listarcategorias("#categoriabanner");  // se existir
  listarcategorias("#categoriasPromocoes");
});

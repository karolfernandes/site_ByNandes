/* === carregar categorias (como já estava) === */
function listarcategorias(nomeid){ (async () => {
  const sel = document.querySelector(nomeid);
  try {
    const r = await fetch("../PHP/cadastro_categorias.php?listar=1");
    if (!r.ok) throw new Error("Falha ao listar categorias!");
    sel.innerHTML = await r.text();
  } catch (e) {
    sel.innerHTML = "<option disabled>Erro ao carregar</option>";
  }
})(); }

listarcategorias("#pCategoria");
listarcategorias("#proCategoria"); // SELECT no formulário


/* ================= produtos_lojista.js (com JSON fix no POST) ================= */
(() => {
  const ENDPOINT = '../PHP/cadastro_produtos.php';
  const TBODY_ID = 'tbProdutos';
  const FORM_SEL = 'form[action="../PHP/cadastro_produtos.php"]';

  const $ = (s, c=document)=>c.querySelector(s);
  const $$= (s, c=document)=>Array.from(c.querySelectorAll(s));
  const esc = s => (s??'').toString().replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const fmtBRL = v => (typeof v==='number' && !Number.isNaN(v)) ? v.toLocaleString('pt-BR',{style:'currency',currency:'BRL'}) : '-';
  const ph = (nome='?')=>'data:image/svg+xml;base64,'+btoa(
    `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">
       <rect width="100%" height="100%" fill="#eee"/>
       <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
             font-family="Arial, sans-serif" font-size="14" fill="#888">
         ${(esc(nome)).slice(0,2).toUpperCase()}
       </text>
     </svg>`
  );
  const precoHTML = m => (typeof m.precoPromocional==='number' && m.precoPromocional>0)
    ? `<span style="text-decoration:line-through;opacity:.7;margin-right:.5rem">${fmtBRL(m.preco)}</span><strong>${fmtBRL(m.precoPromocional)}</strong>`
    : `<strong>${fmtBRL(m.preco)}</strong>`;

  function toast(msg, kind='info'){
    console[kind==='error'?'error':'log'](msg);
    const el = document.createElement('div');
    el.className = 'position-fixed top-0 start-50 translate-middle-x mt-3 px-3 py-2 rounded shadow-sm text-white';
    el.style.zIndex = 1080;
    el.style.background = kind==='error' ? '#dc3545' : (kind==='success' ? '#198754' : '#0d6efd');
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(()=>el.remove(), 2200);
  }

  (function ensureCSS(){
    if (document.getElementById('row-selected-css')) return;
    const s = document.createElement('style');
    s.id = 'row-selected-css';
    s.textContent = `
      .row-selected { outline: 2px solid #0d6efd; background: rgba(13,110,253,.08); }
      @keyframes pulse {0%{opacity:.6} 50%{opacity:1} 100%{opacity:.6}}
    `;
    document.head.appendChild(s);
  })();

  let _cache = [];
  let _selectedId = null;

  async function listar() {
    const tbody = document.getElementById(TBODY_ID);
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="5">
      <div style="height:16px;background:#eee;border-radius:6px;animation:pulse 1.2s infinite"></div>
    </td></tr>`;

    try {
      const url = new URL(ENDPOINT, location.href);
      url.searchParams.set('listar','1');
      const r = await fetch(url.toString(), { headers:{ 'Accept':'application/json' } });
      const txt = await r.text();
      let data;
      try { data = JSON.parse(txt); } catch { throw new Error('Resposta não é JSON: '+txt.slice(0,180)); }
      if (!r.ok) throw new Error(data?.error || (`HTTP ${r.status}`));
      if (!data.ok) throw new Error(data.error || 'Falha na listagem');

      const arr = Array.isArray(data.produtos) ? data.produtos : [];
      _cache = arr.sort((a,b)=> (a.nome||'').localeCompare(b.nome||'', 'pt-BR'));

      if (!_cache.length) {
        tbody.innerHTML = `<tr><td colspan="5">Nenhum produto cadastrado.</td></tr>`;
        _selectedId = null;
        habilitarBotoes(false);
        return;
      }

      tbody.innerHTML = _cache.map(m=>`
        <tr data-id="${m.id}" class="${m.id===_selectedId?'row-selected':''}">
          <td>${esc(m.nome)}</td>
          <td>${esc(m.descricao)}</td>
          <td class="text-start">${precoHTML(m)}</td>
          <td><img src="${m.imagem||ph(m.nome)}" alt="${esc(m.nome)}" width="60" height="60"
                   style="object-fit:cover;border-radius:8px"
                   onerror="this.src='${ph(m.nome)}'"></td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary btn-select" data-id="${m.id}">
              Selecionar
            </button>
          </td>
        </tr>
      `).join('');

      // clique no botão "Selecionar"
      tbody.querySelectorAll('.btn-select').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const id = parseInt(btn.dataset.id, 10);
          _selectedId = id;
          tbody.querySelectorAll('tr').forEach(t=>t.classList.remove('row-selected'));
          btn.closest('tr')?.classList.add('row-selected');
          carregarNoForm(id);
          habilitarBotoes(true);
        });
      });

    } catch (e) {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="5" class="text-danger">Erro ao listar produtos.</td></tr>`;
      toast(e.message, 'error');
    }
  }

  function ensureHiddenId(form){
    let hid = form.querySelector('input[name="id"]#pId');
    if (!hid){
      hid = document.createElement('input');
      hid.type='hidden'; hid.name='id'; hid.id='pId';
      form.appendChild(hid);
    }
    return hid;
  }

  function getBtn(form, value){
    return $$('button[type="submit"]', form).find(b => (b.value||'').toLowerCase() === value);
  }

  function habilitarBotoes(estado){
    const form = $(FORM_SEL);
    if (!form) return;
    const bEdit = getBtn(form, 'editar');
    const bDel  = getBtn(form, 'excluir');
    if (bEdit) bEdit.disabled = !estado;
    if (bDel)  bDel.disabled  = !estado;
  }

  async function carregarNoForm(id){
    const p = _cache.find(x=>x.id===id);
    if (!p) return;

    const form = $(FORM_SEL);
    if (!form) return;

    ensureHiddenId(form).value = id;
    $('#pNome').value        = p.nome ?? '';
    $('#pDescricao').value   = p.descricao ?? '';
    $('#pQtd').value         = p.quantidade ?? 0;
    $('#pPreco').value       = p.preco ?? '';
    $('#pTamanho').value     = p.tamanho ?? '';
    $('#pCor').value         = p.cor ?? '';
    $('#pCodigo').value      = p.codigo ?? '';
    $('#pPrecoPromo').value  = p.precoPromocional ?? '';

    // Pré-vias: usa a imagem principal (se houver) no slot 1
    const img1Prev = document.getElementById('pImg1Prev');
    const ph1 = document.getElementById('pImg1Ph');
    if (img1Prev && ph1) {
      img1Prev.src = p.imagem || ph(p.nome);
      img1Prev.classList.remove('d-none');
      ph1.classList.add('d-none');
    }

    // (inputs type="file" não podem ser preenchidos por JS)
    toast('Produto carregado para edição.');
    form.scrollIntoView({behavior:'smooth'});
  }

  async function enviar(acao, form){
    const fd = new FormData(form);
    fd.set('acao', acao);
    fd.set('json', '1'); // <<< ESSENCIAL: força JSON no PHP

    if ((acao==='editar' || acao==='excluir') && !fd.get('id')) {
      throw new Error('Selecione um produto na tabela (botão "Selecionar") para editar/excluir.');
    }

    const r = await fetch(ENDPOINT, {
      method:'POST',
      body: fd,
      headers: { 'Accept': 'application/json' } // <<< ajuda o wants_json()
    });

    const txt = await r.text();
    let data;
    try { data = JSON.parse(txt); } catch { throw new Error('Resposta não é JSON: '+txt.slice(0,220)); }
    if (!r.ok || !data.ok) throw new Error(data?.error || `HTTP ${r.status}`);
    return data;
  }

  function bindForm(){
    const form = $(FORM_SEL);
    if (!form) return;

    ensureHiddenId(form);
    habilitarBotoes(false); // desabilita Editar/Excluir no início

    form.addEventListener('submit', async ev=>{
      ev.preventDefault();
      const btn = ev.submitter;
      const acao = (btn?.value||'').toLowerCase();
      if (!acao) { toast('Ação não identificada', 'error'); return; }

      try {
        const data = await enviar(acao, form);
        if (acao==='cadastrar') toast(data.msg || 'Produto cadastrado com sucesso!', 'success');
        if (acao==='editar')    toast(data.msg || 'Produto atualizado com sucesso!', 'success');
        if (acao==='excluir')   toast(data.msg || 'Produto excluído com sucesso!', 'success');

        await listar();               // recarrega tabela
        if (acao!=='editar') form.reset();
        $('#pId').value = '';
        _selectedId = null;
        habilitarBotoes(false);

      } catch (e) {
        console.error(e);
        toast(e.message || 'Falha na operação', 'error');
      }
    });
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    bindForm();
    listar();
  });

  window.listarProdutos = listar; // opcional
})();
function clearPreview(imgId, phId, fileId){
  const img  = document.getElementById(imgId);
  const ph   = document.getElementById(phId);
  const file = document.getElementById(fileId);
  if (img)  { img.src = ''; img.classList.add('d-none'); }
  if (ph)   { ph.classList.remove('d-none'); }
  if (file) { file.value = ''; }
}

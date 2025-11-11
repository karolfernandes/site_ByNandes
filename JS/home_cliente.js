// JS/home_cliente.js

(() => {
  /* ================== Utils ================== */
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const esc = (s) => (s ?? "").toString()
    .replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const BRL = (n) => (typeof n === "number" && !Number.isNaN(n))
    ? n.toLocaleString("pt-BR", { style: "currency", currency: "BRL" })
    : "-";
  const imgPh = (w = 1200, h = 400, txt = "SEM IMAGEM") =>
    "data:image/svg+xml;base64," + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}">
         <rect width="100%" height="100%" fill="#e9ecef"/>
         <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
               font-family="Arial, sans-serif" font-size="${Math.max(16, Math.min(w,h)/15)}" fill="#6c757d">
           ${esc(txt)}
         </text>
       </svg>`
    );

  function toast(msg, kind = "info") {
    console[kind === "error" ? "error" : "log"](msg);
    const el = document.createElement("div");
    el.className = "position-fixed top-0 start-50 translate-middle-x mt-3 px-3 py-2 rounded shadow-sm text-white";
    el.style.zIndex = 1080;
    el.style.background = kind === "error" ? "#dc3545" : (kind === "success" ? "#198754" : "#0d6efd");
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2200);
  }

  /* ================== Áreas no HTML ================== */
  const bannersInner = $("#banners-home");
  const bannersIndicators = $("#banners-indicators");
  const main = $("main.container");

  // container dos produtos (vamos pegar a .row existente e substituir)
  let produtosRow = $("main.container .row.text-center");
  if (!produtosRow) {
    // fallback: cria uma linha se não existir
    produtosRow = document.createElement("div");
    produtosRow.className = "row text-center";
    main?.appendChild(produtosRow);
  }

  // cria/insere barra de categorias logo acima dos produtos
  let categoriasBar = document.createElement("div");
  categoriasBar.className = "d-flex gap-2 flex-wrap align-items-center mb-4";
  categoriasBar.id = "categorias-bar";
  categoriasBar.innerHTML = `
    <button type="button" class="btn btn-sm btn-dark" data-cat="all">Todas</button>
    <div class="d-flex gap-2 flex-wrap" id="categorias-chips"></div>
  `;
  // insere antes da linha de produtos
  produtosRow.parentElement?.insertBefore(categoriasBar, produtosRow);

  const categoriasChips = $("#categorias-chips");

  /* ================== Banners ================== */
  async function carregarBanners() {
    if (!bannersInner) return;
    try {
      const r = await fetch("PHP/cadastro_banners.php?listar=1", { headers: { "Accept": "application/json" } });
      const txt = await r.text();
      let data;
      try { data = JSON.parse(txt); } catch { throw new Error("Banners: resposta não é JSON: " + txt.slice(0, 180)); }
      if (!r.ok || !data.ok) throw new Error(data?.error || `HTTP ${r.status}`);

      const arr = Array.isArray(data.banners) ? data.banners : [];
      if (!arr.length) {
        bannersInner.innerHTML = `
          <div class="carousel-item active"><img class="d-block w-100" src="${imgPh(1200,400,'SEM BANNERS')}" alt="Sem banners"></div>`;
        if (bannersIndicators) bannersIndicators.innerHTML = '';
        return;
      }

      // monta slides
      bannersInner.innerHTML = arr.map((b, i) => {
        const src = b.imagem ? `data:image/jpeg;base64,${b.imagem}` : imgPh(1200, 400, b.descricao || "Banner");
        const alt = esc(b.descricao || `Banner ${i + 1}`);
        const imgTag = `<img class="d-block w-100" src="${src}" alt="${alt}" />`;
        const slideInner = b.link ? `<a href="${esc(b.link)}" target="_blank" rel="noopener">${imgTag}</a>` : imgTag;
        return `<div class="carousel-item ${i === 0 ? "active" : ""}">${slideInner}</div>`;
      }).join("");

      // indicadores
      if (bannersIndicators) {
        bannersIndicators.innerHTML = arr.map((_, i) =>
          `<button type="button" data-bs-target="#carouselBanners" data-bs-slide-to="${i}" class="${i === 0 ? "active" : ""}" aria-label="Slide ${i + 1}"></button>`
        ).join("");
      }
    } catch (e) {
      console.error(e);
      bannersInner.innerHTML = `
        <div class="carousel-item active"><img class="d-block w-100" src="${imgPh(1200,400,'ERRO AO CARREGAR')}" alt="Erro"></div>`;
      if (bannersIndicators) bannersIndicators.innerHTML = '';
      toast("Falha ao carregar banners", "error");
    }
  }

  /* ================== Categorias ================== */
  // tenta JSON (?json=1); se voltar HTML com <option>, faz parsing
  async function carregarCategorias() {
    try {
      let url = new URL("PHP/cadastro_categorias.php", location.href);
      url.searchParams.set("listar", "1");
      url.searchParams.set("json", "1"); // caso seu PHP suporte

      const r = await fetch(url.toString(), { headers: { "Accept": "application/json" } });
      const text = await r.text();

      // tentativa JSON
      try {
        const data = JSON.parse(text);
        if (data && data.ok && Array.isArray(data.categorias)) {
          return data.categorias.map(c => ({
            id: Number(c.id ?? c.idCategoria ?? c.idCategorias ?? 0),
            nome: String(c.nome ?? c.Nome_categoria ?? "Categoria")
          })).filter(c => c.id > 0);
        }
        // se não vier nesse formato, cai no parsing HTML
      } catch (_) {
        // parse de <option value="id">Nome</option>
        const out = [];
        const div = document.createElement("div");
        div.innerHTML = text;
        div.querySelectorAll("option").forEach(opt => {
          const val = opt.getAttribute("value");
          const label = opt.textContent?.trim();
          if (val && /^\d+$/.test(val) && label) {
            out.push({ id: Number(val), nome: label });
          }
        });
        if (out.length) return out;
      }

      // sem categorias
      return [];
    } catch (e) {
      console.error(e);
      toast("Falha ao carregar categorias", "error");
      return [];
    }
  }

  function renderCategorias(cats) {
    if (!categoriasChips) return;
    categoriasChips.innerHTML = cats.map(c =>
      `<button type="button" class="btn btn-sm btn-outline-dark" data-cat="${c.id}">${esc(c.nome)}</button>`
    ).join("");

    // delegação de evento (inclui botão "Todas" que fica fora de categoriasChips)
    categoriasBar.addEventListener("click", (ev) => {
      const btn = ev.target.closest("button[data-cat]");
      if (!btn) return;
      const cat = btn.getAttribute("data-cat");
      // ativa visualmente
      $$("button[data-cat]", categoriasBar).forEach(b => b.classList.remove("active", "btn-dark"));
      btn.classList.add("active");
      if (cat === "all") {
        btn.classList.add("btn-dark");
        listarProdutos();          // todas
      } else {
        btn.classList.add("btn-dark");
        listarProdutos({ categoriaId: Number(cat) }); // filtrada
      }
    });
  }

  /* ================== Produtos ================== */
  async function listarProdutos({ categoriaId = null } = {}) {
    if (!produtosRow) return;
    // skeleton
    produtosRow.innerHTML = `
      <div class="col-12 py-4 text-center text-muted">Carregando produtos…</div>
    `;

    try {
      const url = new URL("../PHP/cadastro_produtos.php", location.href);
      url.searchParams.set("listar", "1");
      if (categoriaId && Number.isFinite(categoriaId)) {
        url.searchParams.set("categoria_id", String(categoriaId));
      }
      const r = await fetch(url.toString(), { headers: { "Accept": "application/json" } });
      const txt = await r.text();
      let data;
      try { data = JSON.parse(txt); } catch { throw new Error("Produtos: resposta não é JSON: " + txt.slice(0, 200)); }
      if (!r.ok || !data.ok) throw new Error(data?.error || `HTTP ${r.status}`);

      const arr = Array.isArray(data.produtos) ? data.produtos : [];
      if (!arr.length) {
        produtosRow.innerHTML = `<div class="col-12 py-4 text-center text-muted">Nenhum produto encontrado.</div>`;
        return;
      }

      produtosRow.innerHTML = arr.map(p => {
        const nome  = esc(p.nome ?? "Produto");
        const desc  = esc(p.descricao ?? "");
        const preco = (typeof p.precoPromocional === "number" && p.precoPromocional > 0)
          ? `<div><span class="text-muted text-decoration-line-through me-2">${BRL(p.preco)}</span>
               <strong>${BRL(p.precoPromocional)}</strong></div>`
          : `<div><strong>${BRL(p.preco)}</strong></div>`;
        const src   = p.imagem || imgPh(600, 400, nome.slice(0, 16) || "Produto");

        return `
          <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm">
              <img class="card-img-top" src="${src}" alt="${nome}"
                   onerror="this.src='${imgPh(600,400,'SEM IMAGEM')}'"
                   style="object-fit:cover; height: 220px;">
              <div class="card-body d-flex flex-column">
                <h6 class="card-title mb-1" title="${nome}">${nome}</h6>
                <small class="text-muted flex-grow-1">${desc}</small>
                <div class="mt-2">${preco}</div>
                <button class="btn btn-sm btn-primary mt-3" type="button">Ver detalhes</button>
              </div>
            </div>
          </div>
        `;
      }).join("");

    } catch (e) {
      console.error(e);
      produtosRow.innerHTML = `<div class="col-12 py-4 text-center text-danger">Erro ao carregar produtos.</div>`;
      toast(e.message, "error");
    }
  }

  /* ================== Boot ================== */
  document.addEventListener("DOMContentLoaded", async () => {
    await carregarBanners();

    const cats = await carregarCategorias();
    renderCategorias(cats);

    // Estado inicial: Todas
    const btnTodas = categoriasBar.querySelector('button[data-cat="all"]');
    if (btnTodas) btnTodas.classList.add("active", "btn-dark");

    await listarProdutos(); // sem filtro
  });
})();

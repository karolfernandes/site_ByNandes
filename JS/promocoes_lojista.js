document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector('input[name="img"]'); // ← nome certo do input
  const previewBox = document.querySelector(".banner-thumb");
  if (!input || !previewBox) return;

  input.addEventListener("change", () => {
    const file = input.files && input.files[0];

    if (!file) {
      previewBox.innerHTML = '<span class="text-muted">Prévia</span>';
      return;
    }
    if (!file.type.startsWith("image/")) {
      previewBox.innerHTML = '<span class="text-danger small">Arquivo inválido</span>';
      input.value = "";
      return;
    }

    const reader = new FileReader();
    reader.onload = e => {
      previewBox.innerHTML = `<img src="${e.target.result}" alt="Prévia do banner" 
      style="max-width:100%; max-height:160px; object-fit:contain;">`;
    };
    reader.readAsDataURL(file);
  });
});

/* ==================== LISTAR BANNERS ==================== */
async function listarBanners() {
  const tbody = document.getElementById("tbBanners");
  if (!tbody) return;
  tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Carregando...</td></tr>`;

  try {
    // caminho certo pro seu PHP
    const res = await fetch("../PHP/cadastro_banners.php?listar=1", { cache: "no-store" });
    const data = await res.json();

    if (!data.ok) throw new Error(data.error || "Erro ao listar banners");
    const banners = data.banners || [];

    if (banners.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;
      return;
    }

    const rows = banners.map(b => `
      <tr>
        <td class="text-center">
          ${b.Imagem_banner
            ? `<img src="data:image/jpeg;base64,${b.Imagem_banner}" alt="${b.Descricao}" style="width:96px;height:64px;object-fit:cover;border-radius:6px;">`
            : '<span class="text-muted">Sem imagem</span>'}
        </td>
        <td>${b.Descricao || '-'}</td>
        <td>${b.link ? `<a href="${b.link}" target="_blank">${b.link}</a>` : '-'}</td>
        <td>${b.Nome_categoria || '— Sem vínculo —'}</td>
        <td>${b.Data_validade || '-'}</td>
        <td class="text-end text-muted">—</td>
      </tr>
    `).join("");

    tbody.innerHTML = rows;
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Falha ao carregar: ${err.message}</td></tr>`;
  }
}

/* ==================== EXECUTAR AO ABRIR ==================== */
document.addEventListener("DOMContentLoaded", listarBanners);

function listarcategorias(nomeid){ (async () => {

// selecionando o elemento html da tela de cadastro de produtos
 const sel = document.querySelector(nomeid); try {

  // criando a váriavel que guardar os dados vindo do php, que estão no metodo de listar
   const r = await fetch("../PHP/cadastro_categorias.php?listar=1");
    // se o retorno do php vier false, significa que não foi possivel listar os dados
if (!r.ok) throw new Error("Falha ao listar categorias!"); /* se vier dados do php, ele joga as informações dentro do campo html em formato de texto innerHTML- inserir dados em elementos html */
sel.innerHTML = await r.text(); } catch (e) { 
  // se dê erro na listagem, aparece Erro ao carregar dentro do campo html 
  sel.innerHTML = "<option disable>Erro ao carregar</option>" } })(); }




function listarProdutos(nometabelaprodutos) {
  document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById(nometabelaprodutos);
    const url = '../PHP/testeProdutos.php?listar=1';

    // Função para escapar caracteres especiais (proteção contra quebra de HTML)
    const esc = s => (s || '').replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    // Função que gera uma imagem SVG base64 com as iniciais do produto (quando não tem imagem)
    const ph = n => 'data:image/svg+xml;base64,' + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">
         <rect width="100%" height="100%" fill="#eee"/>
         <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
               font-family="sans-serif" font-size="12" fill="#999">
           ${(n || '?').slice(0, 2).toUpperCase()}
         </text>
       </svg>`
    );

    // Função para criar a linha da tabela, com os dados do produto
    const row = m => `
      <tr>
        <td>${esc(m.Nome || 'Produto')}</td>
        <td>${esc(m.Descricao || '-')}</td>
        <td>${esc(m.Preco || '-')}</td>
        <td><img src="${m.Imagem || ph(m.NomeProduto)}" alt="${esc(m.NomeProduto)}" width="60" height="60"></td>
      </tr>
    `;

    // Buscar os produtos do backend
    fetch(url)
      .then(res => res.json())
      .then(data => {
        
        if (data.ok && data.produtos) {
          // Preencher o tbody com as linhas
          tbody.innerHTML = data.produtos.map(row).join('');
        } else {
          tbody.innerHTML = '<tr><td colspan="4">Nenhum produto encontrado ou erro no backend</td></tr>';
        }
      })
      .catch(err => {
        console.error('Erro na requisição:', err);
        tbody.innerHTML = '<tr><td colspan="4">Erro ao carregar produtos</td></tr>';
      });
  });
}
listarcategorias("#pCategoria");
listarcategorias("#proCategoria"); // SELECT de categorias no formulário de produtos 
listarProdutos("tabelaProdutos");

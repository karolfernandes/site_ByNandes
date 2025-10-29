/*comentário grande*/
-- comentário pequeno

-- Criação do banco de dados
CREATE DATABASE ByNandes ;

-- excluir o banco de dados
-- Drop database bynandes;

-- comando parar usar o banco de dados
use bynandes;

-- criando tabelas

-- Tabela: Empresa
CREATE TABLE Empresa (
    idEmpresa INT AUTO_INCREMENT PRIMARY KEY,
    Nome_Fantasia VARCHAR(100),
    Cnpj_Cpf INT,
    Telefone VARCHAR(20),
    Instagram VARCHAR(100),
    Whatsapp VARCHAR(100),
    Facebook VARCHAR(100),
    Logo VARCHAR(800) not null ,
    Usuario VARCHAR(45) not null unique,
    Senha VARCHAR(100) not null,
    Linkedin VARCHAR(100)
) ;
insert into Empresa (Nome_Fantasia,Cnpj_Cpf,Telefone,Instagram,Whatsapp,Facebook,
Usuario,Senha) values ('ByNandes','40028922','(63)40028922','@ByNandes',
'(63)40028922','facebook.com/bynandes','Anna Karolina','40028922');
select * from Empresa;
select * from Cliente;

INSERT INTO Cliente (nome, cpf, telefone, email, senha, foto_perfil)
VALUES (
  'João da Silva',
  '12345678901',
  '(11) 98765-4321',
  'joao.silva@example.com',
  'senha123',
  NULL
);



-- Tabela: Cliente
CREATE TABLE Cliente(
idCliente int primary key auto_increment,
nome varchar(150) not null,
cpf varchar(20) not null unique,
telefone varchar(20),
email varchar(100) not null,
senha varchar(12) not null,
foto_perfil longblob  );

-- Tabela: Endereco
CREATE TABLE Endereco (
    idEndereco INT AUTO_INCREMENT PRIMARY KEY,
    Cep INT not null,
    Cidade VARCHAR(100) not null,
    Estado VARCHAR(100) not null,
    Numero VARCHAR(20),
    Complemento VARCHAR(100),
    TipoLogradouro VARCHAR(20),
    Bairro VARCHAR(100),
    Tipo VARCHAR(45)
) ;

-- Tabela: Endereco_has_Cliente
CREATE TABLE Endereco_e_Cliente (
    Endereco_idEndereco INT,
    Cliente_idCliente INT,
    
  foreign key(Cliente_idCliente) REFERENCES Cliente(idCliente),
  foreign key(Endereco_idEndereco) REFERENCES Endereco(idEndereco)
) ;

-- Tabela: FormaPagamento
CREATE TABLE FormaPagamento (
    idFormaPagamento INT AUTO_INCREMENT PRIMARY KEY,
    NomePagamento VARCHAR(45),
    FormaPagamento VARCHAR(45)
) ;

-- Tabela: CupomDesconto
CREATE TABLE CupomDesconto (
    idCupomDesconto INT AUTO_INCREMENT PRIMARY KEY,
    Cod_desconto VARCHAR(45) not null,
    Dias_validade DATE not null,
    Quantidade VARCHAR(45) not null,
    Nome_desconto VARCHAR(45)
) ;


-- Tabela: Frete
CREATE TABLE Frete (
    idFrete INT AUTO_INCREMENT PRIMARY KEY,
    Valor_frete DOUBLE,
    Bairro VARCHAR(45),
    Transportadora VARCHAR(45)
) ;
-- drop table Frete;

-- Tabela: Vendas
CREATE TABLE Vendas (
    
    idVendas INT AUTO_INCREMENT PRIMARY KEY,
    DataVendas DATE NOT NULL,
    Valor_frete DOUBLE NOT NULL,
    Valor_Produto DOUBLE,
    Valor_total DOUBLE,
    Forma_pagamento_idFormaPagamento INT NOT NULL,
    Cliente_idCliente INT NOT NULL,
    Cpf_Cliente INT,
    CupomDesconto_idCupomDesconto INT,
    Frete_idFrete INT,
    Data_entrega DATE,
    situacao VARCHAR(45) NOT NULL,
    Cod_pix VARCHAR(45),
    Cod_barras VARCHAR(45),
    Valor_local DOUBLE,
    Valor_desconto DOUBLE,

    FOREIGN KEY (Forma_pagamento_idFormaPagamento) REFERENCES FormaPagamento(idFormaPagamento),
    FOREIGN KEY (Cliente_idCliente) REFERENCES Cliente(idCliente),
    FOREIGN KEY (CupomDesconto_idCupomDesconto) REFERENCES CupomDesconto(idCupomDesconto),
    FOREIGN KEY (Frete_idFrete) REFERENCES Frete(idFrete)

) ;

select * from produtos;
select * from categorias;

-- Tabela: Produtos
CREATE TABLE Produtos (
    idProdutos INT AUTO_INCREMENT PRIMARY KEY,
    NomeProduto VARCHAR(100) not null,
    Descricao TEXT(500) not null,
    Quantidade INT not null,
    Preco DOUBLE not null,
    Tamanho VARCHAR(45),
    Cor VARCHAR(45),
    Codigo INT not null,
    Preco_Promocional DOUBLE
) ;

-- Tabela: Vendas_has_Produtos
CREATE TABLE Vendas_has_Produtos (
    Vendas_idVendas INT,
    Produtos_idProdutos INT,
    
     constraint foreign key(Vendas_idVendas) REFERENCES Vendas(idVendas),
    constraint foreign key(Produtos_idProdutos) REFERENCES Produtos(idProdutos)
) ;
-- drop table Vendas_has_Produtos;

-- Tabela: Vendas_has_Produto_Cliente
CREATE TABLE Vendas_has_Produto_Cliente (
    Vendas_idVendas INT,
    Cliente_idCliente INT,
    Cpf INT,
    Produtos_idProdutos INT,
    
     constraint foreign key(Vendas_idVendas) REFERENCES Vendas(idVendas),
     constraint foreign key(Cliente_idCliente) REFERENCES Cliente(idCliente),
    constraint foreign key(Produtos_idProdutos) REFERENCES Produtos(idProdutos)
    
) ;
select * from Categorias;
DELETE FROM categorias
WHERE idCategorias =8;


-- Tabela: Categorias
CREATE TABLE Categorias (
    idCategorias INT AUTO_INCREMENT PRIMARY KEY,
    Nome_categoria VARCHAR(100),
    Desconto DOUBLE,
    Imagem VARCHAR(800),
    Validade_desconto DATE,
    Categoriaurl VARCHAR(45)
 

);
-- drop table Categorias;

-- Tabela: Produtos_has_Categorias
CREATE TABLE Produtos_has_Categorias (
    Produtos_idProdutos INT,
    Categorias_idCategorias INT,
	constraint foreign key(Produtos_idProdutos) REFERENCES Produtos(idProdutos),
	constraint foreign key(Categorias_idCategorias) REFERENCES Categorias(idCategorias)
) ;

-- Tabela: ImagemProduto
CREATE TABLE ImagemProduto (
    idImagemProduto INT AUTO_INCREMENT PRIMARY KEY,
    Foto LONGBLOB,
    Descricao VARCHAR(45)
) ;

-- Tabela: Produtos_has_ImagemProduto
CREATE TABLE Produtos_has_ImagemProduto (
    Produtos_idProdutos INT,
    ImagemProduto_idImagemProduto INT,
    
       constraint foreign key(Produtos_idProdutos) REFERENCES Produtos(idProdutos),
     constraint foreign key(ImagemProduto_idImagemProduto) REFERENCES ImagemProduto(idImagemProduto)
) ;

-- Tabela: Banner
CREATE TABLE Banner (
    idBanner INT AUTO_INCREMENT PRIMARY KEY,
    Imagem_banner LONGBLOB,
    Data_validade DATE,
    Descricao VARCHAR(45),
    link VARCHAR(45),
    Categorias_idCategorias INT,
    FOREIGN KEY (Categorias_idCategorias) REFERENCES Categorias(idCategorias) ON DELETE CASCADE
) ;
-- drop table Banner;
select * from Cliente;
INSERT INTO Cliente(nome, cpf, telefone, email, senha, foto_perfil)
VALUES
('Anna', '40028922', '(63) 99999-9999', 'apropria@gmail.com', 'senhaSegura', NULL);

select * from CupomDesconto;
INSERT INTO CupomDesconto(Cod_desconto, Dias_validade, Quantidade, Nome_desconto)
VALUES
('123', '2025-02-28', '1', 'Dia das mães');


select * from Endereco;
INSERT INTO Endereco(Cep,Cidade,Estado,Numero,Complemento,TipoLogradouro,Bairro,Tipo)
VALUES
('1234','Araguaína','Tocantins','12','na frente de uma placa verde','casa','Costa Esmeralda','Residencial');

select * from Categorias;
insert into Categorias(Nome_categoria,desconto,imagem,Validade_desconto)values("Godofredo","25%","link imagem","11-11-1111");

select * from Frete;
insert into Frete(Valor_frete,Bairro,Transportadora)values(15,"Costa Esmeralda","correios");


-- SELECT
SELECT * FROM Vendas_has_Produtos;

-- INSERT
INSERT INTO Vendas_has_Produtos(Vendas_idVendas, Produtos_idProdutos)
VALUES (1, 1);


SELECT * FROM Vendas;
-- INSERT
INSERT INTO Vendas(DataVendas, Valor_frete, Valor_Produto, Valor_total, Forma_pagamento_idFormaPagamento, Cliente_idCliente, Cpf_Cliente, CupomDesconto_idCupomDesconto, Frete_idFrete, Data_entrega, situacao, Cod_pix, Cod_barras, Valor_local, Valor_desconto)
VALUES ('2025-09-20', 15.00, 200.00, 215.00, 1, 1, 40028922, 1, 1, '2025-09-25', 'A caminho', '123456789abc', '000011112222', 0, 25.00);
-- UPDATE
UPDATE Vendas
SET situacao = 'Entregue', Data_entrega = '2025-09-21'
WHERE idVendas = 1;

-- SELECT
SELECT * FROM Produtos;
-- INSERT
INSERT INTO Produtos(NomeProduto, Descricao, Quantidade, Preco, Tamanho, Cor, Codigo, Preco_Promocional)
VALUES ('Camiseta Oversized', 'Camiseta confortável e estilosa', 50, 79.90, 'M', 'Preto', 123456, 59.90);
-- UPDATE
UPDATE Produtos
SET Preco = 69.90, Quantidade = 45
WHERE idProdutos = 1;

-- SELECT
SELECT * FROM Vendas;
-- INSERT
INSERT INTO Vendas(DataVendas, Valor_frete, Valor_Produto, Valor_total, Forma_pagamento_idFormaPagamento, Cliente_idCliente, Cpf_Cliente, CupomDesconto_idCupomDesconto, Frete_idFrete, Data_entrega, situacao, Cod_pix, Cod_barras, Valor_local, Valor_desconto)
VALUES ('2025-09-20', 15.00, 200.00, 215.00, 1, 1, 40028922, 1, 1, '2025-09-25', 'A caminho', '123456789abc', '000011112222', 0, 25.00);

-- SELECT
SELECT * FROM Vendas_has_Produto_Cliente;
-- INSERT
INSERT INTO Vendas_has_Produto_Cliente(Vendas_idVendas, Cliente_idCliente, Cpf, Produtos_idProdutos)
VALUES (1, 1, 40028922, 1);

-- SELECT
SELECT * FROM Categorias;
-- INSERT
INSERT INTO Categorias(Nome_categoria, Desconto, Imagem, Validade_desconto, Categoriaurl)
VALUES ('Promoção Primavera', 15.0, 'linkimagem.jpg', '2025-10-15', 'promo-primavera');
-- UPDATE
UPDATE Categorias
SET Desconto = 20.0
WHERE idCategorias = 1;

-- SELECT
SELECT * FROM Produtos_has_Categorias;

-- INSERT
INSERT INTO Produtos_has_Categorias(Produtos_idProdutos, Categorias_idCategorias)
VALUES (1, 1);

-- SELECT
SELECT * FROM ImagemProduto;

-- INSERT
INSERT INTO ImagemProduto(Foto, Descricao)
VALUES (LOAD_FILE('caminho/para/imagem.jpg'), 'Imagem da camiseta');

-- UPDATE
UPDATE ImagemProduto
SET Descricao = 'Imagem atualizada'
WHERE idImagemProduto = 1;

-- SELECT
SELECT * FROM Produtos_has_ImagemProduto;

-- INSERT
INSERT INTO Produtos_has_ImagemProduto(Produtos_idProdutos, ImagemProduto_idImagemProduto)
VALUES (1, 1);
-- SELECT
SELECT * FROM Banner;

-- INSERT
INSERT INTO Banner(Imagem_banner, Data_validade, Descricao, link, Categorias_idCategorias)
VALUES (LOAD_FILE('caminho/para/banner.jpg'), '2025-12-31', 'Banner de fim de ano', 'https://bynandes.com.br/fim-de-ano', 1);

-- UPDATE
UPDATE Banner
SET Descricao = 'Banner atualizado'
WHERE idBanner = 1;

-- SELECT
SELECT * FROM FormaPagamento;

-- INSERT
INSERT INTO FormaPagamento(NomePagamento, FormaPagamento)
VALUES ('Cartão de Crédito', 'Visa');

-- UPDATE
UPDATE FormaPagamento
SET FormaPagamento = 'MasterCard'
WHERE idFormaPagamento = 1;



-- criar tabela de produtos

CREATE TABLE u799109175_agulhaelinha.tbl_produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,

    descricao VARCHAR(255) NOT NULL,

    categoria ENUM(
        'Aniversarios',
        'Buquê Noiva',
        'Dia das Mães',
        'Dia Namorados',
        'Lembrancinhas Corporativas',
        'Datas Especiais',
        'Românticas',
        'Cestas Personalizadas'
    ) NOT NULL,

    img_prod VARCHAR(255), -- URL ou caminho da imagem

    preco_original DECIMAL(10,2) NOT NULL,
    preco_atual DECIMAL(10,2) NOT NULL,

    desconto DECIMAL(10,2)
        GENERATED ALWAYS AS (preco_original - preco_atual) STORED,

    dt_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacao TEXT
);

-- criar tabela de pedido dos clientes

CREATE TABLE u799109175_agulhaelinha.tbl_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    nome_solicitante VARCHAR(150) NOT NULL,
    nome_recebedor VARCHAR(150) NOT NULL,

    endereco_entrega VARCHAR(200) NOT NULL,
    bairro_entrega VARCHAR(150) NOT NULL,
    ponto_referencia VARCHAR(150),

    forma_pgmto ENUM(
        'PIX',
        'DINHEIRO',
        'CARTAO_CREDITO',
        'CARTAO_DEBITO'
    ) NOT NULL,

    mensagem_declaracao TEXT,

    dt_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);  

-- criar tabela de pedido dos clientes

CREATE TABLE u799109175_agulhaelinha.tbl_pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,

    id_cliente INT NOT NULL,

    status_pedido ENUM(
        'ABERTO',
        'CONFIRMADO',
        'EM_PREPARACAO',
        'ENVIADO',
        'ENTREGUE',
        'CANCELADO'
    ) DEFAULT 'ABERTO',

    valor_total DECIMAL(10,2) DEFAULT 0.00,

    dt_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pedido_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES tbl_clientes(id)
        ON DELETE CASCADE
);

-- tabela pedidos e produttos interligados
CREATE TABLE u799109175_agulhaelinha.tbl_pedido_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,

    id_pedido INT NOT NULL,
    id_produto INT NOT NULL,

    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    preco_total DECIMAL(10,2)
        GENERATED ALWAYS AS (quantidade * preco_unitario) STORED,

    CONSTRAINT fk_pp_pedido
        FOREIGN KEY (id_pedido)
        REFERENCES tbl_pedidos(id_pedido)
        ON DELETE CASCADE,

    CONSTRAINT fk_pp_produto
        FOREIGN KEY (id_produto)
        REFERENCES tbl_produtos(id_produto)
        ON DELETE RESTRICT
);

--view resumo geral
CREATE OR REPLACE VIEW u799109175_agulhaelinha.vw_resumo_pedidos AS
SELECT
    -- Cliente
    c.id                AS id_cliente,
    c.nome_solicitante,
    c.nome_recebedor,
    c.endereco_entrega,
    c.bairro_entrega,
    c.forma_pgmto,

    -- Pedido
    p.id_pedido,
    p.status_pedido,
    p.dt_pedido,

    -- Métricas do pedido
    COUNT(pp.id_produto)              AS qtd_itens,
    SUM(pp.quantidade)                AS qtd_total_produtos,
    SUM(pp.preco_total)               AS valor_total_pedido,

    -- Datas
    DATE(p.dt_pedido)                 AS dt_pedido_data,
    YEAR(p.dt_pedido)                 AS ano,
    MONTH(p.dt_pedido)                AS mes,
    DAY(p.dt_pedido)                  AS dia

FROM tbl_clientes c
JOIN tbl_pedidos p
    ON p.id_cliente = c.id
JOIN tbl_pedido_produtos pp
    ON pp.id_pedido = p.id_pedido

GROUP BY
    c.id,
    c.nome_solicitante,
    c.nome_recebedor,
    c.endereco_entrega,
    c.bairro_entrega,
    c.forma_pgmto,
    p.id_pedido,
    p.status_pedido,
    p.dt_pedido;
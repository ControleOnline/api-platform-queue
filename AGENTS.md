## Escopo
- Modulo de filas operacionais, displays e KDS/PPC.
- Cobre `Queue`, `Display`, `DisplayQueue`, `OrderProductQueue` e impressao relacionada a filas.

## Quando usar
- Prompts sobre filas de preparo, displays, distribuicao de itens do pedido em filas e operacao de PPC/KDS.

## Limites
- O pedido continua pertencendo a `orders`.
- `queue` deve cuidar da organizacao operacional da fila, nao da regra comercial do pedido.
- Fila operacional so enxerga pedido pronto para producao (`orderType = sale`). Carrinho de venda (`cart`) e legado de carrinho em `quote` nao entram em display/KDS.

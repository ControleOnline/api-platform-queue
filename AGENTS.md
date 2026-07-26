# controleonline/queue

## Contratos
- `display.display_type` usa `production`, `conference` e `tracking`. Valores antigos `products`, `orders` e `tv` devem ser tratados apenas como aliases legados.
- A ponte PDV -> PPC/PCP e a materializacao em `order_product_queue`; nao modelar esse fluxo como simples troca de status do pedido.
- A sequencia PCP e `production` -> impressao de barcode -> picking/separation com bipagem -> `conference` -> ready/tracking.
- Mesa/comanda (`table`/`tab`) pode gerar preparo antes do pagamento. Pagamento antes da producao so e obrigatorio quando a politica do modo/canal explicitar isso, como no balcao/prepaid checkout; os demais fluxos podem variar. Delivery e sempre depois da producao.

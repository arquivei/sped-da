# Tarefa 2 — NT 2024.002: novos códigos de forma de pagamento (16–22)

**Origem:** [INT-2581 — Fase 1](https://arquivei.atlassian.net/browse/INT-2581)
**Tipo:** Gap regulatório
**Risco:** Baixo
**Estimativa:** 2–4h

## Contexto

O upstream atualizou o array `$formaPagamento` com os códigos definidos pela Nota Técnica 2024.002. O `arquivei-releases` ainda usa a tabela antiga, com 15 entradas. As diferenças identificadas na análise:

| Código | `arquivei-releases` atual | Upstream (NT 2024.002) |
|--------|---------------------------|--------------------------|
| 05 | `'Crédito Loja'` | `'Cartão da Loja/Outros Crediários'` |
| 16–22 | ausentes | Depósito Bancário, PIX Dinâmico, Transferência/Carteira Digital, Fidelidade/Cashback, PIX Estático, Crédito em Loja, Pagamento Eletrônico não Informado |

Isso é uma **lacuna regulatória ativa**: NF-e com forma de pagamento nos códigos 16–22 exibem hoje "Forma XX não encontrado" no DANFE gerado pela Arquivei. Vale notar que já existe um commit recente (`d83a3db "Exibe forma de pagamento 99=Outros"`) tratando um código adjacente (99) — ou seja, o time já vem incrementando essa tabela pontualmente, mas os códigos 16–22 continuam sem cobertura.

**Confirmado no código (checado em `master` e `arquivei-releases`): a tabela está duplicada em dois lugares diferentes, com implementações diferentes:**

1. `src/NFe/Danfe.php:1950` — array `$formaPagamento` (o que a análise da INT-2581 comparou contra o upstream).
2. `src/Legacy/Common.php:209` — método `protected function tipoPag($tPag)`, um `switch/case` equivalente, também sem os códigos 16–22, usado por:
   - `src/NFe/Danfce.php:597` (NFC-e)
   - `src/NFe/Danfe.php:1761` (o próprio `Danfe.php` usa `tipoPag()` como *fallback* em um ponto do código, além do array `$formaPagamento` em outro ponto — ou seja, o `Danfe.php` tem **dois mecanismos diferentes** de resolução do mesmo dado.)

Isso significa que corrigir apenas o array em `Danfe.php` (como a análise original sugere) deixaria o **NFC-e (`Danfce.php`)** e o segundo caminho de código do próprio `Danfe.php` ainda desatualizados.

## O que deve ser feito

1. Atualizar o array `$formaPagamento` em `src/NFe/Danfe.php:1950`.
2. Atualizar o método `tipoPag()` em `src/Legacy/Common.php:209` com os mesmos códigos — **as duas tabelas precisam ficar sincronizadas**, já que `Danfce.php` só usa `tipoPag()` e `Danfe.php` usa as duas dependendo do caminho de código.
3. Avaliar, como melhoria oportunista (não obrigatória para fechar esta tarefa, mas recomendada): unificar as duas implementações em uma única fonte de verdade em `DaCommon`/`Common`, para que essa duplicação não se repita na próxima atualização regulatória. Se não houver tempo na sprint, ao menos abrir um débito técnico registrado para isso.
4. Atualizar a entrada do código `05` para `'Cartão da Loja/Outros Crediários'` nas duas tabelas.
5. Adicionar as entradas `16` a `22` conforme a NT 2024.002, nas duas tabelas:
   - `16` — Depósito Bancário
   - `17` — PIX Dinâmico
   - `18` — Transferência bancária, Carteira Digital
   - `19` — Programa de fidelidade, Cashback, Crédito Virtual
   - `20` — PIX Estático
   - `21` — Crédito em Loja
   - `22` — Pagamento Eletrônico não Informado
   (confirmar o texto exato de cada rótulo contra o Manual de Orientação do Contribuinte / NT 2024.002 vigente antes de codificar, os nomes acima são os citados na análise e podem precisar de ajuste fino de redação oficial.)
6. Adicionar teste unitário parametrizado cobrindo todos os códigos de `00` a `22` e `99`, para as duas tabelas (`Danfe::$formaPagamento` e `Common::tipoPag()`), garantindo que nenhum deles caia no fallback "Forma XX não encontrado" / string vazia.

## Critérios de aceitação

* Nenhum código de pagamento válido (00–22, 99) resulta em "Forma XX não encontrado" em nenhuma das duas tabelas.
* O rótulo do código `05` é atualizado para o texto vigente da NT 2024.002 nas duas tabelas.
* `Danfce.php` (via `tipoPag()`) e `Danfe.php` (via ambos os mecanismos) exibem o mesmo rótulo para o mesmo código — sem divergência entre documentos.
* Teste unitário cobre todos os códigos de ambas as tabelas, não apenas os novos.

## Restrições

* Mudança cirúrgica apenas nos dados de mapeamento — não alterar a lógica de exibição do campo de forma de pagamento (posição, formatação), apenas os rótulos/códigos.
* Não remover nenhuma das duas implementações nesta tarefa (mesmo que redundantes) — unificação é uma melhoria oportunista, não um requisito; removê-las sem uma tarefa dedicada de refatoração arrisca quebrar um caminho de código não coberto por teste.
* Testável com um XML simples por código — não é necessário XML real de produção para esta tarefa, um XML de fixture com o campo `tPag` (ou `indPag`, conforme a classe) variando já é suficiente.

## Dependências e links

* Arquivos: `src/NFe/Danfe.php:1950` e `:1761`, `src/Legacy/Common.php:209` (`tipoPag()`), `src/NFe/Danfce.php:597` (chamador)
* Nota Técnica 2024.002 (SEFAZ) — tabela de formas de pagamento
* Commit relacionado já existente: `d83a3db` "Exibe forma de pagamento 99=Outros"

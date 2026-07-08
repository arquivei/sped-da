# Tarefa 4 — Novas classes de documento: `DanfeSimples`, `DanfeVarejo`, `DanfeEtiqueta`

**Origem:** [INT-2581 — Fase 3 (parte 1/4)](https://arquivei.atlassian.net/browse/INT-2581)
**Tipo:** Nova funcionalidade (classes novas, sem conflito com customizações do fork)
**Risco:** Baixo
**Estimativa:** ~1–2 dias (proporcional às ~800 linhas somadas das 3 classes)

## Contexto

O upstream adicionou três novas classes de documento auxiliar, ausentes no fork da Arquivei:

| Classe | Descrição |
|--------|-----------|
| `src/NFe/DanfeSimples.php` | DANFE simplificado para varejo |
| `src/NFe/DanfeVarejo.php` | DANFE varejo |
| `src/NFe/DanfeEtiqueta.php` | DANFE etiqueta (compacta) |

São classes **novas** — não substituem nem alteram o `Danfe.php` já customizado pela Arquivei, o que as torna de baixo risco: o trabalho é essencialmente portar e adaptar, não fazer merge de mudanças conflitantes.

## O que deve ser feito

1. Portar as três classes do upstream para `src/NFe/` no `arquivei-releases`.
2. Adaptar cada classe para estender/usar a infraestrutura já customizada da Arquivei em `DaCommon` (não a versão "crua" do upstream), garantindo que herdem os mesmos mecanismos de logo, créditos de rodapé, etc. já usados pelo `Danfe.php` principal — verificar se as classes do upstream dependem de métodos/propriedades que só existem na versão upstream de `DaCommon` e, se sim, adaptar para os equivalentes já existentes no fork.
3. Adicionar exemplos de uso em `examples/` (seguindo o padrão de `examples/nfse/danfse.php`, se esse diretório já existir, ou o padrão equivalente para NF-e).
4. Adicionar fixtures de XML de teste e testes unitários para cada uma das 3 classes — cobrindo ao menos: renderização sem erro, presença dos campos essenciais (chave de acesso, emitente, destinatário, itens, totais).
5. Confirmar se alguma dessas classes precisa de tratamento de forma de pagamento (ver Tarefa 2) — se sim, garantir que usem a tabela já corrigida, não uma cópia desatualizada.

## Critérios de aceitação

* As três classes renderizam corretamente um PDF a partir de um XML de NF-e válido, sem erros/warnings do PHP.
* Cada classe tem pelo menos um teste unitário e um exemplo de uso.
* As classes usam a infraestrutura compartilhada (`DaCommon`) já customizada pela Arquivei, não uma cópia paralela.
* Revisão visual de cada um dos três formatos confirma que o layout é fiel ao propósito (simplificado, varejo, etiqueta) — comparar com a saída do upstream como referência, mas validar que texto/formatação seguem os padrões em português/moeda já usados nas demais classes do fork.

## Restrições

* Não modificar o `Danfe.php` existente para dar suporte a essas classes — se houver necessidade de extrair algo comum, extrair para `DaCommon` de forma aditiva (sem alterar comportamento de quem já usa `Danfe.php`).
* Não portar nenhuma customização específica da Arquivei que exista apenas no `Danfe.php` principal (ex.: watermark de cancelamento, `$exibirNumeroItemPedido`) para essas novas classes nesta tarefa, a menos que seja explicitamente pedido pelo produto — escopo inicial é paridade com o upstream, não paridade de customizações.
* Validar que a licença/autoria dessas classes no upstream (LGPL/GPL/MIT, conforme `composer.json`) é compatível com o uso no fork antes de portar — mesmo sendo um fork já derivado do mesmo projeto, confirmar não há necessidade de atribuição adicional.

## Dependências e links

* Novos arquivos: `src/NFe/DanfeSimples.php`, `src/NFe/DanfeVarejo.php`, `src/NFe/DanfeEtiqueta.php`
* Depende de: Tarefa 2 (tabela de formas de pagamento), se aplicável a algum desses formatos
* Referência: `nfephp-org/sped-da` (upstream), diretório `src/NFe/`

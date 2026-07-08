# Análise de consistência — `docs/jira/INT-2597` vs. spec/plano gerados

**Confrontado contra:**
- `docs/superpowers/specs/2026-07-08-sincronia-fork-sped-da-design.md`
- `docs/superpowers/plans/2026-07-08-sincronia-fork-sped-da.md`

## O achado central

O `docs/jira/INT-2597/00-iniciativa.md` é taxativo: **"o objetivo desta iniciativa é fechar essas lacunas — não substituir a implementação do fork pela do upstream"**. Essa é a terceira fonte independente dizendo a mesma coisa (a Tarefa 5 do INT-2581, o próprio texto da iniciativa INT-2597, e a investigação desta sessão que encontrou arquiteturas totalmente diferentes entre as duas implementações de `Danfse.php`): **não tratar `Danfse.php` como um port genérico** — é comparação cirúrgica, arquivo por arquivo, bug por bug, sempre preservando a arquitetura própria (`blocoX()`).

A Fase G / Task 15 do plano até respeita esse espírito na letra ("preservar arquitetura própria, seguindo a convenção `blocoX()`"), mas erra de um jeito diferente: manda o executor **refazer do zero** uma comparação (`git show ... | grep function`, montar tabela lado a lado) que **já foi feita e já virou 6 tarefas prontas, específicas, com números de linha, critérios de aceitação e restrições** em `docs/jira/INT-2597/01` a `06`. Isso não é uma inconsistência de conteúdo — é retrabalho puro, com risco de a task 15 chegar a conclusões menos precisas do que as já documentadas.

## Achados concretos do INT-2597 ausentes do spec/plano

| Tarefa INT-2597 | Achado técnico | Severidade | Presente no spec/plano? |
|---|---|---|---|
| 1 — `cStat` na marca d'água | `watermark()` compara `cStat` contra `'2'`/`'3'` (2 dígitos), mas `getCStatLabel()` trata `cStat` como código de 3 dígitos — **a marca d'água de CANCELADA/SUBSTITUÍDA provavelmente nunca aparece em produção** | Alta (bug silencioso em documento fiscal) | Não |
| 2 — `creditsIntegratorFooter()` | Método herdado de `DaCommon` seta `$this->creditos`, mas `Danfse.php` nunca lê essa property — chamada é aceita silenciosamente e não faz nada | Média (API que finge funcionar) | Não |
| 3 — Totais Aproximados (Lei nº 12.741/2012) | Exigência legal de transparência fiscal não é atendida hoje — bloco simplesmente não existe | Alta (gap de compliance) | Não |
| 4 — Informações Complementares | 9 campos do XML nunca são lidos/exibidos (inclui código de obra, pedido de compra — mesmo padrão que já existe no `Danfe.php` via `$exibirNumeroItemPedido`) | Média | Não |
| 5 — Supressão tributação federal 2027 | Upstream já suprime o bloco de PIS/COFINS/IRRF a partir da competência 2027 (Reforma Tributária); fork sempre desenha o bloco. Data de corte do upstream (2026) já está no meio do próprio ano de corte — urgente, não é "algum dia" | Alta (regulatório, com prazo real próximo) | Não |
| 6 — Estrutura IBS/CBS | Fork lê alíquotas/valores direto do nó `trib`; upstream lê de sub-nós `uf`/`mun`/`fed` separados. Se a estrutura do fork estiver errada, os campos de IBS/CBS podem estar **sempre zerados em produção** sempre que há dado real | **Crítica** (possível dado fiscal incorreto exibido silenciosamente) | Não |

O item 6 é o mais grave: é uma hipótese concreta de bug de dado fiscal incorreto em produção. A Task 15 do plano nem chega perto de levantar essa possibilidade — fala genericamente em "paridade de funcionalidade", quando o problema real já diagnosticado é "os dois lados podem estar lendo de lugares diferentes do XML, e não se sabe qual está certo sem um XML real ou o XSD oficial".

## Outros detalhes do INT-2597 ausentes do spec/plano

- **Restrição explícita**: não introduzir `setAsCanceled()`/`setAsSubstituted()` do upstream sem uma tarefa própria (mudança de API pública) — o plano não menciona essa cautela.
- **DoD inclui SonarQube/PSR-12** ("seguindo o padrão já aplicado nos commits `refactor(danfse): resolve issues apontados pelo SonarQube`") — o plano só manda rodar `composer test`, nunca `composer phpcs`/`phpstan`, apesar de esses scripts existirem no `composer.json`.
- **Dependência de confirmação externa** (tabela oficial de `cStat`, XSD do IBS/CBS, calendário da Reforma Tributária) é tratada em várias tarefas do INT-2597 como bloqueio explícito ("não implementar sem confirmar") — o plano não tem esse tipo de gate para nada relacionado a `Danfse.php`.

## Veredito

A Fase G / Task 15 do plano deveria deixar de existir na forma atual (comparação genérica de métodos) e ser substituída por algo como "executar as 6 tarefas já especificadas em `docs/jira/INT-2597`" — não porque o raciocínio da Task 15 estivesse errado (a diretriz de preservar arquitetura própria bate com o que o INT-2597 pede), mas porque o trabalho de descoberta que a Task 15 propõe já foi feito, com mais profundidade e precisão técnica do que uma comparação genérica de nomes de método conseguiria reproduzir.

## Itens a corrigir, em ordem de prioridade

1. Substituir a Fase G / Task 15 do plano por uma referência direta às 6 tarefas do INT-2597, preservando a decisão já tomada nesta sessão de não substituir a implementação própria pela do upstream.
2. Priorizar a Tarefa 6 do INT-2597 (estrutura IBS/CBS) acima das demais, dado o risco de dado fiscal incorreto silencioso.
3. Sinalizar a urgência de calendário da Tarefa 5 do INT-2597 (supressão 2027) dado que a data atual já está próxima do corte usado como referência pelo upstream.
4. Incluir `composer phpcs`/`phpstan` na estratégia de validação do plano para qualquer fase que toque `Danfse.php`, alinhando com o DoD já estabelecido pela iniciativa INT-2597.

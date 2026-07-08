# Tarefa 1 — Sync `arquivei-releases` ← `fork/master` (baseline)

**Origem:** [INT-2581 — Fase 0 / "Resumo — Análise de Sync: arquivei-releases ← master"](https://arquivei.atlassian.net/browse/INT-2581)
**Tipo:** Manutenção / débito técnico
**Risco:** Baixo
**Estimativa:** 2,5–3h (execução manual) / 40–60min (execução por agente subagent-driven, conforme já estimado na análise)

## Contexto

O fork `arquivei/sped-da` divergiu do upstream em `431de98` (Merge PR #333). Desde então, as duas branches evoluíram de forma independente:

* `master` (upstream, mirror neste repositório): 34 commits com correções de bugs, novas funcionalidades e refatorações.
* `arquivei-releases` (branch de onde as releases da Arquivei são geradas hoje): 19 commits com customizações específicas.

Esta tarefa é sobre trazer para `arquivei-releases` o que há de valioso nesses 34 commits do `master`, sem perder nenhuma das customizações já existentes.

## O que deve ser feito

### Correções de bugs a portar

* **`Dacte`**: referência a `cteDPEC()` inexistente causa fatal error em CTe em contingência DPEC.
* **`Daevento` (CTe)**: Carta de Correção 3.0 com múltiplas correções (`infCorrecao`) não estava sendo impressa.
* **`Danfce`**: logo sobreposta ao texto "Documento Auxiliar"; mensagem de nota premiada (MS) quebrada.
* **`Dabpe`**: BPe com múltiplas `infViagem` (ex.: conexões) renderizava apenas a primeira.

### Melhorias de layout a portar

* **`Damdfe`**: box de observações com altura fixa de 30mm (insuficiente para textos longos) — ampliar para 145mm.
* **`DaCommon`**: `adjustImage()` lançava warning ao receber logo vazio ou arquivo inexistente — corrigir.

### Novas funcionalidades a portar (Danfe)

* Flag `$gerarInformacoesAutomaticas` — permite suprimir a geração automática de informações de notas referenciadas e da tag `<compra>`.
* `setMostrarUnidadeTributavel()` — exibe linha adicional por item com `uTrib`/`qTrib`/`vUnTrib`.
* `setQComCasasDec()` / `setVUnComCasasDec()` — casas decimais configuráveis em quantidade e valor unitário.

### Nova funcionalidade a portar (CTe)

* Modal aquaviário completo no `Dacte` (tabela de containers com Tipo Doc., CNPJ, Lacre, etc.).

### Bugs que NÃO devem ser portados (introduzidos no upstream)

* `fatura()` imprime o valor numérico de `$y` no título da fatura — resquício de debug do upstream, não portar.
* `dadosAdicionais()` fixa a posição Y a partir do final da página, ignorando o layout sequencial — regressão do upstream, não portar.

## Critérios de aceitação

* Todos os itens da lista "a portar" estão presentes em `arquivei-releases`, com testes unitários cobrindo cada correção/funcionalidade.
* Os dois itens da lista "não portar" são explicitamente excluídos do cherry-pick (revisão de diff confirma a ausência).
* Nenhuma customização exclusiva de `arquivei-releases` é perdida no processo (ver lista abaixo, que deve continuar intacta após o sync).
* Suite de testes existente continua passando sem alteração de comportamento não intencional.

## Restrições

* **Não fazer merge direto de `master` em `arquivei-releases`** — o volume de customizações históricas do fork (444 commits, incluindo toda a customização citada abaixo) torna um merge automático arriscado. Preferir cherry-pick commit a commit ou porte manual de cada item listado.
* Preservar explicitamente as customizações que só existem em `arquivei-releases` e não têm equivalente no upstream:
  - `setStatus()` / `getStatus()` + watermarks visuais para NF-e cancelada e denegada.
  - `imagePNGtoJPG()` — suporte nativo a logos PNG.
  - `$exibirNumeroItemPedido` / `$exibirNumeroPedidoCompra` — exibe dados de pedido na descrição do produto.
  - `monta()` público com 8 parâmetros (assinatura usada pelo `da-api`) — **não alterar essa assinatura**, é contrato externo.
  - `Dacanc.php` e `Dacce.php` (removidos no upstream, mas ainda usados pela Arquivei).
* Validar cada item portado com um XML de teste real antes de considerar a tarefa concluída — não assumir que o cherry-pick "limpo" é suficiente sem teste funcional.

## Dependências e links

* Branches: `master` (upstream) → `arquivei-releases` (destino do sync)
* Arquivos afetados: `src/CTe/Dacte.php`, `src/CTe/Daevento.php`, `src/NFe/Danfce.php`, `src/BPe/Dabpe.php`, `src/MDFe/Damdfe.php`, `src/Common/DaCommon.php`, `src/NFe/Danfe.php`

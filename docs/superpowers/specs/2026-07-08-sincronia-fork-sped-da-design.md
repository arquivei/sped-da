# Sincronia do fork `sped-da` com o upstream — Design

**Origem:** [INT-2581 — Análise de sincronia do fork sped-da com o upstream](https://arquivei.atlassian.net/browse/INT-2581) (Spike, status "Concluído")
**Data:** 2026-07-08
**Branch de trabalho:** `INT-2597/sincronia-fork`

## Contexto

A issue INT-2581 é um Spike que analisou o gap de código entre o fork `arquivei/sped-da` e o upstream `nfephp-org/sped-da`, propondo 5 fases de trabalho (0 a 4) com estimativas de esforço e risco. Esta spec revalida essa análise contra o estado atual do repositório e do upstream real, e reorganiza o trabalho em fases executáveis.

### O que mudou desde a análise original

A análise do INT-2581 foi feita comparando pontos de divergência específicos (`431de98`, `a5f2851`) que já não refletem o estado atual dos branches. Ao revalidar:

- **Não havia remote configurado para o upstream real** (`nfephp-org/sped-da`) neste ambiente de trabalho — foi adicionado (`upstream_real`) para permitir a revalidação.
- A branch `master` deste repositório é uma cópia histórica do upstream **parada em 2020-07-31** — não é sincronizada automaticamente.
- `arquivei-releases` diverge do merge-base com o upstream real (`f8305a9`) em **51 commits próprios**; o upstream real tem **515 commits** desde esse mesmo ponto — muito mais do que os "34/19 commits" mencionados na análise original (que usava um ponto de divergência mais recente/estreito).
- O diff real em `src/` entre o merge-base e o upstream é de **45 arquivos / ~18.000 linhas** — bem mais amplo que os 8 itens listados no ticket.
- **Gap não coberto pela análise original**: `Danfce.php`, `DaCommon.php`, `Common/NfeStd.php` (novo no upstream), `Legacy/Dom.php`, `Legacy/FPDF/Fpdf.php`, `Legacy/FPDF/Fpdf181.php`, `NFe/Daevento.php` e `MDFe/Daevento.php` (novo no upstream) mudaram significativamente no upstream e não são mencionados no INT-2581.
- **Achado inicial (nível de merge)**: a Arquivei nunca customizou a maioria desses arquivos desde o fork (diff zero entre o merge-base e `arquivei-releases` neles) — sem conflito de merge. `Legacy/Common.php` e `Legacy/Pdf.php` são exceção: têm customizações pequenas (8 e 26 linhas) que exigem merge manual.
- **Achado crítico (nível de API, investigado a fundo antes de planejar)**: ausência de conflito de merge não significa ausência de risco. Verificando a API pública método a método:
  - `Legacy/FPDF/Fpdf.php` (48/48 métodos idênticos), `Legacy/FPDF/Fpdf181.php` (idêntico), `Legacy/Dom.php` (idêntico exceto `loadXMLFile()`, sem chamadores internos), `Common/NfeStd.php` e `MDFe/Daevento.php` (arquivos novos, sem equivalente no fork) — **realmente seguros para portar**.
  - `NFe/Daevento.php` é compatível, mas o upstream mudou sua herança de `extends Common` para `extends DaCommon` (para herdar `creditsIntegratorFooter()`) — **depende de `DaCommon.php` estar atualizado antes**.
  - `Common/DaCommon.php` (classe-base de todas as `Da*`) **não é um replace seguro**: o upstream removeu/renomeou `imagePNGtoJPG()`, `setFontType()`, `setFontSize()`, `setFontStyle()`, `setOrientationAndSize()` — API pública atual, usada internamente (`Danfe.php:982`, `NFe/Daevento.php:272`) e potencialmente pelo `da-api` externo.
  - `NFe/Danfce.php` **não é um replace seguro**: o upstream reescreveu a classe do zero — `paperWidth()` virou `setPaperWidth()`, `monta()`/`debugMode()`/`creditsIntegratorFooter()` foram removidos, e o construtor mudou de assinatura. Isso quebraria qualquer consumidor (interno ou `da-api`) da API atual.
- **Decisão tomada**: `DaCommon.php` e `Danfce.php` ficam **fora do escopo desta rodada de implementação** (nenhum dos dois está na issue original — são gap descoberto durante a investigação). Ambos viram itens de decisão futura, no mesmo espírito da Fase I (Traits): exigem uma análise de uso real do `da-api` e uma escolha explícita entre wrapper de compatibilidade ou aceitar quebra de API, antes de qualquer implementação.
- `NFSe/Danfse.php`: a Arquivei já implementou sua própria versão completa da DANFSe Nacional (1165 linhas, PRs #35/#36, posteriores à análise) — divergente da proposta original de "portar DanfSe v2 do upstream". Decisão tomada: portar mesmo assim, avaliando o conflito durante a execução.

### Objetivo

Reduzir o gap entre `arquivei-releases` e o upstream real de forma incremental, cada fase entregável como PR independente, priorizando risco baixo primeiro, sem quebrar as customizações exclusivas da Arquivei (watermarks de status, suporte a PNG, numeração de pedido de compra, assinatura pública de `monta()`, `Dacanc.php`, `Dacce.php`, DANFSe própria).

## Escopo

### Fora de escopo
- Merge automático da Fase 0 original (sync `arquivei-releases` ← `master` local) como PR isolado: como `master` local é um subconjunto histórico do upstream real, seu conteúdo relevante é absorvido pelas fases que portam diretamente do upstream real. Não há PR "Fase 0" separado.
- `Common/DaCommon.php` e `NFe/Danfce.php`: não estão na issue original, e a investigação de API mostrou que exigem uma decisão explícita (wrapper de compatibilidade vs. quebra de API, com validação junto ao `da-api`) antes de qualquer implementação. Viram itens de decisão futura — ver Fase J.
- Qualquer mudança em arquivos fora de `src/` (docs, exemplos, fixtures de teste) exceto quando necessário para validar uma fase.

### Fases (cada uma é um PR contra `arquivei-releases`)

| Fase | Escopo | Arquivos-chave | Risco | Esforço estimado |
|---|---|---|---|---|
| A | NT 2024.002 — códigos de forma de pagamento 16–22 (regulatório) | `Danfe.php` (array `$formaPagamento` em `pagamento()`), `Legacy/Common.php` (`tipoPag()`, usado por `Danfce.php` e `Danfe.php:1761`) | Baixo | 2–4h |
| B | Novos métodos pontuais no Danfe | `Danfe.php`: `setTitle()`, `obsContShow()`, `setExibirEmailDestinatario()` (aditivos, isolados) + `epec()` (6 pontos de uso internos) + `setGerarInformacoesAutomaticas()` (default invertido vs. fork) | Baixo/Médio | 4–8h |
| C | Port de arquivos com API pública compatível | `Legacy/FPDF/Fpdf.php`, `Legacy/FPDF/Fpdf181.php`, `Legacy/Dom.php`, `Common/NfeStd.php` (novo) | Baixo (merge e API confirmados) | 1 dia |
| D | Merge manual dos arquivos com customização leve da Arquivei | `Legacy/Common.php`, `Legacy/Pdf.php` | Médio (impacto sistêmico, mudança pequena, conflito real na regex de `toTimestamp()`) | 4–8h |
| E | Novas classes de documento | `NFe/DanfeSimples.php`, `NFe/DanfeVarejo.php`, `NFe/DanfeEtiqueta.php` | Baixo | 1–2 dias |
| F | MDFe — CIOT e percursos | `MDFe/Damdfe.php` (CIOT já é lido do XML mas nunca impresso — confirmado no código atual; percursos ausentes) | Médio | 1 dia |
| G | DanfSe v2 do upstream vs. implementação própria da Arquivei | `NFSe/Danfse.php` | Médio-Alto (arquiteturas totalmente diferentes — não é merge de linhas, é paridade de funcionalidade) | 1–2 dias |
| H | Refatoração do Dacte/DacteOS do upstream | `CTe/Dacte.php`, `CTe/DacteOS.php` | Médio-Alto (maior superfície de customização própria da Arquivei) | 1–2 dias |
| I | Decisão arquitetural: Traits no Danfe | 14 arquivos em `NFe/Traits/*` + refatoração de `Danfe.php` | Não é implementação — é decisão de negócio | Documento de decisão |
| J | Decisão: `DaCommon.php` e `Danfce.php` | Análise de uso do `da-api` + escolha de wrapper de compatibilidade vs. quebra de API | Não é implementação — é decisão de negócio | Documento de decisão |

### Ordem de execução

1. **A, B, C, D** — sem dependência entre si, podem correr em paralelo. Priorizar por serem baixo risco e destravarem PRs rápidos.
2. **E** depende de C mergeada. Achado adicional: `DanfeVarejo` e `DanfeEtiqueta` (não `DanfeSimples`) chamam `$this->adjustImage()`, método novo do upstream que não existe na `DaCommon.php` atual da Arquivei. Isso **não** exige a decisão completa da Fase J — `adjustImage()` e seu helper privado `getImageStringFromObject()` são autocontidos (usam só GD nativo e a property `$logomarca`, que as próprias classes novas já declaram) e podem ser **adicionados** a `DaCommon.php` sem tocar/remover nada existente. Essa adição pontual faz parte da Fase E, não da Fase J.
3. **F** e **H** são independentes entre si; ambas se beneficiam de D já mergeada (mesma base `Legacy/*`).
4. **G** por último entre as fases de implementação — quanto mais tarde, menor a chance de retrabalho por causa de mudanças paralelas em `DaCommon`/`Legacy`.
5. **I** e **J** não bloqueiam nem são bloqueadas por nenhuma fase de implementação; são documentos de decisão, produzidos a qualquer momento (idealmente depois das fases de código, informados pelo aprendizado delas).

## Estratégia de validação

- Cada fase de código roda `composer test` (`vendor/bin/phpunit -c phpunit.xml.dist`) antes do PR.
- Fases que tocam renderização de PDF (A, B, C, E, F, G, H) exigem **validação visual manual**: gerar PDF de um XML de exemplo (usar `examples/` e `tests/fixtures/xml/`) antes/depois da mudança e comparar visualmente — a suíte de testes do sped-da não cobre regressão visual de PDF.
- Fase D exige atenção redobrada: `Legacy/Common.php` e `Legacy/Pdf.php` são usados por todas as classes `Da*`. Validar renderizando pelo menos um documento de cada tipo (NFe, NFCe, CTe, MDFe, NFSe) após o merge.
- Fase G exige comparação campo a campo entre a implementação própria (ex.: lookup de municípios IBGE, formatação de `dCompet`) e a do upstream antes de decidir o que preservar.
- Fase H exige revisão bloco a bloco por causa de possíveis conflitos com as personalizações ISSQN/modal aquaviário do fork.

## Riscos e decisões em aberto

- **Fase B**: `setGerarInformacoesAutomaticas()` tem default invertido entre upstream (`false`) e fork (`true`). Decisão a tomar durante a execução: manter o comportamento atual do fork como default (compatibilidade retroativa com consumidores do `da-api`) e expor o comportamento do upstream como opção explícita.
- **Fase G**: risco de a implementação própria da Arquivei (mais recente, PRs #35/#36) ser mais completa que a do upstream em pontos específicos (lookup IBGE). A fase deve preservar funcionalidades exclusivas da Arquivei mesmo ao portar a estrutura do upstream.
- **Fase I**: mantém a recomendação original do INT-2581 — não executar sem driver de negócio explícito (novo tipo de documento obrigatório ou bug crítico do upstream ausente no fork). O documento de decisão desta fase deve detalhar os 19 métodos/propriedades exclusivos do fork que precisariam ser redistribuídos entre os 14 Traits, e os dois conflitos semânticos de API (`gerarInformacoesAutomaticas`, controle de unidade tributável).

## Entregáveis desta iniciativa

- Esta spec: `docs/superpowers/specs/2026-07-08-sincronia-fork-sped-da-design.md`
- Plano de execução (próximo passo, via skill `writing-plans`)
- 8 PRs de código (Fases A–H) contra `arquivei-releases`
- 2 documentos de decisão, sem PR de código: Fase I (Traits no Danfe) e Fase J (`DaCommon.php`/`Danfce.php`)

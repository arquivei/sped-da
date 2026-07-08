# INT-2581 — Análise de sincronia do fork sped-da com o upstream — Tarefas

> Quebra em tarefas da análise técnica registrada em [INT-2581](https://arquivei.atlassian.net/browse/INT-2581) (Spike, status "Concluído"). A análise compara a branch `master` deste repositório (mirror do upstream `nfephp-org/sped-da`, ponto de divergência no commit `431de98`) com a branch `arquivei-releases` (de onde as releases da Arquivei são geradas hoje), e também compara `upstream/master` com `fork/master` a partir do commit `a5f2851`.

## Tabela de tarefas

| # | Tarefa | Escopo (linhas aprox.) | Esforço | Risco |
|---|--------|------------------------|---------|-------|
| 1 | Sync `arquivei-releases` ← `fork/master` (baseline) | ~19 commits | 2,5–3h (manual) / 40–60min (agente) | Baixo |
| 2 | NT 2024.002 — novos códigos de forma de pagamento (16–22) | ~10 linhas | 2–4h | Baixo |
| 3 | Novos métodos no `Danfe` existente | pontual | 4–8h | Baixo |
| 4 | Novas classes de documento: `DanfeSimples`, `DanfeVarejo`, `DanfeEtiqueta` | ~800 linhas | 1–2 dias | Baixo |
| 5 | Portar `DanfSe v2` do upstream — **⚠️ ver ressalva de conflito na tarefa** | ~1176 linhas | 1–2 dias | Médio (por causa do conflito, não do código em si) |
| 6 | MDFe — suporte a CIOT e impressão de percursos (`Damdfe`) | ~970 linhas de diff | 1 dia | Baixo/Médio |
| 7 | Refatoração do `Dacte` (CTe) do upstream | ~1636 linhas de diff | 1–2 dias | Médio |
| 8 | Decisão arquitetural: refatoração do `Danfe` em Traits | 14 traits, ~4200 linhas | 2–3 semanas (se aprovado) | Alto |

As tarefas 1–3 correspondem à Fase 0 e às Fases 1–2 da análise original (baixo risco, podem ser feitas em qualquer ordem). As tarefas 4–7 correspondem à Fase 3 (risco médio, splitadas por componente porque têm perfis de risco e tamanho de diff bem diferentes entre si). A tarefa 8 corresponde à Fase 4, e **não é uma tarefa de implementação** — é uma tarefa de decisão, pois a própria análise recomenda não executá-la sem um driver de negócio explícito.

## Observação importante antes de priorizar

Entre a data desta análise (INT-2581) e hoje, a branch `arquivei-releases` já recebeu uma implementação própria e completa de DANFSe para NFS-e Nacional (`src/NFSe/Danfse.php`, PRs #35 e #36, 10–11/06/2026) — trabalho que **não está refletido nesta análise** (que comparava `fork/master`, não `arquivei-releases`, contra o upstream). Isso afeta diretamente a Tarefa 5 — ver a ressalva nela antes de planejar.

## Dependências e links

* Fonte: [INT-2581 — Análise de sincronia do fork sped-da com o upstream](https://arquivei.atlassian.net/browse/INT-2581)
* Repositório upstream de referência: [`nfephp-org/sped-da`](https://github.com/nfephp-org/sped-da)
* Branches deste repositório: `master` (mirror do upstream), `arquivei-releases` (branch de release da Arquivei)
* Commit de divergência fork/upstream: `431de98` (branches antigas) / `a5f2851` (comparação `upstream/master` vs `fork/master` mais recente)

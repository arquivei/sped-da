# Análise de consistência — `docs/jira/INT-2581` vs. spec/plano gerados

**Confrontado contra:**
- `docs/superpowers/specs/2026-07-08-sincronia-fork-sped-da-design.md`
- `docs/superpowers/plans/2026-07-08-sincronia-fork-sped-da.md`

## Tabela comparativa

| Item do INT-2581 | Status no spec/plano | Veredito |
|---|---|---|
| Tarefa 1 (sync baseline): bugs `Dacte::cteDPEC()`, `Daevento` Carta de Correção múltipla, `Danfce` logo/MS, `Dabpe` infViagem, `Damdfe` box 30mm→145mm, `DaCommon::adjustImage()` warning | **Ausente** — nenhuma task cobre isso | **Gap real.** Confirmado por leitura direta do código: `cteDPEC()` já existe em `arquivei-releases` (resolvido, não é mais gap), mas `Daevento::xCorrecao` ainda só lê `item(0)` — bug real e aberto — e `DaCommon::imagePNGtoJPG()` ainda não tem guard de vazio/inexistente — bug real e aberto. `Danfce` (logo/MS) e `Damdfe` (altura do box) não foram confirmados com certeza. |
| Tarefa 2 (NT 2024.002, duas tabelas de pagamento) | Coberto (Tasks 1–2 do plano) | ✅ Consistente, quase idêntico em conteúdo e códigos |
| Tarefa 3 (`epec()`/DPEC, defaults de `obsCont`/email) | Coberto (Tasks 3–4 do plano) | ✅ Consistente, praticamente palavra-por-palavra na investigação |
| Tarefa 4 (novas classes de documento) — checar compatibilidade de licença antes de portar | Ausente no plano | Gap menor — falta um passo de verificação de licença (LGPL/GPL/MIT) |
| Tarefa 5 (DanfSe v2) — não iniciar sem decisão de produto; provável duplicidade com a iniciativa INT-2597 | A Fase G / Task 15 do plano trata como implementação direta, sem citar INT-2597 | **Gap real e o mais importante das duas análises** — ver `2026-07-08-int-2597-vs-spec-plan.md` |
| Tarefa 6 (MDFe CIOT/percursos) | Coberto (Task 14 do plano) | ✅ Consistente |
| Tarefa 7 (Dacte refatoração, revisão bloco a bloco) | Coberto (Task 16 do plano) | ✅ Consistente |
| Tarefa 8 (decisão arquitetural: Traits) | Coberto (Task 17 do plano, Fase I) | ✅ Muito consistente, quase o mesmo conteúdo |
| — | Fase J do plano (DaCommon/Danfce como decisão arquitetural grande) | O INT-2581 nunca pediu substituição desses arquivos — só as 2 correções cirúrgicas listadas na Tarefa 1 (guard do `imagePNGtoJPG`; bugs pontuais do `Danfce`). A Fase J do plano é mais pesada do que o necessário (foi dimensionada para avaliar uma substituição completa do arquivo) e, ao mesmo tempo, não entrega as correções pontuais que o INT-2581 pedia. |

## Resumo

O spec/plano está bem alinhado na parte "portar funcionalidades do upstream que o fork não tem", mas tem um buraco real na parte "bugs internos já conhecidos e documentados no `docs/jira`", porque a investigação que gerou o spec/plano usou a lente "o que o upstream tem que a gente não tem" e nunca usou a lente "o que já está quebrado hoje, independente do upstream".

## Itens a corrigir, em ordem de prioridade

1. Adicionar ao plano uma fase/task para os bugs confirmados como reais e abertos: `Daevento` (CTe) só imprime a primeira Carta de Correção (`xCorrecao`, não itera múltiplas ocorrências); `DaCommon::imagePNGtoJPG()` sem guard para logo vazio/inexistente.
2. Verificar se os bugs de `Danfce` (logo sobreposta / mensagem de nota premiada MS) e de `Damdfe` (altura do box de observações) ainda estão abertos em `arquivei-releases`, e se sim, adicioná-los ao plano.
3. Rever a Fase G do plano à luz do achado da Tarefa 5 do INT-2581 (ver segunda análise).
4. Rever a Fase J do plano: separar "correções cirúrgicas seguras" (o que o INT-2581 realmente pediu) de "decisão sobre substituição completa" (o que a investigação desta sessão levantou como preocupação adicional, mas que não é o mesmo escopo).
5. Adicionar um passo de verificação de licença na Fase E (novas classes de documento).

# Tarefa 5 — Portar `DanfSe v2` do upstream

**Origem:** [INT-2581 — Fase 3 (parte 2/4)](https://arquivei.atlassian.net/browse/INT-2581)
**Tipo:** Reescrita completa de renderer
**Risco:** Médio — **não pelo código em si, mas por um conflito de escopo detectado (ver ressalva abaixo)**
**Estimativa:** ~1176 linhas de inserção segundo a análise original; recomenda-se **não estimar prazo antes de resolver a ressalva**

## ⚠️ Ressalva — leia antes de planejar esta tarefa

A análise INT-2581 comparou a branch `master` deste repositório (mirror do upstream, **sem nenhuma classe de NFS-e**) contra o `upstream/master`, e identificou que o upstream tem `src/NFSe/Danfse.php (v2)` — um renderer de NFS-e (NT-008 v2) que o `master` do fork não tem.

**O que a análise não sabia (ou não considerou) é que a branch `arquivei-releases` — de onde as releases da Arquivei já são geradas hoje — recebeu, em 10–11/06/2026 (PRs #35 e #36), uma implementação própria e completa de DANFSe** (`src/NFSe/Danfse.php`, ~1165 linhas), já compatível com o modelo de dados da NFS-e Nacional (`infNFSe`/`infDPS`, incluindo os blocos de IBS/CBS da Reforma Tributária). Essa implementação:

* Já está em produção (branch de release), não é um rascunho.
* Cobre boa parte do mesmo escopo do "DanfSe v2" do upstream (cabeçalho, QR code, blocos de prestador/tomador/destinatário/intermediário, serviço, ISSQN, tributação federal, IBS/CBS, valor total, canhoto, marca d'água).
* Tem lacunas próprias identificadas separadamente (ver iniciativa [INT-2597](../INT-2597/00-iniciativa.md) e suas tarefas 01–06), mas **não é um ponto de partida vazio**.

**Portanto, antes de executar esta tarefa como "portar a classe do zero", é obrigatório decidir com o time de produto:**

1. Esta tarefa está **obsoleta/duplicada** porque o trabalho equivalente já foi feito de forma independente em `arquivei-releases`? (Cenário mais provável.)
2. Ou existem **funcionalidades específicas do "DanfSe v2" do upstream** que a implementação já existente da Arquivei não cobre e que valeria a pena importar pontualmente (não como substituição de classe, mas como incremento)? Se sim, essas lacunas específicas devem ser levantadas por comparação direta entre as duas implementações — trabalho já iniciado nas tarefas da iniciativa INT-2597 (que nasceu exatamente dessa comparação).

## O que deve ser feito

* **Não iniciar a implementação desta tarefa isoladamente.** Primeiro, validar com o time se ela deve ser fechada como "já coberta por outra iniciativa" (apontando para INT-2597) ou se sobrevive algum escopo residual.
* Se sobreviver escopo residual (funcionalidades específicas do upstream ausentes na implementação atual da Arquivei), tratar como tarefas adicionais dentro da iniciativa INT-2597, não como uma reescrita completa da classe já existente em produção.
* Caso a decisão do time seja, por algum motivo de arquitetura, **substituir** a implementação atual pela reescrita do upstream (cenário pouco provável dado que a implementação atual já é mais aderente ao modelo de dados vigente da NFS-e Nacional — IBS/CBS/infDPS — do que aparenta ser o objetivo original desta tarefa), isso precisa virar uma decisão arquitetural formal (ADR), não uma tarefa de sprint comum, dado o risco de regressão em um documento fiscal já em produção.

## Critérios de aceitação

* Existe uma decisão documentada (comentário na tarefa do Jira, ADR, ou equivalente) sobre qual dos três cenários acima se aplica, **antes** de qualquer código ser escrito.
* Se houver escopo residual, ele está claramente listado e rastreável nas tarefas da iniciativa INT-2597.
* Nenhum código é escrito "duplicando" a implementação já existente em `src/NFSe/Danfse.php` (`arquivei-releases`) sem essa decisão prévia.

## Restrições

* **Não fazer merge/substituição da classe `Danfse.php` já existente sem validação extensiva** — ela já está em produção; qualquer regressão afeta documentos fiscais reais já sendo emitidos.
* Não tratar esta tarefa como paralela/independente da iniciativa INT-2597 — são, na prática, o mesmo espaço de trabalho (o mesmo arquivo, `src/NFSe/Danfse.php`), e tratá-las separadamente é o cenário mais provável de gerar retrabalho ou conflito de merge.

## Dependências e links

* Iniciativa relacionada (mesmo arquivo, escopo já mais atualizado): [INT-2597 — Adequação ao PDF NFS-e Nacional](../INT-2597/00-iniciativa.md) e tarefas `01`–`06` dessa pasta
* Implementação atual em produção: `src/NFSe/Danfse.php` (branch `arquivei-releases`, PRs #35 e #36)
* Referência do upstream (ponto de partida da análise original, pode estar desatualizada frente ao que já foi implementado): [`nfephp-org/sped-da` — `src/NFSe/Danfse.php`](https://github.com/nfephp-org/sped-da/blob/master/src/NFSe/Danfse.php)

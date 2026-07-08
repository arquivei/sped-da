# Tarefa 8 — Decisão arquitetural: refatoração do `Danfe` em Traits

**Origem:** [INT-2581 — Fase 4](https://arquivei.atlassian.net/browse/INT-2581)
**Tipo:** Decisão arquitetural (não é uma tarefa de implementação)
**Risco:** Alto, caso executada
**Estimativa:** ~2–3 semanas **se e somente se aprovada** — esta tarefa em si é apenas a decisão, não a execução

## Contexto

O upstream decompôs `Danfe.php` em 14 Traits independentes (`TraitBlocoI` a `TraitBlocoX`, `TraitHeaderNfe`, `TraitItensNfe`, `TraitWaterMark`, etc.), distribuindo um arquivo que no fork ainda é monolítico (3.625 linhas em um único arquivo; o upstream tem 4.215 linhas distribuídas entre arquivo principal + traits).

A análise original é explícita: **"Não executar sem uma decisão arquitetural explícita. O custo/benefício é desfavorável a menos que haja um driver claro (novo tipo de documento obrigatório, bug crítico no upstream não presente no fork)."**

Motivos do alto risco, conforme a análise:

* O fork tem **19 métodos/propriedades exclusivos** que precisariam ser distribuídos entre os 14 Traits do upstream.
* **2 conflitos semânticos de API** entre fork e upstream:
  | Conflito | Upstream | Fork |
  |---|---|---|
  | Default de `gerarInformacoesAutomaticas` | `false` | `true` |
  | Controle de unidade tributável | `setOcultarUnidadeTributavel()` (opt-out) | `setMostrarUnidadeTributavel()` (opt-in) |
* O maior Trait (`TraitItensNfe`) tem 476 linhas e inclui o método `itens()`, que carrega as personalizações de largura de coluna e pedido de compra do fork — ou seja, é exatamente o Trait mais arriscado de todos para adaptar.
* Cada um dos 14 Traits precisaria ser auditado individualmente contra os 19 métodos exclusivos do fork.
* As inversões semânticas (tabela acima) quebram a API do `da-api` se não tratadas com compatibilidade retroativa — o `da-api` é um consumidor externo desta biblioteca, então qualquer mudança de comportamento default é uma mudança de contrato, não apenas interna.
* Alto risco de regressão visual no PDF gerado (o documento fiscal mais usado do pacote).

## O que deve ser feito (escopo desta tarefa — apenas a decisão)

1. Levar à liderança técnica/produto a pergunta: **existe hoje um driver de negócio concreto** que justifique essa refatoração (ex.: um novo tipo de documento obrigatório que só existe na estrutura em Traits, ou um bug crítico do upstream inexistente no fork que só é corrigível adotando a nova estrutura)?
2. Se a resposta for **não** (cenário mais provável, dado que as Tarefas 1–7 já cobrem os ganhos funcionais concretos identificados na análise, sem precisar da refatoração estrutural): **encerrar esta tarefa como "não fazer agora"**, documentando a decisão e a razão, para que não seja re-proposta sem uma revisão do contexto.
3. Se a resposta for **sim**: abrir uma tarefa de **planejamento** separada (não esta) para:
   - Mapear os 19 métodos/propriedades exclusivos do fork e decidir, para cada um, em qual Trait ele deveria viver.
   - Decidir e documentar (ADR) o comportamento de compatibilidade retroativa para os 2 conflitos semânticos — inclusive avaliando o impacto no `da-api`.
   - Definir uma estratégia de migração incremental (Trait por Trait, com testes de regressão visual a cada etapa) em vez de um "big bang".

## Critérios de aceitação

* Existe uma decisão registrada (aprovada/reprovada) sobre executar ou não a refatoração, com a justificativa do driver de negócio (ou a ausência dele).
* Se reprovada, a tarefa é encerrada sem código escrito — o valor entregue é a decisão documentada, evitando que a refatoração seja iniciada por inércia.
* Se aprovada, existe uma tarefa de planejamento subsequente, com o mapeamento dos 19 métodos exclusivos e a decisão sobre os 2 conflitos semânticos, antes de qualquer linha de código da refatoração ser escrita.

## Restrições

* **Não iniciar a implementação da refatoração em Traits a partir desta tarefa.** Esta tarefa é exclusivamente sobre a decisão de fazer ou não fazer.
* Não tratar a ausência de decisão como "sim implícito" — se a liderança não se posicionar, o padrão é **não fazer**, dado o risco alto e o custo/benefício desfavorável já apontado pela análise original.
* Se decidido fazer, não subestimar a quebra de compatibilidade do `da-api` — qualquer mudança nos defaults semânticos (`gerarInformacoesAutomaticas`, unidade tributável) precisa ser coordenada com o time responsável pelo `da-api` antes do merge, não depois.

## Dependências e links

* Arquivo: `src/NFe/Danfe.php` (3.625 linhas hoje, monolítico)
* Consumidor externo afetado por mudanças de contrato: `da-api`
* Referência de estrutura: `nfephp-org/sped-da` (upstream), `src/NFe/Traits/*.php` (14 arquivos)

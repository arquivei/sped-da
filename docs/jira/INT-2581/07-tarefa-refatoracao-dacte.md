# Tarefa 7 — Refatoração do `Dacte` (CTe) do upstream

**Origem:** [INT-2581 — Fase 3 (parte 4/4)](https://arquivei.atlassian.net/browse/INT-2581)
**Tipo:** Refatoração + possível nova funcionalidade
**Risco:** Médio
**Estimativa:** ~1636 linhas de diff (946 inserções / 690 deleções no upstream) — estimar 1–2 dias, incluindo revisão bloco a bloco

## Contexto

O upstream reestruturou significativamente o `Dacte.php` (946 inserções / 690 deleções). Diferente das classes novas (Tarefa 4) ou de adições cirúrgicas (Tarefas 2, 3, 6), este é o item de maior risco dentro da Fase 3 porque:

* É uma **reestruturação**, não apenas adição — partes do código existente são reescritas, o que aumenta a chance de conflito com as personalizações ISSQN e outras customizações já presentes no `Dacte.php` da Arquivei.
* A análise original já alerta: "DanfSe e Dacte têm grandes refatorações que podem conflitar com as personalizações ISSQN do fork. Requer revisão bloco a bloco."

Nota: o suporte a modal aquaviário completo no `Dacte` (tabela de containers) já está coberto separadamente pela **Tarefa 1** (sync baseline), que trata especificamente esse item como parte do sync `arquivei-releases ← master`. Esta Tarefa 7 é sobre a refatoração estrutural mais ampla do arquivo, não sobre esse recurso específico.

## O que deve ser feito

1. Obter o diff completo entre `upstream/master` e o ponto de divergência (`a5f2851`) para `src/CTe/Dacte.php`.
2. Revisar o diff **bloco a bloco** (não como um merge automático), identificando:
   - Trechos que são puramente estruturais/de organização de código (sem mudança de comportamento) — baixo risco, podem ser adotados diretamente.
   - Trechos que mudam comportamento de renderização — precisam de comparação visual antes/depois.
   - Trechos que tocam áreas onde a Arquivei tem customização de ISSQN (identificar essas áreas primeiro, antes de iniciar o porte, para saber onde redobrar a atenção).
3. Portar as mudanças de forma incremental, com testes visuais (PDF antes/depois) a cada bloco significativo portado, priorizando não quebrar a lógica de ISSQN customizada.
4. Caso algum trecho da refatoração seja incompatível com a customização ISSQN a ponto de exigir reescrita (não apenas adaptação), documentar essa decisão e escalar para revisão antes de prosseguir — não forçar a compatibilidade "na marra" em uma classe de documento fiscal.

## Critérios de aceitação

* A lógica de ISSQN customizada da Arquivei continua funcionando de forma idêntica após a refatoração (validado com XMLs reais que exercitam essa lógica).
* O restante da refatoração (organização, bugs corrigidos pelo upstream nessa reescrita, se houver) é adotado sem regressão visual no PDF gerado.
* Existe um registro (comentário de PR, documento) de quais partes do diff upstream foram adotadas e quais foram deliberadamente descartadas/adaptadas, com justificativa.

## Restrições

* **Não fazer merge automático do diff.** Esta é explicitamente a orientação da análise original ("requer revisão bloco a bloco") — qualquer ferramenta de merge automático (`git merge`, `git cherry-pick` direto) deve ser tratada como ponto de partida para revisão manual, não como resultado final.
* Rodar a suíte de testes existente (e expandi-la, se a cobertura de ISSQN for insuficiente) antes de considerar a tarefa concluída — este é o item de maior risco de regressão silenciosa de toda a análise INT-2581, fora a Fase 4.
* Se o prazo estimado (1–2 dias) se mostrar insuficiente durante a revisão bloco a bloco, preferir parar e escalar a manter velocidade à custa de pular a validação da lógica ISSQN.

## Dependências e links

* Arquivo: `src/CTe/Dacte.php`
* Relacionado a (mesmo arquivo, mas escopo distinto): [Tarefa 1 — Sync baseline](./01-tarefa-sync-baseline.md) (modal aquaviário)
* Referência de comportamento: `nfephp-org/sped-da` (upstream), `src/CTe/Dacte.php`

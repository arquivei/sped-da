# Tarefa 6 — MDFe: suporte a CIOT e impressão de percursos (`Damdfe`)

**Origem:** [INT-2581 — Fase 3 (parte 3/4)](https://arquivei.atlassian.net/browse/INT-2581)
**Tipo:** Nova funcionalidade
**Risco:** Baixo/Médio
**Estimativa:** ~970 linhas de diff (595 inserções / 375 deleções no upstream) — estimar ~1 dia

## Contexto

O upstream adicionou ao `Damdfe.php`:

* Suporte a `infCIOT` — código identificador da operação de transporte (CIOT), usado para vincular o MDF-e ao registro da ANTT quando aplicável.
* Impressão de percursos (`infPercurso` — sequência de UFs pelas quais o veículo passa) no documento auxiliar.

Nenhum dos dois existe hoje no `Damdfe.php` da Arquivei.

Vale lembrar que a Tarefa 1 desta mesma quebra (sync baseline) já porta uma melhoria de layout no `Damdfe` (altura do box de observações, de 30mm para 145mm) — como as duas tarefas tocam o mesmo arquivo, a ordem de execução deve ser coordenada para evitar conflito de merge.

## O que deve ser feito

1. Portar o suporte a `infCIOT`:
   - Ler o(s) nó(s) `infCIOT` do XML do MDF-e (pode haver mais de uma ocorrência, uma por contratante/CIOT).
   - Exibir o(s) código(s) CIOT em um bloco/campo apropriado no layout do Damdfe, seguindo o padrão visual já usado para campos similares (ex.: informações de contratante/contratado de transporte, se já existirem).
2. Portar a impressão de percursos (`infPercurso`):
   - Ler a lista de UFs de percurso do XML.
   - Exibir a sequência de UFs no documento, em um formato compacto (ex.: "SP → MG → RJ"), sem quebrar o layout quando houver muitas UFs (validar comportamento com um percurso de 5+ UFs).
3. Adicionar fixtures de XML de MDF-e com `infCIOT` e com `infPercurso` preenchidos (a fixture atual provavelmente não cobre nenhum dos dois campos, já que são funcionalidades ausentes).
4. Adicionar testes unitários cobrindo:
   - MDF-e com `infCIOT` presente e ausente (campo opcional).
   - MDF-e com `infPercurso` de 1, 2 e 5+ UFs.

## Critérios de aceitação

* CIOT e percurso aparecem corretamente no PDF quando presentes no XML.
* Ausência desses campos (MDF-e sem CIOT/percurso) não altera o layout existente — sem espaços em branco ou quebras de página.
* Percurso com muitas UFs não estoura a área reservada no layout (trunca/quebra linha de forma controlada).

## Restrições

* Coordenar a ordem de execução com a **Tarefa 1** (sync baseline), que já altera o `Damdfe.php` (box de observações) — implementar em sequência ou avisar quem estiver trabalhando nas duas para evitar conflito de merge no mesmo arquivo.
* Não alterar o cálculo de layout de blocos que não têm relação com CIOT/percurso — mudança deve ser aditiva ao layout existente do Damdfe.
* Validar contra pelo menos um XML real de MDF-e com CIOT (não apenas a fixture sintética), já que o formato de exibição de CIOT pode ter particularidades (ex.: múltiplos CIOTs por contratante) não cobertas por um XML de teste simples.

## Dependências e links

* Arquivo: `src/MDFe/Damdfe.php`
* Depende de/relacionado a: [Tarefa 1 — Sync baseline](./01-tarefa-sync-baseline.md) (mesmo arquivo)
* Referência de comportamento: `nfephp-org/sped-da` (upstream), `src/MDFe/Damdfe.php`

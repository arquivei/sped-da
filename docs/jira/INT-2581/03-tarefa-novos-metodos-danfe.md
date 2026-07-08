# Tarefa 3 — Novos métodos no `Danfe` existente

**Origem:** [INT-2581 — Fase 2](https://arquivei.atlassian.net/browse/INT-2581)
**Tipo:** Nova funcionalidade (adição pura, sem conflito)
**Risco:** Baixo
**Estimativa:** 4–8h

## Contexto

O upstream adicionou métodos ao `Danfe.php` que não existem na versão da Arquivei. São adições puras — não entram em conflito com métodos exclusivos do fork, o que os torna seguros para portar isoladamente, um de cada vez, com teste unitário próprio.

## O que deve ser feito

**Verificado diretamente no código antes de escrever esta tarefa** (branch `arquivei-releases`, onde o `Danfe.php` de produção vive):

* `calculoEspacoVericalDadosAdicionais()` **já existe** em `Danfe.php` (também presente em `master`) — remover este item do escopo, já foi feito.
* O e-mail do destinatário **já é exibido hoje, sempre, sem controle** (`$this->textoAdic .= $this->getTagValue($this->dest, "email", ...)`, sem nenhuma flag condicionando a exibição).
* As observações do contribuinte (`obsCont`) **já são exibidas hoje, sempre, sem controle** (bloco `foreach ($obsCont as $obs)`, sem flag).
* Ou seja, os métodos `setExibirEmailDestinatario()` e `obsContShow($flag)` do upstream não introduzem uma funcionalidade nova — introduzem a **capacidade de desligar** algo que já é exibido incondicionalmente hoje.

Portar para `src/NFe/Danfe.php`:

1. **`epec($protocolo, $data)`** — adiciona dados de EPEC (Evento Prévio de Emissão em Contingência) ao documento. **Atenção:** o fork já tem um mecanismo relacionado, `$numero_registro_dpec` (usado em pelo menos 3 pontos do arquivo para contingência DPEC) — antes de portar, mapear exatamente a diferença entre EPEC e DPEC no contexto da NF-e (são dois modos de contingência distintos) e confirmar que não há sobreposição de responsabilidade entre o método novo e o campo existente. Não portar `epec()` como uma cópia cega se ele acabar duplicando/conflitando com `$numero_registro_dpec`.
2. **`obsContShow($flag)`** — adiciona uma flag (default `true`, para não quebrar o comportamento atual) que permite suprimir a exibição de `obsCont` quando `false`.
3. **`setExibirEmailDestinatario()`** — adiciona uma flag (default `true`, para não quebrar o comportamento atual) que permite suprimir a exibição do e-mail do destinatário quando `false`.
4. **`setTitle($title)`** — define o título do documento PDF (metadado do arquivo, não conteúdo visual da página). Não existe hoje no fork.

Para cada método:
- Portar a implementação, adaptando nomes/convenções ao estilo já usado no `Danfe.php` da Arquivei (idioma dos comentários, nomenclatura de variáveis).
- Adicionar teste unitário isolado validando o efeito do método no PDF gerado (presença/ausência do conteúdo controlado pelo método), incluindo o teste de que o **default continua exibindo** o conteúdo (sem regressão para quem não chamar o novo método).

## Critérios de aceitação

* Os 4 métodos estão disponíveis publicamente na classe `Danfe`, com o mesmo comportamento documentado no upstream.
* Por padrão (sem chamar os novos métodos), o comportamento do PDF gerado é **idêntico ao atual** — e-mail e `obsCont` continuam aparecendo, exatamente como hoje.
* `epec()` tem sua relação com `$numero_registro_dpec` documentada (comentário no código explicando a diferença) e não introduz comportamento ambíguo quando ambos são usados/configurados na mesma instância.
* Cada método tem cobertura de teste unitário próprio, incluindo o caso "não chamado" (default).
* Nenhum método existente do `Danfe.php` da Arquivei teve seu comportamento alterado como efeito colateral.

## Restrições

* Portar **método a método, em commits/PRs separados ou claramente isolados**, para permitir revisão e rollback independente — não fazer um único commit "porta todos os métodos novos".
* Não alterar a assinatura de métodos já existentes no fork para "encaixar" os novos métodos — os métodos novos devem ser aditivos.
* Os defaults de `obsContShow()` e `setExibirEmailDestinatario()` **devem** preservar o comportamento atual (exibir) — o valor padrão não é uma escolha de estilo, é uma restrição de não-regressão, já que hoje esses dados são sempre exibidos.

## Dependências e links

* Arquivo: `src/NFe/Danfe.php`
* Referência de comportamento: `nfephp-org/sped-da` (upstream), classe `Danfe`

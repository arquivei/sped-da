# Tarefa 1 — Corrigir mapeamento de `cStat` na marca d'água (CANCELADA/SUBSTITUÍDA)

**Iniciativa:** [INT-2597 — Adequação ao PDF NFS-e Nacional](./00-iniciativa.md)
**Tipo:** Bug
**Risco:** Baixo
**Estimativa sugerida:** 2–4h (inclui confirmação da tabela oficial de códigos)

## Contexto

`src/NFSe/Danfse.php` tem **duas** funções diferentes lendo o mesmo campo `cStat` do XML (`infNFSe/cStat`) com tabelas de código **incompatíveis entre si**:

* `watermark()` (linha ~860-885) compara o valor bruto de `cStat` contra `'2'` e `'3'`:
  ```php
  $message = match ($cStat) {
      '2'     => 'CANCELADA',
      '3'     => 'SUBSTITUÍDA',
      default => '',
  };
  ```
* `getCStatLabel()` (linha ~1102-1111), usada no campo "SITUAÇÃO DA NFS-e" do bloco de identificação, trata `cStat` como um código de **3 dígitos**:
  ```php
  $map = [
      '100' => 'NFS-e Gerada',
      '101' => 'NFS-e de Substituição Gerada',
      '102' => 'NFS-e de Decisão Judicial',
      '103' => 'NFS-e Avulsa',
  ];
  ```

Como o `cStat` real da NFS-e Nacional é um código de 3 dígitos (a própria `getCStatLabel` já assume isso), a comparação em `watermark()` contra `'2'`/`'3'` nunca deve casar com um valor real do XML — ou seja, **a marca d'água de CANCELADA/SUBSTITUÍDA provavelmente nunca é exibida em produção**, mesmo quando a nota está de fato cancelada ou substituída.

Além disso, os dois mapas atribuem **significados diferentes** ao mesmo código: `getCStatLabel` mapeia `101` como "NFS-e de Substituição Gerada" e `102` como "NFS-e de Decisão Judicial", enquanto a implementação de referência do upstream (`nfephp-org/sped-da`, `src/NFSe/Danfse.php`) usa `101`/`135` para CANCELADA e `102`/`151` para SUBSTITUÍDA. Antes de corrigir o código, é preciso confirmar qual tabela está correta para o schema vigente da NFS-e Nacional — os dois arquivos não podem estar certos ao mesmo tempo.

## O que deve ser feito

1. **Confirmar a tabela oficial de códigos `cStat`** vigente para a NFS-e Nacional (schema ADN / NT-008), junto à documentação oficial (https://www.gov.br/nfse ou o schema XSD `NFSe_v1.xx.xsd` usado como referência de geração) ou com o time de produto/fiscal da Arquivei que já opera esse documento em produção. Documentar a tabela completa (não apenas os 4 códigos hoje mapeados).
2. Unificar a leitura de `cStat` em **um único método/tabela** (ex.: um `const` array `CSTAT_LABELS` e um helper `isCancelada(string $cStat)` / `isSubstituida(string $cStat)`), eliminando a duplicidade entre `watermark()` e `getCStatLabel()`.
3. Corrigir `watermark()` para usar os códigos corretos confirmados no passo 1.
4. Corrigir `getCStatLabel()` caso a apuração do passo 1 mostre que o mapeamento atual está errado (rótulos ou códigos).
5. Adicionar teste unitário que carregue um XML de fixture com `cStat` de nota cancelada e outro com `cStat` de nota substituída, e valide que:
   - O texto "SITUAÇÃO DA NFS-e" mostra o rótulo esperado.
   - A marca d'água correspondente é desenhada (pode ser verificado indiretamente via o método interno chamado, ou via snapshot/assert de que o PDF gerado contém o texto — usar a mesma estratégia de teste já usada pelos testes existentes de `Danfse`, se houver).

## Critérios de aceitação

* Uma nota com `cStat` de cancelamento gera a marca d'água "CANCELADA".
* Uma nota com `cStat` de substituição gera a marca d'água "SUBSTITUÍDA".
* Uma nota com `cStat` de situação normal (autorizada) não gera nenhuma marca d'água.
* Não existe mais mais de um mapa de códigos `cStat` no arquivo.
* O rótulo textual em "SITUAÇÃO DA NFS-e" é consistente com a tabela oficial confirmada.

## Restrições

* Não alterar o layout/posicionamento da marca d'água (fonte, rotação, cor) — o escopo é exclusivamente a correção do mapeamento de códigos, não o desenho.
* Não introduzir dependência de uma flag externa tipo `setAsCanceled()`/`setAsSubstituted()` do upstream nesta tarefa — isso é tratado separadamente (ver observação na iniciativa; se o produto pedir esse controle explícito, abrir uma tarefa nova, pois implica em mudança de API pública).
* Se a tabela oficial de `cStat` não puder ser confirmada com uma fonte externa, escalar para o time de produto/fiscal antes de alterar o código — não adivinhar os códigos.

## Dependências e links

* Arquivo: `src/NFSe/Danfse.php` (métodos `watermark()` e `getCStatLabel()`)
* Referência de comportamento (não normativa): [`nfephp-org/sped-da` — `Danfse.php`, método `drawWatermark()`](https://github.com/nfephp-org/sped-da/blob/master/src/NFSe/Danfse.php)
* Fixture de teste: `tests/fixtures/xml/nfse.xml` (pode ser necessário criar variantes com `cStat` de cancelamento/substituição)

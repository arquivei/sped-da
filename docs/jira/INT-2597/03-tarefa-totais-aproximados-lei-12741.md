# Tarefa 3 — Implementar bloco "Totais Aproximados dos Tributos" (Lei nº 12.741/2012)

**Iniciativa:** [INT-2597 — Adequação ao PDF NFS-e Nacional](./00-iniciativa.md)
**Tipo:** Gap de compliance
**Risco:** Baixo
**Estimativa sugerida:** 3–5h

## Contexto

A Lei nº 12.741/2012 (Lei da Transparência Fiscal) exige que documentos fiscais emitidos ao consumidor final informem, de forma aproximada, a carga tributária federal, estadual e municipal incidente sobre a operação. O XML da NFS-e Nacional carrega essa informação no bloco `trib/totTrib` (dentro de `infDPS/valores/trib`), com os campos `vTotTribFed`/`pTotTribFed`, `vTotTribEst`/`pTotTribEst` e `vTotTribMun`/`pTotTribMun` (valor absoluto ou percentual, a depender de como o prestador optou por informar).

O método `blocoInfoCompl()` (linha ~817-838) do `Danfse.php` atual só imprime o texto livre de `xInfComp`:

```php
private function blocoInfoCompl(float $y): float
{
    ...
    $xInfComp = $this->getTagValue($ic, 'xInfComp') ?: '';
    ...
    $this->pdf->textBox(..., $xInfComp ?: '-', ...);
    return $y + $h;
}
```

Não há, em nenhum outro ponto do arquivo, qualquer leitura ou exibição do total aproximado de tributos — a exigência legal simplesmente não é atendida hoje. A implementação de referência do upstream resolve isso no método `totaisAproximados()`, concatenando o texto fixo "Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: R$ X ; Estaduais: R$ Y ; Municipais: R$ Z" ao final do texto de informações complementares.

## O que deve ser feito

1. Implementar um método (ex.: `totaisAproximadosTributos()`) que:
   - Localize o nó `infDPS/valores/trib/totTrib`.
   - Leia os valores/percentuais federal, estadual e municipal, com fallback entre valor absoluto (`vTotTrib*`) e percentual (`pTotTrib*`) — priorizar valor absoluto quando presente, exibindo percentual apenas se o valor absoluto não existir no XML.
   - Formate cada total como moeda (`R$ 0,00`) quando for valor absoluto, ou como percentual quando for o campo `p*`.
   - Monte a string final no formato: `Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: <valor> ; Estaduais: <valor> ; Municipais: <valor>`.
   - Trate a ausência completa do nó `totTrib` (campo opcional no schema) exibindo `-` para os três totais, sem lançar erro.
2. Anexar essa string ao conteúdo de Informações Complementares em `blocoInfoCompl()`, preferencialmente como uma linha separada (quebra de linha) após o texto livre de `xInfComp`, para não misturar visualmente as duas informações.
3. Garantir que o texto acomode o limite de espaço já calculado por `calculaAlturaInfoCompl()` — se necessário, ajustar esse cálculo para considerar o texto adicional (ele é sempre presente quando há bloco de valores, diferentemente de `xInfComp`, que é opcional).
4. Adicionar teste unitário cobrindo:
   - XML com os três totais informados como valor absoluto.
   - XML com totais informados como percentual (sem valor absoluto).
   - XML sem o nó `totTrib` (deve exibir `-` nos três, sem erro/exception).

## Critérios de aceitação

* O bloco de Informações Complementares sempre exibe a linha de totais aproximados de tributos, mesmo quando `xInfComp` está vazio.
* Os valores exibidos batem com o XML de origem (validar manualmente contra pelo menos um XML real de produção, além da fixture).
* Ausência do nó `totTrib` no XML não quebra a geração do PDF.
* Nenhuma regressão no texto livre de `xInfComp` que já era exibido.

## Restrições

* Não remover ou reformatar o texto de `xInfComp` já existente — apenas adicionar a nova linha.
* Seguir o mesmo padrão de formatação monetária (`R$ 0,00`, separador de milhar `.`, decimal `,`) já usado em outros campos monetários do arquivo (ver `formatMoney`/equivalente usado nos blocos de valores, se existir; caso não exista um helper compartilhado, reutilizar a lógica de formatação já aplicada em `blocoValorTotal()`).
* Não inventar uma tabela de "aproximação" própria — a informação vem diretamente do XML (`totTrib`), não deve ser calculada localmente pela biblioteca.

## Dependências e links

* Arquivo: `src/NFSe/Danfse.php` (método `blocoInfoCompl()`, e ajuste em `calculaAlturaInfoCompl()`)
* Lei nº 12.741/2012 — Lei da Transparência Fiscal
* Referência de comportamento (não normativa): [`nfephp-org/sped-da` — `Danfse.php`, método `totaisAproximados()`](https://github.com/nfephp-org/sped-da/blob/master/src/NFSe/Danfse.php)
* Fixture de teste: `tests/fixtures/xml/nfse.xml` (pode precisar de variante com `totTrib` ausente e outra com percentuais em vez de valores)

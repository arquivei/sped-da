# Tarefa 5 — Suspender bloco "Tributação Federal (Exceto CBS)" a partir da competência 2027

**Iniciativa:** [INT-2597 — Adequação ao PDF NFS-e Nacional](./00-iniciativa.md)
**Tipo:** Gap regulatório (Reforma Tributária)
**Risco:** Médio (depende de confirmação de calendário legal/produto antes de codificar)
**Estimativa sugerida:** 3–5h (inclui confirmação da regra de negócio)

## Contexto

O `Danfse.php` atual sempre desenha o bloco "TRIBUTAÇÃO FEDERAL (EXCETO CBS)" (`blocoTribFederal()`, linha ~693-716), exibindo IRRF, Contribuição Previdenciária Retida, Contribuições Sociais Retidas, PIS e COFINS — tributos federais que, com a transição da Reforma Tributária (EC 132/2023 e legislação complementar), estão sendo substituídos pela CBS ao longo de um cronograma de transição.

A implementação de referência do upstream já trata isso: o método `printFederalTax()` suprime esse bloco inteiro quando a competência da nota (`dCompet`) é posterior a 2026:

```php
private function printFederalTax()
{
    $compet = $this->value('dCompet', $this->infDPS);
    if ($compet === '') {
        return true;
    }
    return substr($compet, 0, 4) <= '2026';
}
```

Ou seja, a partir de notas com competência em 2027, o upstream já não exibe mais o bloco de tributos federais "legados" (o que faz sentido se PIS/COFINS deixam de ser apurados/retidos sobre essas operações a partir dessa data). O fork **não tem nenhuma lógica equivalente** — o bloco é sempre desenhado, mesmo que os campos venham vazios do XML (o que hoje já resulta em campos com "-", mas ocupando espaço na página e podendo confundir o usuário quanto à vigência do tributo).

**Importante:** o corte exato (ano de competência, e se é uma supressão total do bloco ou uma alteração de conteúdo) é uma decisão de calendário tributário que pode ter sido ajustada por lei complementar entre a escrita da versão do upstream e hoje (07/07/2026 — ou seja, já estamos no segundo semestre do próprio ano de corte usado pelo upstream, `2026`). Não implementar o corte "as-is" do upstream sem confirmar a data vigente.

## O que deve ser feito

1. **Confirmar com o time de produto/fiscal da Arquivei (ou consultando a legislação vigente da Reforma Tributária)** qual é o calendário correto de transição para a supressão/manutenção deste bloco — se ainda é o ano de 2026 (cutoff usado pelo upstream) ou se já foi alterado por lei complementar/ato do Comitê Gestor do IBS.
2. Implementar um método equivalente a `printFederalTax()` no `Danfse.php`, usando o ano de corte confirmado no passo 1 como uma constante nomeada (ex.: `private const ANO_LIMITE_TRIBUTACAO_FEDERAL_LEGADA = 2026;`), não um número mágico solto no meio do código.
3. Ajustar `monta()`/`calculaLayout()` para que, quando o bloco for suprimido:
   - O espaço vertical que ele ocupava (`hTribFederal`) seja recalculado/removido do layout, e os blocos seguintes (IBS/CBS, Valor Total, Informações Complementares) subam para preencher o espaço — não deixar um vão em branco na página.
4. Adicionar teste unitário cobrindo:
   - XML com `dCompet` anterior/igual ao ano de corte → bloco aparece normalmente.
   - XML com `dCompet` posterior ao ano de corte → bloco não aparece, e os blocos seguintes ocupam o espaço vazio corretamente.
   - XML sem `dCompet` preenchido → comportamento default deve ser **exibir** o bloco (mesma decisão conservadora do upstream: `return true` quando vazio), evitar esconder informação por ausência de dado.

## Critérios de aceitação

* A decisão sobre o ano de corte é documentada no código (comentário explicando a origem/norma) e não é um valor "adivinhado" copiado do upstream sem verificação.
* O bloco é corretamente suprimido/exibido conforme a competência da nota.
* Não sobra espaço em branco na página quando o bloco é suprimido.
* Comportamento com `dCompet` ausente é conservador (exibe o bloco).

## Restrições

* **Não implementar esta tarefa sem a confirmação do passo 1.** Se não for possível confirmar o calendário com uma fonte confiável dentro do prazo da sprint, abrir a tarefa como bloqueada e sinalizar ao Product Owner — não estimar por conta própria uma data regulatória.
* Não misturar esta lógica de supressão com a Tarefa 6 (revisão dos campos IBS/CBS) — são independentes, mas ambas mexem em blocos vizinhos (`blocoTribFederal` e `blocoIBSCBS`); coordenar a ordem de merge para evitar conflitos.
* Não remover o método `blocoTribFederal()` nem os campos que ele lê — a supressão deve ser condicional (por competência), não uma remoção permanente do recurso, já que a biblioteca ainda precisa gerar corretamente DANFSe de competências anteriores ao corte (inclusive para retransmissão/reimpressão de notas antigas).

## Dependências e links

* Arquivo: `src/NFSe/Danfse.php` (métodos `blocoTribFederal()`, `calculaLayout()`)
* Depende de: confirmação de calendário regulatório junto ao time de produto/fiscal da Arquivei
* Referência de comportamento (não normativa, calendário pode estar desatualizado): [`nfephp-org/sped-da` — `Danfse.php`, método `printFederalTax()`](https://github.com/nfephp-org/sped-da/blob/master/src/NFSe/Danfse.php)
* Contexto legal: Emenda Constitucional nº 132/2023 e legislação complementar da Reforma Tributária (cronograma de transição do PIS/COFINS/IRRF/CSLL para o CBS)

# Tarefa 4 — Completar campos das Informações Complementares

**Iniciativa:** [INT-2597 — Adequação ao PDF NFS-e Nacional](./00-iniciativa.md)
**Tipo:** Gap funcional
**Risco:** Baixo/Médio
**Estimativa sugerida:** 4–6h (independente da Tarefa 3, mas toca o mesmo método — recomenda-se implementar em sequência com ela para evitar conflito de merge)

## Contexto

O bloco "INFORMAÇÕES COMPLEMENTARES" do `Danfse.php` atual (`blocoInfoCompl()`, linha ~817-838) exibe **apenas** o campo `infDPS/infoCompl/xInfComp` (texto livre digitado pelo prestador). O schema da NFS-e Nacional carrega, no entanto, diversas outras informações relevantes que hoje **não aparecem em lugar nenhum do PDF**:

| Campo no XML | Onde fica | Significado |
|---|---|---|
| `subst/chSubstda` | `infDPS/subst` | Chave de acesso da NFS-e substituída (quando a nota é uma substituição) |
| `infoCompl/docRef` | `infDPS/serv/infoCompl` | Documento de referência informado pelo prestador |
| `obra/cObra` | `infDPS/serv/obra` | Código da obra (serviços de construção civil) |
| `obra/inscImobFisc` | `infDPS/serv/obra` | Inscrição imobiliária fiscal da obra |
| `atvEvento/idAtvEvt` | `infDPS/serv/atvEvento` | Identificador de atividade de evento |
| `infoCompl/idDocTec` | `infDPS/serv/infoCompl` | Identificador de documento técnico |
| `infoCompl/xPed` / `infoCompl/xItemPed` | `infDPS/serv/infoCompl` | Número e item do pedido de compra |
| `infoCompl/xOutInf` | `infDPS/serv/infoCompl` | Outras informações de interesse do município |
| `IBSCBS/imovel/inscImobFisc` | `infDPS/IBSCBS/imovel` | Inscrição imobiliária fiscal (bloco IBS/CBS, quando aplicável) |

Nenhum desses campos é lido hoje pelo `Danfse.php` do fork. Isso é relevante em particular para prestadores do setor de construção civil (`obra`) e para tomadores que exigem referência de número de pedido de compra na nota — um padrão que, aliás, **já existe como funcionalidade dedicada** em outras classes do pacote (`$exibirNumeroItemPedido` / `$exibirNumeroPedidoCompra`, citados na análise INT-2581 como customização do `Danfe.php` para NF-e) — ou seja, a ausência desse dado no DANFSe é uma inconsistência frente a um requisito que a Arquivei já reconhece como importante para outros documentos.

## O que deve ser feito

1. Implementar a leitura de cada campo da tabela acima, com os respectivos `getElementsByTagName`/`getTagValue` já usados no restante do arquivo.
2. Montar as informações como uma lista de pares rótulo/valor, exibidos apenas quando o campo estiver presente no XML (omitir silenciosamente os ausentes, sem imprimir rótulos vazios) — seguir o padrão de composição condicional (equivalente ao `appendInfo()` da implementação de referência do upstream: só adiciona o par "Rótulo: valor" se o valor não for vazio).
3. Definir um formato de exibição compacto e consistente com o espaço disponível no bloco (ex.: `Doc. Ref.: X | NFS-e Subst.: Y | Cod. Obra: Z | ...`), já que o bloco de Informações Complementares tem altura limitada e compartilhada com o texto livre (`xInfComp`) e, após a Tarefa 3, também com os totais de tributos.
4. Truncar com reticências (`...`) se o texto total ultrapassar o espaço disponível, evitando overflow do bloco — seguir o padrão de truncamento por caracteres já usado em outros campos do arquivo (ex.: `mb_substr` + `...`, usado em `getRegApTribSNLabel()`).
5. Adicionar teste unitário cobrindo:
   - XML com todos os campos opcionais presentes → todos aparecem no texto final, na ordem definida.
   - XML sem nenhum dos campos opcionais → bloco mostra apenas `xInfComp` (ou `-` se também vazio), sem rótulos "soltos".
   - XML com texto muito longo (soma de todos os campos + `xInfComp`) → texto é truncado sem quebrar o layout do bloco.

## Critérios de aceitação

* Todos os 9 campos listados na tabela são exibidos quando presentes no XML.
* Campos ausentes não deixam rótulos vazios nem separadores duplicados/sobrando (ex.: `" | | "`).
* O bloco nunca ultrapassa a altura calculada por `calculaAlturaInfoCompl()`.
* Revisão visual confirma que o bloco continua legível mesmo no cenário de "todos os campos preenchidos + texto livre longo + totais de tributos" (cenário combinado com a Tarefa 3).

## Restrições

* Esta tarefa depende logicamente da Tarefa 3 (totais aproximados) porque as duas escrevem no mesmo bloco (`blocoInfoCompl()`) e competem pelo mesmo espaço vertical — implementar e revisar as duas juntas para dimensionar corretamente `calculaAlturaInfoCompl()`, mesmo que sejam PRs separados.
* Não é necessário implementar o cenário "Cod. Evt." / "Doc. Tec." com validação de formato — apenas exibir o valor bruto do XML, sem regra de negócio adicional.
* Priorizar clareza e não quebrar o layout: se o espaço for insuficiente para todos os campos em cenários extremos, truncar (não expandir a altura do bloco dinamicamente além do que os demais blocos permitem na página A4).

## Dependências e links

* Arquivo: `src/NFSe/Danfse.php` (método `blocoInfoCompl()`, e ajuste em `calculaAlturaInfoCompl()`)
* Depende de: [Tarefa 3 — Totais Aproximados dos Tributos](./03-tarefa-totais-aproximados-lei-12741.md) (mesmo método, mesmo espaço de tela)
* Referência de comportamento (não normativa): [`nfephp-org/sped-da` — `Danfse.php`, métodos `informacoesComplementares()` e `appendInfo()`](https://github.com/nfephp-org/sped-da/blob/master/src/NFSe/Danfse.php)
* Contexto de precedente no mesmo pacote: customização `$exibirNumeroItemPedido`/`$exibirNumeroPedidoCompra` do `Danfe.php` (NF-e), citada em INT-2581
* Fixture de teste: `tests/fixtures/xml/nfse.xml` (precisa de variantes com os campos opcionais preenchidos)

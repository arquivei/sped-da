# Tarefa 6 — Revisar extração dos campos de alíquota/valor do bloco IBS/CBS

**Iniciativa:** [INT-2597 — Adequação ao PDF NFS-e Nacional](./00-iniciativa.md)
**Tipo:** Validação técnica / possível bug
**Risco:** Médio (o schema IBS/CBS da Reforma Tributária ainda está em evolução; requer confirmação contra XML real antes de alterar)
**Estimativa sugerida:** 4–8h (inclui obtenção/validação de XML real de produção com IBS/CBS)

## Contexto

O bloco "TRIBUTAÇÃO IBS / CBS" é lido em dois lugares distintos do `Danfse.php`, com estruturas de nós **diferentes** da implementação de referência do upstream:

**Fork (`blocoIBSCBS()` / `getIBSCBSValues()`, linha ~719-774)** lê as alíquotas e valores **diretamente** do nó `trib` (dentro de `IBSCBS/values/trib`):

```php
'pIBSUF'  => $this->getTagValue($trib, 'pIBSUF')       ?: '0',
'pIBSMun' => $this->getTagValue($trib, 'pIBSMun')      ?: '0',
'pEfMun'  => $this->getTagValue($trib, 'pAliqEfetMun') ?: '0.00',
'vIBSMun' => $this->getTagValue($trib, 'vIBSMun')      ?: '0.00',
'pEfUF'   => $this->getTagValue($trib, 'pAliqEfetUF')  ?: '0.00',
'vIBSUF'  => $this->getTagValue($trib, 'vIBSUF')       ?: '0.00',
...
```

**Upstream (`drawIbsCbs()`)** lê as mesmas informações a partir de **sub-nós separados** `uf`, `mun` e `fed` dentro do bloco de valores do IBS/CBS:

```php
$uf = $this->childNode('uf', $valores);
$mun = $this->childNode('mun', $valores);
$fed = $this->childNode('fed', $valores);
...
$this->percent($this->value('pIBSUF', $uf))   // lido de dentro de <uf>
$this->percent($this->value('pIBSMun', $mun)) // lido de dentro de <mun>
$this->percent($this->value('pCBS', $fed))    // lido de dentro de <fed>
```

Ou seja, as duas implementações assumem **posições diferentes no XML** para o mesmo dado. Isso é esperado quando duas equipes implementam de forma independente contra uma especificação que estava em rascunho/revisão no momento de cada implementação (o bloco IBS/CBS da NFS-e Nacional está diretamente ligado à Reforma Tributária, cujo schema técnico ainda passou por revisões ao longo de 2025-2026). **Não se sabe, sem checar contra um XML real e/ou o XSD oficial vigente, qual das duas estruturas está correta** — ou se a estrutura oficial mudou entre a escrita das duas implementações e hoje.

Se o fork estiver lendo do nó errado, os campos de alíquota/valor do IBS/CBS estarão **sempre zerados ou incorretos** em produção sempre que o prestador realmente tiver dados de IBS/CBS segregados por UF/Município/Federal no XML.

## O que deve ser feito

1. Obter o XSD oficial vigente do schema da NFS-e Nacional (bloco `IBSCBS`) junto à documentação do ADN (Ambiente de Dados Nacional) ou confirmar com o time de produto/fiscal da Arquivei qual é a fonte de schema usada na geração/validação de XML hoje.
2. Obter ao menos um XML real (ou fixture gerada por um emissor homologado) de uma NFS-e com tributação IBS/CBS efetivamente preenchida (não apenas com campos zerados), para validar empiricamente contra qual estrutura de nós (`trib` direto vs. sub-nós `uf`/`mun`/`fed`) o campo realmente é populado.
3. Corrigir `getIBSCBSValues()` (e o code path relacionado em `blocoIBSCBS()`) para ler a partir da estrutura confirmada no passo 2. Caso a estrutura real seja a de sub-nós (`uf`/`mun`/`fed`), refatorar `getIBSCBSValues()` para navegar até eles antes de extrair cada campo.
4. Se a investigação mostrar que **ambas** as estruturas podem aparecer a depender da versão do emissor/schema em uso (períodos de transição são comuns em specs novas), implementar fallback: tentar a leitura a partir dos sub-nós e, se ausente, tentar a leitura direta do nó `trib` (compatibilidade com XMLs antigos já emitidos).
5. Atualizar a fixture de teste (`tests/fixtures/xml/nfse.xml`) e/ou criar uma fixture adicional com dados de IBS/CBS reais e não-zerados, já que a fixture atual aparentemente não exercita esse cenário com valores diferentes de zero (os defaults `'0'`/`'0.00'` no código sugerem que os testes até hoje nunca precisaram validar um valor real diferente do fallback).
6. Adicionar teste unitário cobrindo:
   - XML com valores de IBS/CBS efetivamente preenchidos na estrutura confirmada como correta → valores extraídos batem com o XML.
   - XML sem bloco IBS/CBS (operação não sujeita) → campos mostram os defaults (`0`/`0.00`), sem erro.

## Critérios de aceitação

* A extração de cada campo do bloco IBS/CBS é validada contra pelo menos um XML real com dados não-zerados.
* Os valores exibidos no PDF batem com os valores do XML de origem nesse cenário real.
* A extração não depende de suposições não verificadas sobre a estrutura do schema — a tarefa só é considerada concluída após confirmação com fonte externa (XSD oficial) ou XML real, não apenas comparação de código com o upstream.

## Restrições

* **Não copiar a estrutura de nós do upstream (`uf`/`mun`/`fed`) diretamente sem validação.** O upstream pode estar implementando uma versão diferente (mais nova ou mais antiga) do schema IBS/CBS do que a que a Arquivei usa em produção. O objetivo desta tarefa é a correção validada, não a paridade com o upstream por si só.
* Se não for possível obter um XML real com dados de IBS/CBS preenchidos dentro do prazo da sprint, não fechar a tarefa como "corrigida" — documentar o achado (as duas estruturas divergentes) e escalar para confirmação, deixando o código atual inalterado até a confirmação chegar (evitar trocar um possível bug por outro possível bug sem evidência).
* Coordenar com a Tarefa 5 (supressão do bloco de Tributação Federal) caso haja sobreposição de arquivos/linhas durante o desenvolvimento simultâneo.

## Dependências e links

* Arquivo: `src/NFSe/Danfse.php` (métodos `blocoIBSCBS()` e `getIBSCBSValues()`)
* Depende de: confirmação de schema/XSD oficial vigente da NFS-e Nacional (bloco IBS/CBS), junto ao time de produto/fiscal ou à documentação do ADN
* Referência de comportamento (não normativa, pode refletir versão de schema diferente): [`nfephp-org/sped-da` — `Danfse.php`, método `drawIbsCbs()`](https://github.com/nfephp-org/sped-da/blob/master/src/NFSe/Danfse.php)
* Fixture de teste: `tests/fixtures/xml/nfse.xml` (precisa de variante com IBS/CBS não-zerado)

# Tarefa 2 — Implementar rodapé de créditos do integrador (`creditsIntegratorFooter`)

**Iniciativa:** [INT-2597 — Adequação ao PDF NFS-e Nacional](./00-iniciativa.md)
**Tipo:** Bug (funcionalidade herdada da classe base, mas sem efeito)
**Risco:** Baixo
**Estimativa sugerida:** 2–3h

## Contexto

`Danfse` estende `NFePHP\DA\Common\DaCommon`, que já implementa o método público:

```php
// src/Common/DaCommon.php
public function creditsIntegratorFooter($message = '')
{
    $this->creditos = trim($message);
}
```

Esse método é usado pelas demais classes de documento do pacote — por exemplo `src/NFe/Danfe.php`, que lê `$this->creditos` dentro do seu bloco de rodapé (linha ~3469) para imprimir a mensagem do integrador no rodapé de cada página do DANFE.

`Danfse.php`, porém, **não referencia `$this->creditos` em nenhum lugar**. Isso significa que qualquer consumidor da biblioteca (incluindo a própria API interna da Arquivei, da-api) que chame:

```php
$danfse = new Danfse($xml);
$danfse->creditsIntegratorFooter('Emitido via Sistema XYZ');
$pdf = $danfse->render();
```

verá o método aceitar a chamada silenciosamente, sem nenhum erro — mas a mensagem **nunca aparece no PDF**. É uma inconsistência de comportamento entre o DANFSe e as demais classes de documento auxiliar do mesmo pacote (Danfe, Dacte, etc.), que pode confundir quem já usa esse recurso nos outros documentos e espera o mesmo funcionamento aqui.

## O que deve ser feito

1. Adicionar um bloco de rodapé em `Danfse::monta()` (ou em um novo método privado `blocoRodape()`/`drawFooter()`, seguindo a nomenclatura já usada no arquivo — `bloco*` para blocos de conteúdo), que:
   - Só é desenhado quando `$this->creditos` não está vazio (mesmo comportamento condicional do `Danfe.php`: rodapé "opt-in" via chamada explícita de `creditsIntegratorFooter()`).
   - Imprime o texto de `$this->creditos` no rodapé da página, em fonte pequena e itálica, consistente com o estilo usado em `Danfe.php` (ver referência de layout no upstream: data/hora de impressão + mensagem do integrador à esquerda, "Powered by NFePHP®" à direita — usar apenas as partes que fizerem sentido para o padrão visual já adotado no DANFSe da Arquivei).
2. Garantir que o cálculo de alturas dos blocos (`calculaLayout()`, `ajustaAlturaTotal()`, `calculaAlturaInfoCompl()`) reserve espaço vertical para o rodapé quando ele estiver ativo, evitando sobreposição com o bloco de Informações Complementares ou com o Canhoto — hoje esses cálculos não têm nenhuma noção de "rodapé ativo".
3. Adicionar teste unitário cobrindo:
   - Render sem chamar `creditsIntegratorFooter()` → nenhum texto de rodapé no PDF, layout idêntico ao atual (sem regressão).
   - Render chamando `creditsIntegratorFooter('mensagem de teste')` → texto aparece no rodapé.

## Critérios de aceitação

* `creditsIntegratorFooter($message)` passa a ter efeito visível no PDF gerado pelo `Danfse`.
* Quando não chamado, o comportamento do PDF é idêntico ao atual (nenhuma regressão de layout para quem não usa o recurso).
* Quando chamado, o rodapé não sobrepõe nem corta o Canhoto (quando habilitado via `setCanhoto(true)`) nem o bloco de Informações Complementares.

## Restrições

* Não alterar a assinatura de `creditsIntegratorFooter()` (ela é herdada de `DaCommon` e usada por outras classes — mudar a assinatura quebraria compatibilidade).
* Não introduzir um segundo parâmetro `$powered` (presente no upstream) nesta tarefa, a menos que already exista um padrão equivalente em uso nas outras classes do fork (`Danfe.php`, `Dacte.php`) — verificar antes; se não existir, manter escopo mínimo (somente a mensagem do integrador).
* Não é necessário portar o texto "Powered by NFePHP®" do upstream — decidir com o time se cabe uma marca d'água de "powered by" própria da Arquivei ou nenhuma, já que este é um fork proprietário.

## Dependências e links

* Arquivo: `src/NFSe/Danfse.php`
* Referência já existente no mesmo pacote: `src/NFe/Danfe.php` (uso de `$this->creditos` no rodapé)
* Classe base: `src/Common/DaCommon.php` (`creditsIntegratorFooter()`)
* Referência de comportamento (não normativa): [`nfephp-org/sped-da` — `Danfse.php`, métodos `creditsIntegratorFooter()` e `drawFooter()`](https://github.com/nfephp-org/sped-da/blob/master/src/NFSe/Danfse.php)

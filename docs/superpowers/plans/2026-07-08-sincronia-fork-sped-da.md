# Sincronia do fork sped-da com o upstream — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reduzir o gap de código entre `arquivei-releases` e o upstream real (`nfephp-org/sped-da`) através de 8 PRs independentes de baixo-a-médio risco, mais 2 documentos de decisão, sem quebrar as customizações exclusivas da Arquivei.

**Architecture:** Cada Fase (A–H) é um branch próprio a partir de `arquivei-releases`, com um PR independente. Para arquivos que a Arquivei nunca customizou, o "port" é a extração do conteúdo do remote `upstream_real` (adicionado neste plano) via `git show`. Para arquivos com customização, o trabalho é merge manual linha a linha, preservando a API pública atual (usada internamente e potencialmente por consumidores externos como o `da-api`).

**Tech Stack:** PHP 7.x/8.x, PHPUnit ^5.7, Composer. Sem framework — biblioteca standalone de geração de PDF (FPDF customizado).

## Global Constraints

- Nenhuma mudança pode remover ou alterar a assinatura de um método público existente em `Danfe.php`, `Damdfe.php`, `Dacte.php`, `DacteOS.php`, `Danfse.php` sem registrar isso explicitamente como decisão (nenhuma fase deste plano faz isso — ver Fases I/J para os dois casos que precisariam).
- Toda fase que gera PDF precisa ser validada visualmente (gerar PDF antes/depois de um XML de exemplo em `tests/fixtures/xml/` ou `examples/`) além de `composer test`.
- Todo PR é contra a branch `arquivei-releases`, não contra `master`.
- O remote `upstream_real` (`https://github.com/nfephp-org/sped-da.git`) é a fonte de verdade para todo código portado — nunca copiar de `origin/master` (branch local parada em 2020).
- `DaCommon.php` e `Danfce.php` NÃO são tocados neste plano, exceto pela adição pontual e aditiva de `adjustImage()`/`getImageStringFromObject()` na Task 10 (Fase E) — qualquer outra mudança nesses dois arquivos está fora de escopo (ver Fase J).

---

## Preparação (uma vez, antes da Task 1)

- [ ] **Passo 1: Confirmar/adicionar o remote do upstream real**

Run: `git remote get-url upstream_real || git remote add upstream_real https://github.com/nfephp-org/sped-da.git`

- [ ] **Passo 2: Buscar o histórico do upstream**

Run: `git fetch upstream_real`
Expected: branches `upstream_real/master` disponível localmente.

- [ ] **Passo 3: Instalar dependências**

Run: `composer install`
Expected: `vendor/` criado, `vendor/bin/phpunit` disponível.

- [ ] **Passo 4: Confirmar que os testes atuais rodam (baseline verde antes de qualquer mudança)**

Run: `composer test`
Expected: PHPUnit executa sem erro fatal (a única suite existente, `tests/DamdfeTest.php`, tem seu único teste comentado — o resultado esperado é "OK" ou "No tests executed", não uma falha).

---

# Fase A — NT 2024.002: novos códigos de forma de pagamento

### Task 1: Atualizar `Legacy/Common.php::tipoPag()`

**Files:**
- Modify: `src/Legacy/Common.php` (método `tipoPag()`, dentro do `switch`)
- Test: `tests/Legacy/CommonTest.php` (novo arquivo)

**Interfaces:**
- Consumes: nada de outras tasks.
- Produces: `tipoPag($tPag)` retorna o rótulo correto para os códigos `'05'`, `'16'`–`'22'`. Consumido por `Danfce.php:597` e `Danfe.php:1761` (nenhuma mudança necessária nesses arquivos — eles já chamam `$this->tipoPag()`).

- [ ] **Passo 1: Escrever o teste que falha**

```php
<?php
namespace NFePHP\DA\Tests\Legacy;

use PHPUnit\Framework\TestCase;
use NFePHP\DA\Legacy\Common;

class CommonTestDouble extends Common
{
    public function publicTipoPag($tPag)
    {
        return $this->tipoPag($tPag);
    }
}

class CommonTest extends TestCase
{
    /**
     * @dataProvider provideFormasPagamentoNt2024002
     */
    public function testTipoPagRetornaRotuloCorretoParaNovosCodigos($codigo, $rotuloEsperado)
    {
        $common = new CommonTestDouble();
        $this->assertSame($rotuloEsperado, $common->publicTipoPag($codigo));
    }

    public function provideFormasPagamentoNt2024002()
    {
        return [
            'codigo 05 atualizado' => ['05', 'Cartão da Loja/Outros Crediários'],
            'codigo 16 deposito bancario' => ['16', 'Depósito Bancário'],
            'codigo 17 pix dinamico' => ['17', 'PIX Dinâmico'],
            'codigo 18 transferencia' => ['18', 'Transferência bancária, Carteira Digit.'],
            'codigo 19 fidelidade' => ['19', 'Programa de fidelidade, Cashback, Crédito Virt.'],
            'codigo 20 pix estatico' => ['20', 'PIX Estático'],
            'codigo 21 credito loja' => ['21', 'Crédito em Loja'],
            'codigo 22 sem hardware' => ['22', 'Pagamento Eletrônico não Informado - Falha de hardware'],
        ];
    }
}
```

- [ ] **Passo 2: Rodar o teste e confirmar que falha**

Run: `vendor/bin/phpunit tests/Legacy/CommonTest.php`
Expected: FAIL — para o código `'05'` o valor atual retornado é `'Crédito Loja'` (não `'Cartão da Loja/Outros Crediários'`), e para `'16'`–`'22'` o método cai no `default` e retorna `''`.

- [ ] **Passo 3: Atualizar o switch em `tipoPag()`**

Em `src/Legacy/Common.php`, dentro de `protected function tipoPag($tPag)`, substituir o case `'05'` e adicionar os novos cases antes do `case '90':`:

```php
            case '05':
                $tPagNome = 'Cartão da Loja/Outros Crediários';
                break;
            case '10':
                $tPagNome = 'Vale Alimentação';
                break;
            case '11':
                $tPagNome = 'Vale Refeição';
                break;
            case '12':
                $tPagNome = 'Vale Presente';
                break;
            case '13':
                $tPagNome = 'Vale Combustível';
                break;
            case '14':
                $tPagNome = 'Duplicata Mercantil';
                break;
            case '15':
                $tPagNome = 'Boleto Bancário';
                break;
            case '16':
                $tPagNome = 'Depósito Bancário';
                break;
            case '17':
                $tPagNome = 'PIX Dinâmico';
                break;
            case '18':
                $tPagNome = 'Transferência bancária, Carteira Digit.';
                break;
            case '19':
                $tPagNome = 'Programa de fidelidade, Cashback, Crédito Virt.';
                break;
            case '20':
                $tPagNome = 'PIX Estático';
                break;
            case '21':
                $tPagNome = 'Crédito em Loja';
                break;
            case '22':
                $tPagNome = 'Pagamento Eletrônico não Informado - Falha de hardware';
                break;
```

(Os cases `'10'` a `'15'` já existem — mantê-los como estão, só inserindo `'16'`–`'22'` depois de `'15'`.)

- [ ] **Passo 4: Rodar o teste e confirmar que passa**

Run: `vendor/bin/phpunit tests/Legacy/CommonTest.php`
Expected: OK (8 testes, 8 assertions).

- [ ] **Passo 5: Commit**

```bash
git add src/Legacy/Common.php tests/Legacy/CommonTest.php
git commit -m "feat(pagamento): adiciona códigos NT 2024.002 em Common::tipoPag()"
```

### Task 2: Atualizar array `$formaPagamento` em `Danfe.php::pagamento()`

**Files:**
- Modify: `src/NFe/Danfe.php:1950` (array `$formaPagamento` dentro de `pagamento()`)
- Test: validação manual (ver Passo 3) — este array é local a um método privado de renderização de PDF, sem acesso via API pública testável por unidade; a suíte de testes do repositório não cobre renderização de PDF (ver Estratégia de Validação da spec).

**Interfaces:**
- Consumes: nada.
- Produces: nada consumido por outra task.

- [ ] **Passo 1: Substituir o array**

Em `src/NFe/Danfe.php`, dentro de `protected function pagamento($x, $y)`, substituir a linha do array `$formaPagamento` (atualmente uma única linha com `'01'` a `'99'`) por:

```php
            $formaPagamento = [
                '01' => 'Dinheiro',
                '02' => 'Cheque',
                '03' => 'Cartão de Crédito',
                '04' => 'Cartão de Débito',
                '05' => 'Cartão da Loja/Outros Crediários',
                '10' => 'Vale Alimentação',
                '11' => 'Vale Refeição',
                '12' => 'Vale Presente',
                '13' => 'Vale Combustível',
                '14' => 'Duplicata Mercantil',
                '15' => 'Boleto',
                '16' => 'Depósito Bancário',
                '17' => 'PIX Dinâmico',
                '18' => 'Transferência bancária, Carteira Digital',
                '19' => 'Programa fidelidade, Cashback, Créd Virt',
                '20' => 'PIX Estático',
                '21' => 'Crédito em Loja',
                '22' => 'Pagamento Eletrônico não Informado - Falha de hardware',
                '90' => 'Sem pagamento',
                '99' => 'Outros',
            ];
```

- [ ] **Passo 2: Rodar `php -l` para confirmar sintaxe válida**

Run: `php -l src/NFe/Danfe.php`
Expected: `No syntax errors detected`

- [ ] **Passo 3: Validação visual manual**

Editar (ou criar a partir de um fixture existente) um XML de NF-e de exemplo em `tests/fixtures/xml/` com uma tag `<detPag><tPag>16</tPag>...` (ou qualquer código 16–22), e rodar o script de exemplo:

Run: `php examples/nfe/danfe.php` (ajustando o script para apontar para o XML de teste e usar `file_put_contents` para salvar o PDF em vez de enviar para o navegador, se necessário)

Expected: o PDF gerado mostra "Depósito Bancário" (ou o rótulo correspondente) em vez de "Forma 16 não encontrado".

- [ ] **Passo 4: Commit**

```bash
git add src/NFe/Danfe.php
git commit -m "feat(danfe): adiciona códigos de pagamento NT 2024.002 no array local de pagamento()"
```

---

# Fase B — Novos métodos pontuais no Danfe

### Task 3: Métodos aditivos — `setTitle()`, `obsContShow()`, `setExibirEmailDestinatario()`

**Files:**
- Modify: `src/NFe/Danfe.php` (adicionar 2 properties + 3 métodos públicos + 1 ponto de uso em `pdf->setTitle`)

**Interfaces:**
- Consumes: nada.
- Produces: `setTitle(string $title)`, `obsContShow(bool $flag = true)`, `setExibirEmailDestinatario(bool $exibirEmailDestinatario = true)` — API pública nova, aditiva.

- [ ] **Passo 1: Adicionar as properties**

Perto de outras properties `protected`/`public` no topo da classe `Danfe` (ex.: perto de `protected $obsshow`, se já existir; caso não exista, adicionar junto às demais flags booleanas de exibição):

```php
    protected $obsshow = true;
    protected $title = '';
    protected $exibirEmailDestinatario = true;
```

- [ ] **Passo 2: Adicionar os métodos**

Em qualquer ponto do bloco de métodos `setX()`/`setY()` já existentes:

```php
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function obsContShow($flag = true)
    {
        $this->obsshow = $flag;
    }

    public function setExibirEmailDestinatario($exibirEmailDestinatario = true)
    {
        $this->exibirEmailDestinatario = filter_var($exibirEmailDestinatario, FILTER_VALIDATE_BOOLEAN);
    }
```

- [ ] **Passo 3: Conectar `setTitle()` ao PDF**

Localizar o método `monta()` (linha ~380) e, logo após a instanciação de `$this->pdf = new Pdf(...)`, adicionar:

```php
        $this->pdf->setTitle($this->title);
```

- [ ] **Passo 4: Conectar `$obsshow`/`$exibirEmailDestinatario` ao bloco de dados adicionais**

Dentro de `public function monta(...)` (linha ~375), localizar o bloco que constrói `$this->textoAdic` com o email do destinatário e as observações do contribuinte (por volta da linha 518-538):

```php
            $this->textoAdic .= $this->getTagValue($this->dest, "email", ' Email do Destinatário: ');
            $this->textoAdic .= ! empty($this->getTagValue($this->infAdic, "infAdFisco"))
            ? "\r\n Inf. fisco: " . $this->getTagValue($this->infAdic, "infAdFisco")
            : '';
            $obsCont = $this->infAdic->getElementsByTagName("obsCont");
            if (isset($obsCont)) {
                foreach ($obsCont as $obs) {
                    $campo =  $obsCont->item($i)->getAttribute("xCampo");
                    $xTexto = ! empty($obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue)
                    ? $obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue
                    : '';
                    $this->textoAdic .= "\r\n" . $campo . ':  ' . trim($xTexto);
                    $i++;
                }
            }
```

Substituir a linha do email por (nota: como o default de `$exibirEmailDestinatario` é `false`, e o fork hoje sempre exibe o email, ajustar o default da property da Task 3 Passo 1 para `true` para preservar o comportamento atual):

```php
            if ($this->exibirEmailDestinatario) {
                $this->textoAdic .= $this->getTagValue($this->dest, "email", ' Email do Destinatário: ');
            }
```

E envolver o bloco `$obsCont`/`foreach` inteiro (as 8 linhas acima, do `$obsCont = ...` até o `}` que fecha o `if (isset($obsCont))`) com `if ($this->obsshow) { ... }`.

- [ ] **Passo 5: Validação visual manual**

Run: `php -l src/NFe/Danfe.php` (confirma sintaxe)
Gerar um PDF de exemplo com `setTitle('Teste')` chamado antes de `render()`, abrir o PDF gerado e confirmar no título do arquivo (metadados do PDF, visível na aba do navegador/leitor) que aparece "Teste".

- [ ] **Passo 6: Commit**

```bash
git add src/NFe/Danfe.php
git commit -m "feat(danfe): adiciona setTitle(), obsContShow() e setExibirEmailDestinatario()"
```

### Task 4: `epec()` — dados de EPEC para contingência (tpEmis == 4)

**Files:**
- Modify: `src/NFe/Danfe.php`

**Interfaces:**
- Consumes: nada.
- Produces: `epec(string $protocolo, string $data)` seta `$this->epec = ['protocolo' => ..., 'data' => ...]`, consumido pelos blocos de watermark de contingência do próprio arquivo.

**Contexto necessário antes de implementar:** o fork já tem um mecanismo de contingência via `$numero_registro_dpec` (setado pelo construtor) e o helper `notaDpec()`, usados nos blocos de watermark condicionados a `$this->notaDpec() || $this->tpEmis == 4` (ex.: linha ~1322). Isso é DPEC/FS (`tpEmis` 2 e 5). O `tpEmis == 4` é EPEC — regime de contingência diferente — e hoje, quando `tpEmis == 4` e `notaDpec()` é falso, o bloco de watermark já executa mas imprime uma mensagem genérica sem protocolo/data, porque não existe fonte de dados para isso. `epec()` preenche exatamente essa lacuna — é aditivo, não substitui `notaDpec()`.

- [ ] **Passo 1: Adicionar a property e o método**

Perto de `protected $numero_registro_dpec = '';`:

```php
    protected $epec = [];
```

Junto aos outros métodos `setX()`:

```php
    public function epec($protocolo, $data)
    {
        $this->epec = [
            'protocolo' => $protocolo,
            'data' => $data,
        ];
    }
```

- [ ] **Passo 2: Usar os dados nos blocos de watermark de contingência**

No bloco identificado (ao redor da linha ~1322, dentro do `if ($this->notaDpec() || $this->tpEmis == 4) { ... }`), o texto fixo atual é:

```php
            $texto = "DANFE impresso em contingência -\n".
                     "DPEC regularmente recebido pela Receita\n".
                     "Federal do Brasil";
```

Alterar para diferenciar EPEC (quando há dados em `$this->epec`) do DPEC/FS legado:

```php
            if (!empty($this->epec) && $this->tpEmis == 4) {
                $texto = "DANFE impresso em contingência -\n".
                         "EPEC regularmente recebido pela Receita\n".
                         "Federal do Brasil em " . $this->epec['data'] . "\n".
                         "Protocolo: " . $this->epec['protocolo'];
            } else {
                $texto = "DANFE impresso em contingência -\n".
                         "DPEC regularmente recebido pela Receita\n".
                         "Federal do Brasil";
            }
```

Repetir esse mesmo padrão condicional nos demais blocos de watermark de contingência do arquivo que hoje testam `$this->tpEmis == 4` sozinho ou em conjunto com `notaDpec()` — localizar com:

Run: `grep -n "tpEmis == 4\|tpEmis==4" src/NFe/Danfe.php`

- [ ] **Passo 3: Validação visual manual**

Usar um XML de exemplo com `<ide><tpEmis>4</tpEmis></ide>`, chamar `$danfe->epec('123456789012345', '2026-07-08T10:00:00-03:00')` antes de `render()`, gerar o PDF e confirmar visualmente que o protocolo e a data aparecem no watermark de contingência (e que, sem chamar `epec()`, o comportamento atual — mensagem genérica — é preservado).

- [ ] **Passo 4: Commit**

```bash
git add src/NFe/Danfe.php
git commit -m "feat(danfe): adiciona epec() para dados de contingência EPEC (tpEmis 4)"
```

### Task 5: `setGerarInformacoesAutomaticas()` — decisão de default

**Files:**
- Modify: `src/NFe/Danfe.php`

**Interfaces:**
- Consumes: nada.
- Produces: `setGerarInformacoesAutomaticas(bool $flag = true)`, property `$gerarInformacoesAutomaticas`.

**Decisão já registrada na spec:** manter o default do fork (`true`) para não quebrar consumidores atuais do `da-api` que dependem do comportamento automático hoje ligado por padrão. O upstream tem default `false` — aqui expomos o comportamento do upstream como opt-out, não opt-in.

- [ ] **Passo 1: Adicionar a property com o default do fork**

```php
    public $gerarInformacoesAutomaticas = true;
```

- [ ] **Passo 2: Adicionar o método com default `true` (preserva compatibilidade)**

```php
    public function setGerarInformacoesAutomaticas($gerarInformacoesAutomaticas = true)
    {
        $this->gerarInformacoesAutomaticas = filter_var($gerarInformacoesAutomaticas, FILTER_VALIDATE_BOOLEAN);
    }
```

- [ ] **Passo 3: Envolver a geração automática de informações de notas referenciadas / tag `<compra>` com a flag**

Localizar os pontos onde essas informações são geradas automaticamente:

Run: `grep -n "notasReferenciadas\|<compra>\|infAdic\|informacoesAutomaticas" src/NFe/Danfe.php`

Envolver o bloco de geração automática identificado com `if ($this->gerarInformacoesAutomaticas) { ... }`, preservando o comportamento atual (que já é sempre gerar, equivalente a `true`).

- [ ] **Passo 4: Validação visual manual**

Gerar um PDF sem chamar `setGerarInformacoesAutomaticas()` e confirmar que o comportamento é idêntico ao atual (informações automáticas aparecem). Gerar outro PDF chamando `setGerarInformacoesAutomaticas(false)` e confirmar que essas informações somem.

- [ ] **Passo 5: Commit**

```bash
git add src/NFe/Danfe.php
git commit -m "feat(danfe): adiciona setGerarInformacoesAutomaticas() com default compatível com o fork"
```

---

# Fase C — Port de arquivos com API pública compatível

### Task 6: Portar `Legacy/FPDF/Fpdf.php` e `Legacy/FPDF/Fpdf181.php`

**Files:**
- Modify: `src/Legacy/FPDF/Fpdf.php`, `src/Legacy/FPDF/Fpdf181.php`

**Interfaces:**
- Consumes: nada.
- Produces: nada novo — API pública 100% idêntica à atual (48/48 métodos confirmados), apenas correções internas do upstream.

**Já confirmado nesta investigação:** nem a Arquivei customizou esses dois arquivos desde o merge-base, nem a lista de métodos públicos difere do upstream. Isto é um replace seguro.

- [ ] **Passo 1: Extrair o conteúdo do upstream**

```bash
git show upstream_real/master:src/Legacy/FPDF/Fpdf.php > src/Legacy/FPDF/Fpdf.php
git show upstream_real/master:src/Legacy/FPDF/Fpdf181.php > src/Legacy/FPDF/Fpdf181.php
```

- [ ] **Passo 2: Confirmar sintaxe válida**

Run: `php -l src/Legacy/FPDF/Fpdf.php && php -l src/Legacy/FPDF/Fpdf181.php`
Expected: `No syntax errors detected` para os dois.

- [ ] **Passo 3: Confirmar que a API pública não mudou**

```bash
diff <(git show HEAD:src/Legacy/FPDF/Fpdf.php | grep -oE "function [a-zA-Z_]+" | sort -u) <(grep -oE "function [a-zA-Z_]+" src/Legacy/FPDF/Fpdf.php | sort -u)
```

Expected: nenhuma diferença (diff vazio) — nenhum método foi removido ou renomeado.

- [ ] **Passo 4: Validação visual manual**

Rodar os exemplos de cada tipo de documento (`examples/nfe/danfe.php`, `examples/nfe/danfce.php`, `examples/cte/dacte.php`, `examples/mdfe/damdfe.php`) e confirmar visualmente que os PDFs continuam idênticos ao gerado antes da mudança (essas classes usam `Pdf extends Fpdf`, então qualquer regressão em `Fpdf.php` afeta todos os documentos).

- [ ] **Passo 5: Commit**

```bash
git add src/Legacy/FPDF/Fpdf.php src/Legacy/FPDF/Fpdf181.php
git commit -m "chore(fpdf): sincroniza Fpdf.php e Fpdf181.php com o upstream (API pública inalterada)"
```

### Task 7: Portar `Legacy/Dom.php`, `Common/NfeStd.php` (novo) e `MDFe/Daevento.php` (novo)

**Files:**
- Modify: `src/Legacy/Dom.php`
- Create: `src/Common/NfeStd.php`
- Create: `src/MDFe/Daevento.php`

**Interfaces:**
- Consumes: nada.
- Produces: `NfeStd` e `MDFe\Daevento` ficam disponíveis para uso futuro (nenhuma outra fase deste plano os consome diretamente, mas ficam disponíveis para consumidores do pacote).

- [ ] **Passo 1: Extrair os três arquivos do upstream**

```bash
git show upstream_real/master:src/Legacy/Dom.php > src/Legacy/Dom.php
git show upstream_real/master:src/Common/NfeStd.php > src/Common/NfeStd.php
mkdir -p src/MDFe
git show upstream_real/master:src/MDFe/Daevento.php > src/MDFe/Daevento.php
```

- [ ] **Passo 2: Confirmar sintaxe válida**

Run: `php -l src/Legacy/Dom.php && php -l src/Common/NfeStd.php && php -l src/MDFe/Daevento.php`

- [ ] **Passo 3: Confirmar que `loadXMLFile()` (removido do upstream) não tem chamadores no fork**

Run: `git grep -n "loadXMLFile" HEAD -- src/`
Expected: nenhum resultado (o único uso era a própria definição, agora removida) — se aparecer algum resultado em outro arquivo, PARAR e reavaliar antes de prosseguir, pois isso indicaria uma chamada quebrada.

- [ ] **Passo 4: Validação**

Run: `composer test`
Rodar `examples/nfe/danfe.php`, `examples/nfe/danfce.php`, `examples/cte/dacte.php` (todos usam `Dom` internamente) e confirmar que os PDFs gerados continuam corretos.

- [ ] **Passo 5: Commit**

```bash
git add src/Legacy/Dom.php src/Common/NfeStd.php src/MDFe/Daevento.php
git commit -m "feat: adiciona NfeStd e MDFe/Daevento do upstream; sincroniza Dom.php"
```

---

# Fase D — Merge manual dos arquivos com customização leve

### Task 8: Merge `Legacy/Common.php`

**Files:**
- Modify: `src/Legacy/Common.php`
- Test: `tests/Legacy/CommonTest.php` (estende o criado na Task 1)

**Interfaces:**
- Consumes: nada.
- Produces: `toDateTime(string $input)` (novo método público), `getTagValue()` com decodificação HTML mais robusta.

**Achados desta investigação que definem o que NÃO fazer:**
- **NÃO adotar** a regex mais restritiva do upstream em `toTimestamp()` — a regex atual do fork já é um superconjunto (aceita offset `+` e `-`, faixa `00`–`12`) da versão do upstream (só `-`, faixa `01`–`05`). Trocar seria uma regressão.
- **NÃO remover** `modulo11()` — o upstream removeu esse método, mas `Dacte.php:2221` e `Danfe.php:3682` (na branch `arquivei-releases`) ainda o chamam diretamente.

- [ ] **Passo 1: Escrever o teste para `toDateTime()`**

Adicionar ao `tests/Legacy/CommonTest.php` da Task 1:

```php
    public function testToDateTimeRetornaObjetoDateTimeValido()
    {
        $common = new CommonTestDouble();
        $resultado = $common->toDateTime('2026-07-08T10:00:00-03:00');
        $this->assertInstanceOf(\DateTime::class, $resultado);
        $this->assertSame('2026-07-08', $resultado->format('Y-m-d'));
    }
```

(`toDateTime()` será público, então não precisa do wrapper `publicTipoPag`-like — chamar direto em `$common->toDateTime(...)`.)

- [ ] **Passo 2: Rodar o teste e confirmar que falha**

Run: `vendor/bin/phpunit tests/Legacy/CommonTest.php`
Expected: FAIL — `Call to undefined method ... toDateTime()`

- [ ] **Passo 3: Adicionar `toDateTime()`/`toDateTimeLegacy()`**

Adicionar depois de `toTimestamp()` em `src/Legacy/Common.php`:

```php
    /**
     * Converte data da NFe YYYY-mm-ddThh:mm:ss-03:00 para \DateTime
     *
     * @param string $input
     *
     * @return \DateTime|false
     */
    public function toDateTime($input)
    {
        if (PHP_MAJOR_VERSION > 7) {
            try {
                return new \DateTime($input);
            } catch (\Exception $e) {
                return false;
            }
        }

        return $this->toDateTimeLegacy($input);
    }

    private function toDateTimeLegacy($input)
    {
        $quantidadeColons = substr_count($input, ':');

        $format = "Y-m-d\TH:i:sP";
        if ($quantidadeColons == 2) {
            $format = "Y-m-d\TH:i:s";
        }

        try {
            return \DateTime::createFromFormat($format, $input);
        } catch (\Exception $e) {
            return false;
        }
    }
```

- [ ] **Passo 4: Atualizar `html_entity_decode()` em `getTagValue()`**

Em `getTagValue()`, trocar:

```php
                $value = html_entity_decode($value);
```

por:

```php
                $value = html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
```

- [ ] **Passo 5: Rodar os testes e confirmar que passam**

Run: `vendor/bin/phpunit tests/Legacy/CommonTest.php`
Expected: OK (9 testes: os 8 da Task 1 + este novo).

- [ ] **Passo 6: Confirmar que `modulo11()` continua presente**

Run: `grep -n "function modulo11" src/Legacy/Common.php`
Expected: 1 resultado — o método não foi removido.

- [ ] **Passo 7: Validação visual manual**

Rodar `examples/cte/dacte.php` e `examples/nfe/danfe.php` (ambos chamam `modulo11()` indiretamente) e confirmar que a chave de acesso é gerada corretamente nos PDFs.

- [ ] **Passo 8: Commit**

```bash
git add src/Legacy/Common.php tests/Legacy/CommonTest.php
git commit -m "feat(common): adiciona toDateTime() e decodificação HTML mais robusta, preservando modulo11() e regex de toTimestamp()"
```

### Task 9: Merge `Legacy/Pdf.php`

**Files:**
- Modify: `src/Legacy/Pdf.php`

**Interfaces:**
- Consumes: nada.
- Produces: `textBox(...)` ganha o parâmetro opcional `$fill` (compatível — é o último parâmetro, com default `false`), consumido potencialmente por fases futuras que queiram preencher caixas de texto (nenhuma task deste plano usa `$fill` ainda).

**Achado desta investigação:** as mudanças do upstream aqui (troca de `utf8_decode()` — deprecated no PHP 8.2+ — por um `convertToIso()` próprio, parâmetro `$fill` em `textBox()`, defaults de array em `$setFrom`/`$setTo`, property `$angle`) não colidem com a única customização da Arquivei no arquivo (guards `isset()` na linha do `strtr()` dentro do encoding de código de barras) — são áreas diferentes do arquivo.

- [ ] **Passo 1: Adicionar a property `$angle` e os defaults de array**

Substituir:

```php
    private $setFrom;                                          // converter de
    private $setTo;                                            // converter para
```

por:

```php
    private $setFrom = ["A" => 0, "B" => 0, "C" => 0];         // converter de
    private $setTo = ["A" => 0, "B" => 0, "C" => 0];           // converter para
```

E adicionar, na mesma área de properties:

```php
    private $angle = 0;
```

- [ ] **Passo 2: Adicionar `convertToIso()` e trocar os dois usos de `utf8_decode()`**

No final da classe, antes do `}` de fechamento:

```php
    /**
     * Converte os caracteres para ISO-88591.
     *
     * @param string $text
     * @return string
     */
    private function convertToIso($text)
    {
        return mb_convert_encoding($text, 'ISO-8859-1', ['UTF-8', 'windows-1252']);
    }
```

Localizar as duas ocorrências de `$text = utf8_decode($text);` (uma em `textBox()`, outra em `textBoxRotate()` ou método equivalente de rotação de texto) e trocar por:

```php
                $text = $this->convertToIso($text);
```

- [ ] **Passo 3: Adicionar o parâmetro `$fill` em `textBox()`**

Na assinatura de `textBox()`, trocar:

```php
        $border = 1,
        $link = '',
        $force = true,
        $hmax = 0,
        $vOffSet = 0
    ) {
```

por:

```php
        $border = true,
        $link = '',
        $force = true,
        $hmax = 0,
        $vOffSet = 0,
        $fill = false
    ) {
```

E no bloco que desenha a borda, trocar:

```php
        if ($border) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'D');
        }
```

por:

```php
        if ($border && $fill) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'DF');
        } elseif ($border) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'D');
        } elseif ($fill) {
            $this->rect($x, $y, $w, $h, 'F');
        }
```

- [ ] **Passo 4: Confirmar sintaxe válida**

Run: `php -l src/Legacy/Pdf.php`
Expected: `No syntax errors detected`

- [ ] **Passo 5: Confirmar que a mudança de `$border = 1` para `$border = true` não quebra chamadores existentes**

Run: `grep -rn "->textBox(" src/ | grep -c ","`

(PHP trata `1` e `true` de forma equivalente em contexto booleano — nenhuma chamada existente passa `$border` nomeado, então a mudança de default é segura.)

- [ ] **Passo 6: Validação visual manual**

Rodar `examples/nfe/danfe.php`, `examples/nfe/danfce.php`, `examples/cte/dacte.php`, `examples/mdfe/damdfe.php`, `examples/bpe/dabpe.php` (todas usam `textBox()` extensivamente) e comparar visualmente com os PDFs gerados antes da mudança — nenhuma diferença esperada, já que `$fill` só ativa quando explicitamente `true`.

- [ ] **Passo 7: Commit**

```bash
git add src/Legacy/Pdf.php
git commit -m "feat(pdf): adiciona convertToIso() (substitui utf8_decode deprecated) e parâmetro fill em textBox()"
```

---

# Fase E — Novas classes de documento

### Task 10: Adicionar `adjustImage()`/`getImageStringFromObject()` em `DaCommon.php` (aditivo)

**Files:**
- Modify: `src/Common/DaCommon.php`

**Interfaces:**
- Consumes: nada.
- Produces: `adjustImage($logo, $turn_bw = false): string|null`, usado pelas Tasks 12 e 13 (`DanfeVarejo`, `DanfeEtiqueta`).

**Importante:** esta é a ÚNICA mudança permitida em `DaCommon.php` neste plano. Não remove, não renomeia nada existente — apenas adiciona dois métodos novos. `DaCommon.php` continua com `imagePNGtoJPG()`, `setFontType()`, `setFontSize()`, `setFontStyle()`, `setOrientationAndSize()` intactos (ver Fase J para a decisão sobre esses métodos).

- [ ] **Passo 1: Adicionar os dois métodos no final da classe, antes do `}` de fechamento**

```php
    protected function adjustImage($logo, $turn_bw = false)
    {
        if (!empty($this->logomarca)) {
            return $this->logomarca;
        }
        if (empty($logo)) {
            return null;
        }
        if (substr($logo, 0, 24) !== 'data://text/plain;base64') {
            if (is_file($logo)) {
                $logo = 'data://text/plain;base64,' . base64_encode(file_get_contents($logo));
            } else {
                return null;
            }
        }
        $logoInfo = getimagesize($logo);
        $type = $logoInfo[2];
        if ($type != '2' && $type != '3') {
            throw new \Exception('O formato da imagem não é aceitável! Somente PNG ou JPG podem ser usados.');
        }
        if ($type == '3') {
            $image = @imagecreatefrompng($logo);
            if (!$image) {
                return null;
            }
            if ($turn_bw) {
                imagefilter($image, IMG_FILTER_GRAYSCALE);
            }
            return $this->getImageStringFromObject($image);
        } elseif ($type == '2' && $turn_bw) {
            $image = imagecreatefromjpeg($logo);
            if (!$image) {
                return null;
            }
            imagefilter($image, IMG_FILTER_GRAYSCALE);
            return $this->getImageStringFromObject($image);
        }
        return $logo;
    }

    private function getImageStringFromObject($image)
    {
        ob_start();
        imagejpeg($image, null, 100);
        imagedestroy($image);
        $logo = ob_get_contents();
        ob_end_clean();
        return 'data://text/plain;base64,' . base64_encode($logo);
    }
```

**Nota:** esse método referencia `$this->logomarca`, que não existe como property em `DaCommon.php` hoje. Isso é seguro porque PHP permite que uma classe use `$this->propriedadeDinamica` sem declará-la na própria classe — as subclasses que chamam `adjustImage()` (Tasks 12 e 13) já declaram sua própria `protected $logomarca`.

- [ ] **Passo 2: Confirmar sintaxe válida**

Run: `php -l src/Common/DaCommon.php`

- [ ] **Passo 3: Confirmar que nenhum método existente foi tocado**

```bash
diff <(git show HEAD~1:src/Common/DaCommon.php | grep -oE "function [a-zA-Z_]+" | sort -u) <(grep -oE "function [a-zA-Z_]+" src/Common/DaCommon.php | sort -u)
```

Expected: a única diferença deve ser a adição de `adjustImage` e `getImageStringFromObject` — nenhum método existente removido.

- [ ] **Passo 4: Commit**

```bash
git add src/Common/DaCommon.php
git commit -m "feat(dacommon): adiciona adjustImage() (aditivo, necessário para DanfeVarejo/DanfeEtiqueta)"
```

### Task 11: Portar `NFe/DanfeSimples.php`

**Files:**
- Create: `src/NFe/DanfeSimples.php`

**Interfaces:**
- Consumes: nada (não chama `adjustImage()`).
- Produces: classe `NFePHP\DA\NFe\DanfeSimples`.

- [ ] **Passo 1: Extrair do upstream**

```bash
git show upstream_real/master:src/NFe/DanfeSimples.php > src/NFe/DanfeSimples.php
```

- [ ] **Passo 2: Confirmar sintaxe e dependências**

Run: `php -l src/NFe/DanfeSimples.php`
Run: `grep -n "^use " src/NFe/DanfeSimples.php`
Expected: usa apenas `NFePHP\DA\Legacy\Dom`, `NFePHP\DA\Legacy\Pdf`, `NFePHP\DA\Common\DaCommon` — todos já disponíveis após as Fases C e a Task 10.

- [ ] **Passo 3: Validação visual manual**

Criar um script de exemplo mínimo (`examples/nfe/danfeSimples.php`, seguindo o padrão de `examples/nfe/danfe.php`) usando um XML de NF-e de `tests/fixtures/xml/`, instanciar `DanfeSimples`, chamar `render()` e confirmar que o PDF é gerado sem erros.

- [ ] **Passo 4: Commit**

```bash
git add src/NFe/DanfeSimples.php examples/nfe/danfeSimples.php
git commit -m "feat(nfe): adiciona DanfeSimples (DANFE simplificado para varejo)"
```

### Task 12: Portar `NFe/DanfeVarejo.php`

**Files:**
- Create: `src/NFe/DanfeVarejo.php`

**Interfaces:**
- Consumes: `adjustImage()` da Task 10.
- Produces: classe `NFePHP\DA\NFe\DanfeVarejo`.

- [ ] **Passo 1: Extrair do upstream**

```bash
git show upstream_real/master:src/NFe/DanfeVarejo.php > src/NFe/DanfeVarejo.php
```

- [ ] **Passo 2: Confirmar sintaxe**

Run: `php -l src/NFe/DanfeVarejo.php`

- [ ] **Passo 3: Confirmar que `adjustImage()` está disponível (Task 10 já aplicada)**

Run: `grep -n "function adjustImage" src/Common/DaCommon.php`
Expected: 1 resultado.

- [ ] **Passo 4: Validação visual manual**

Criar `examples/nfe/danfeVarejo.php` seguindo o padrão existente, testar com e sem logo (PNG e JPG) para exercitar `adjustImage()`.

- [ ] **Passo 5: Commit**

```bash
git add src/NFe/DanfeVarejo.php examples/nfe/danfeVarejo.php
git commit -m "feat(nfe): adiciona DanfeVarejo"
```

### Task 13: Portar `NFe/DanfeEtiqueta.php`

**Files:**
- Create: `src/NFe/DanfeEtiqueta.php`

**Interfaces:**
- Consumes: `adjustImage()` da Task 10.
- Produces: classe `NFePHP\DA\NFe\DanfeEtiqueta`.

- [ ] **Passo 1: Extrair do upstream**

```bash
git show upstream_real/master:src/NFe/DanfeEtiqueta.php > src/NFe/DanfeEtiqueta.php
```

- [ ] **Passo 2: Confirmar sintaxe**

Run: `php -l src/NFe/DanfeEtiqueta.php`

- [ ] **Passo 3: Validação visual manual**

Criar `examples/nfe/danfeEtiqueta.php`, testar com e sem logo.

- [ ] **Passo 4: Commit**

```bash
git add src/NFe/DanfeEtiqueta.php examples/nfe/danfeEtiqueta.php
git commit -m "feat(nfe): adiciona DanfeEtiqueta"
```

---

# Fase F — MDFe: CIOT e percursos

### Task 14: Imprimir CIOT (múltiplos) e percursos no `Damdfe.php`

**Files:**
- Modify: `src/MDFe/Damdfe.php`

**Interfaces:**
- Consumes: nada.
- Produces: nada consumido por outra task.

**Contexto confirmado nesta investigação:** o fork já lê o CIOT do XML (`$this->ciot`, propriedade única, primeiro nó apenas) mas nunca o imprime no PDF. Percursos (`infPercurso`) não são lidos nem impressos. O upstream trata CIOT como array (`$this->infCIOT`, pode haver múltiplos) e imprime junto com percursos numa seção "Percursos"/"CIOT" perto do rodapé do documento.

- [ ] **Passo 1: Trocar a leitura de CIOT de valor único para array, e ler percursos**

Localizar o bloco atual (por volta da linha 128-130 de `src/MDFe/Damdfe.php`):

```php
            $this->ciot = "";
            if ($this->dom->getElementsByTagName('CIOT')->item(0) != "") {
                $this->ciot = $this->dom->getElementsByTagName('CIOT')->item(0)->nodeValue;
            }
```

Substituir por:

```php
            $this->infCIOT = [];
            if ($this->dom->getElementsByTagName('infCIOT')->item(0) != "") {
                $this->infCIOT = $this->dom->getElementsByTagName('infCIOT');
            }
            $this->infPercurso = $this->dom->getElementsByTagName('infPercurso');
```

E declarar as duas novas properties junto de onde `$this->ciot` era declarado:

```php
    protected $infCIOT;
    protected $infPercurso;
```

(Manter `protected $ciot` e a leitura antiga só se algum outro trecho do arquivo ainda usar `$this->ciot` — verificar com `grep -n "this->ciot" src/MDFe/Damdfe.php` antes de remover.)

- [ ] **Passo 2: Adicionar a impressão de percursos e CIOT**

No método de renderização onde os dados do rodapé/informações adicionais do MDFe são desenhados (localizar com `grep -n "function monta\|dadosAdicionais\|rodape" src/MDFe/Damdfe.php` e escolher o método apropriado, tipicamente o que desenha a última seção antes do canhoto), adicionar:

```php
        $temPercursos = ($this->infPercurso->length > 0);
        if ($temPercursos) {
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $texto = 'Percursos';
            $this->pdf->textBox($x, $y, $wp, 4, $texto, $aFont, 'T', 'L', 1, '');
            $percursos = [];
            foreach ($this->infPercurso as $per) {
                $percursos[] = $per->nodeValue;
            }
            $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
            $this->pdf->textBox($x1, $y + 0.5, $wp - 1, 4, implode(', ', $percursos), $aFont, 'T', 'L', 0, '', false);
        }

        if ($this->infCIOT->length > 0) {
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $this->pdf->textBox($x, $y + 8.5, $x2, 4, 'CIOT', $aFont, 'T', 'L', 0, '', false);
            $ciots = [];
            foreach ($this->infCIOT as $ciot) {
                $ciots[] = $ciot->getElementsByTagName('CIOT')->item(0)->nodeValue;
            }
            $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
            $this->pdf->textBox($x, $y + 11.5, $maxW / 2, 4, implode(', ', $ciots), $aFont, 'T', 'L', 0, '');
        }
```

Ajustar `$x`, `$y`, `$x1`, `$x2`, `$wp`, `$maxW` para as variáveis de posicionamento já usadas no método escolhido (essas vêm do upstream original e assumem o layout dele — no fork, usar as coordenadas do bloco de rodapé/observações já existente, adaptando conforme necessário para não sobrepor outros elementos).

- [ ] **Passo 3: Confirmar sintaxe válida**

Run: `php -l src/MDFe/Damdfe.php`

- [ ] **Passo 4: Validação visual manual**

Usar (ou criar) um XML de MDF-e de exemplo com `<infCIOT><CIOT>12345678901</CIOT></infCIOT>` e `<infPercurso><UFPer>SP</UFPer></infPercurso>`, gerar o PDF via `examples/mdfe/damdfe.php` e confirmar visualmente que CIOT e percursos aparecem, sem sobrepor outros elementos do layout. Gerar também um MDF-e sem essas tags e confirmar que a seção não aparece (sem espaço em branco extra).

- [ ] **Passo 5: Commit**

```bash
git add src/MDFe/Damdfe.php
git commit -m "feat(mdfe): imprime CIOT (múltiplos) e percursos no Damdfe"
```

---

# Fase G — DanfSe v2: paridade de funcionalidades

### Task 15: Comparar e portar funcionalidades faltantes para a `Danfse.php` própria da Arquivei

**Files:**
- Modify: `src/NFSe/Danfse.php`

**Interfaces:**
- Consumes: nada.
- Produces: nada consumido por outra task.

**Contexto confirmado nesta investigação:** as duas implementações (`arquivei-releases` e `upstream_real`) são reescritas independentes, com nomenclatura e arquitetura totalmente diferentes (`blocoX()` na Arquivei vs. `drawX()` no upstream) — **não é um merge de linhas**, é uma comparação de paridade de funcionalidades. Isto não é uma tarefa mecânica; os passos abaixo são o roteiro de investigação e decisão, não um diff pronto.

- [ ] **Passo 1: Listar os métodos de cada implementação lado a lado**

```bash
git show upstream_real/master:src/NFSe/Danfse.php | grep -n "function " > /tmp/upstream_danfse_methods.txt
git show HEAD:src/NFSe/Danfse.php | grep -n "function " > /tmp/fork_danfse_methods.txt
```

Ler os dois arquivos lado a lado (`upstream_real/master:src/NFSe/Danfse.php` e o arquivo atual do fork) e montar uma tabela: para cada bloco visual (cabeçalho, identificação, QR code, prestador, tomador, destinatário, serviço, ISSQN, tributos federais, IBS/CBS, totais, informações complementares, canhoto, rodapé, marca d'água), confirmar se a implementação da Arquivei já cobre o que o upstream faz.

- [ ] **Passo 2: Para cada funcionalidade do upstream ausente na Arquivei, adicionar como método novo seguindo a convenção `blocoX()` já usada no arquivo**

Exemplo de convenção a seguir (baseado nos métodos já existentes como `blocoServico`, `blocoISSQN`): todo bloco recebe/retorna a posição vertical (`float $y`) e usa os helpers privados já existentes (`drawField`, `drawBoxedField`, `firstValue`, `value`, etc.) em vez de reimplementar a leitura de XML.

- [ ] **Passo 3: Para cada funcionalidade da Arquivei que o upstream não tem (ex.: lookup de municípios IBGE via `lookupMunicipio()`, formatação de `dCompet` em `formatDatetime()`), preservar sem alteração**

Confirmar explicitamente que `lookupMunicipio()` e `formatDatetime()` continuam presentes e não foram removidos por engano durante a Task.

- [ ] **Passo 4: Rodar `composer test` e validação visual**

Run: `composer test`
Gerar PDFs de pelo menos 3 XMLs de NFS-e diferentes (variando prestador/tomador, com e sem intermediário, com e sem valores de IBS/CBS) e comparar visualmente antes/depois desta task.

- [ ] **Passo 5: Commit**

```bash
git add src/NFSe/Danfse.php
git commit -m "feat(nfse): porta funcionalidades do DanfSe v2 do upstream mantendo a arquitetura própria"
```

---

# Fase H — Revisão do Dacte/DacteOS

### Task 16: Revisão bloco a bloco do `Dacte.php`/`DacteOS.php` contra o upstream

**Files:**
- Modify: `src/CTe/Dacte.php`, `src/CTe/DacteOS.php`

**Interfaces:**
- Consumes: nada.
- Produces: nada consumido por outra task.

**Contexto:** maior superfície de customização própria da Arquivei entre todas as fases (223 e 49 linhas próprias desde o merge-base, respectivamente) — incluindo o modal aquaviário completo e personalizações ISSQN mencionadas na issue original. Esta task é deliberadamente uma revisão manual guiada, não um script.

- [ ] **Passo 1: Gerar o diff completo do upstream para revisão**

```bash
MB=$(git merge-base upstream_real/master HEAD)
git diff $MB upstream_real/master -- src/CTe/Dacte.php > /tmp/dacte-upstream.diff
git diff $MB upstream_real/master -- src/CTe/DacteOS.php > /tmp/dacteos-upstream.diff
git diff $MB HEAD -- src/CTe/Dacte.php > /tmp/dacte-fork.diff
git diff $MB HEAD -- src/CTe/DacteOS.php > /tmp/dacteos-fork.diff
```

- [ ] **Passo 2: Categorizar cada hunk do diff do upstream em 3 grupos**

Ler `/tmp/dacte-upstream.diff` hunk a hunk e classificar cada um:
1. **Correção de bug isolada** (não toca linhas que aparecem em `/tmp/dacte-fork.diff`) — aplicar diretamente.
2. **Melhoria de layout isolada** (idem) — aplicar diretamente, validando visualmente depois.
3. **Toca região que a Arquivei também modificou** (linhas presentes em ambos os diffs, ou dentro do bloco de modal aquaviário/ISSQN) — não aplicar automaticamente; decidir manualmente linha a linha, preservando a customização da Arquivei como prioridade, incorporando a mudança do upstream só onde não conflita semanticamente.

- [ ] **Passo 3: Aplicar os hunks do grupo 1 e 2**

Para cada hunk classificado como 1 ou 2, editar `src/CTe/Dacte.php` (ou `DacteOS.php`) manualmente com o conteúdo do lado "upstream" do hunk correspondente.

- [ ] **Passo 4: Para os hunks do grupo 3, documentar a decisão tomada**

Para cada conflito real, adicionar um comentário inline no código (só quando a razão não for óbvia pela leitura) explicando por que a versão da Arquivei foi mantida, ou como foi mesclada com a mudança do upstream.

- [ ] **Passo 5: Confirmar sintaxe válida**

Run: `php -l src/CTe/Dacte.php && php -l src/CTe/DacteOS.php`

- [ ] **Passo 6: Validação visual manual**

Gerar PDFs de CT-e cobrindo: modal rodoviário simples, modal aquaviário (múltiplos containers), CT-e com Carta de Correção, CT-e em contingência DPEC (bug conhecido do fork atual: `cteDPEC()` inexistente causa fatal error — confirmar se essa correção do upstream foi incorporada nesta task ou se precisa de uma task própria). Comparar visualmente com os PDFs gerados antes da mudança.

- [ ] **Passo 7: Commit**

```bash
git add src/CTe/Dacte.php src/CTe/DacteOS.php
git commit -m "fix(cte): incorpora correções e melhorias do upstream em Dacte/DacteOS preservando customizações da Arquivei"
```

---

# Fase I — Documento de decisão: Traits no Danfe

### Task 17: Escrever o documento de decisão

**Files:**
- Create: `docs/superpowers/decisions/2026-07-08-traits-danfe.md` (renomear com a data real de execução da task, se diferente da data deste plano)

- [ ] **Passo 1: Escrever o documento cobrindo, no mínimo:**
  - Recomendação herdada do INT-2581: não executar sem driver de negócio explícito.
  - Lista dos 19 métodos/propriedades exclusivos do fork que precisariam ser redistribuídos entre os 14 Traits do upstream (`TraitBlocoI`–`TraitBlocoX`, `TraitHeaderNfe`, `TraitItensNfe`, `TraitWaterMark`, etc. — usar `git diff` entre `arquivei-releases` e o merge-base com `upstream_real/master` em `src/NFe/Danfe.php` para extrair a lista atual no momento da execução, já que o código muda entre a escrita deste plano e sua execução).
  - Os dois conflitos semânticos de API: `gerarInformacoesAutomaticas` (default) e controle de unidade tributável (`setOcultarUnidadeTributavel` vs. `setMostrarUnidadeTributavel`) — como a Task 5 deste plano já resolveu o primeiro caso (mantendo default `true` do fork), referenciar essa decisão como precedente.
  - Estimativa de esforço (2–3 semanas) e uma recomendação clara: executar ou não, e sob qual condição.

- [ ] **Passo 2: Commit**

```bash
git add docs/superpowers/decisions/
git commit -m "docs: adiciona documento de decisão sobre refatoração em Traits do Danfe"
```

---

# Fase J — Documento de decisão: `DaCommon.php` e `Danfce.php`

### Task 18: Escrever o documento de decisão

**Files:**
- Create: `docs/superpowers/decisions/2026-07-08-dacommon-danfce.md` (renomear com a data real de execução da task, se diferente da data deste plano)

- [ ] **Passo 1: Escrever o documento cobrindo, no mínimo:**
  - Os métodos de `DaCommon.php` que o upstream removeu/renomeou e que são API pública atual: `imagePNGtoJPG()` (chamado internamente por `Danfe.php:982` e `NFe/Daevento.php:272`, cada um com cópia própria privada — então o método da própria `DaCommon` está de fato não utilizado internamente, mas é público), `setFontType()`, `setFontSize()`, `setFontStyle()`, `setOrientationAndSize()` (sem chamadores internos encontrados nesta investigação — candidatos a uso exclusivo por consumidores externos como o `da-api`).
  - A reescrita completa de `Danfce.php`: `paperWidth()` → `setPaperWidth()`, remoção/mudança de `monta()`, `debugMode()`, `creditsIntegratorFooter()`, mudança de assinatura do construtor.
  - Uma recomendação explícita de próximo passo: levantamento de uso real desses métodos no `da-api` e em outros consumidores internos da Arquivei, ANTES de decidir entre (a) wrapper de compatibilidade, (b) aceitar quebra de API com aviso de depreciação, ou (c) não portar essas mudanças do upstream.

- [ ] **Passo 2: Commit**

```bash
git add docs/superpowers/decisions/
git commit -m "docs: adiciona documento de decisão sobre DaCommon.php e Danfce.php"
```

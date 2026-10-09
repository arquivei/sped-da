<?php

namespace NFePHP\DA\NFe;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Le do XML da NF-e os valores da Reforma Tributaria (IBS, CBS e IS) impressos
 * no DANFE, ja formatados, conforme a NT 2026.010.
 *
 * Todo acesso e por caminho relativo ao grupo: pAliqEfet existe em gIBSUF,
 * gIBSMun e gCBS, e vBC existe em ICMS e em gIBSCBS.
 */
final class ReformaTributaria
{
    private const CRT = [
        '1' => 'SIMPLES NACIONAL',
        '2' => 'SIMPLES NACIONAL - EXCESSO DE SUBLIMITE',
        '3' => 'REGIME NORMAL',
        '4' => 'SIMPLES NACIONAL - MEI',
    ];

    private const MONOFASICOS = ['vIBSMono', 'vCBSMono', 'vIBSMonoReten', 'vCBSMonoReten'];

    /** @var DOMXPath */
    private $xpath;
    /** @var DOMNode|null */
    private $ibscbsTot;
    /** @var DOMNode|null */
    private $isTot;
    /** @var DOMNode|null */
    private $emit;

    public function __construct(DOMDocument $dom)
    {
        $this->xpath = new DOMXPath($dom);
        $infNFe = $this->first('//*[local-name()="infNFe"]');
        $total = $this->node('total', $infNFe);
        $this->ibscbsTot = $this->node('IBSCBSTot', $total);
        $this->isTot = $this->node('ISTot', $total);
        $this->emit = $this->node('emit', $infNFe);
    }

    public function possuiGrupos(): bool
    {
        return $this->ibscbsTot !== null || $this->isTot !== null;
    }

    public function totais(): array
    {
        $totais = [
            'vCBS' => $this->money($this->value('gCBS/vCBS', $this->ibscbsTot)),
            'vIBSUF' => $this->money($this->value('gIBS/gIBSUF/vIBSUF', $this->ibscbsTot)),
            'vIBSMun' => $this->money($this->value('gIBS/gIBSMun/vIBSMun', $this->ibscbsTot)),
            'vIS' => $this->money($this->value('vIS', $this->isTot)),
        ];
        foreach (self::MONOFASICOS as $tag) {
            $totais[$tag] = $this->money($this->value('gMono/' . $tag, $this->ibscbsTot));
        }
        return $totais;
    }

    public function exibeMonofasicos(): bool
    {
        foreach (self::MONOFASICOS as $tag) {
            if ((float) $this->value('gMono/' . $tag, $this->ibscbsTot) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tributos do item para o quadro de produtos (NT 2026.010 4.3), ja formatados.
     * Cada tributo e null quando o grupo nao existe no XML; cada campo e null quando a tag nao existe.
     */
    public function tributosItem(DOMElement $det): array
    {
        $imposto = $this->node('imposto', $det);
        $icms = $this->primeiroFilho($this->node('ICMS', $imposto));
        $ibscbs = $this->node('IBSCBS', $imposto);
        $gIBSCBS = $this->node('gIBSCBS', $ibscbs);
        return [
            'cClassTrib' => $this->value('cClassTrib', $ibscbs),
            'situacaoIcms' => $this->situacaoIcms($icms),
            'ICMS' => $this->tributo($icms, 'vBC', 'pICMS', 'vICMS'),
            'IBS/CBS' => $this->tributo($gIBSCBS, 'vBC', null, null),
            'CBS' => $this->tributoIbsCbs($this->node('gCBS', $gIBSCBS), 'pCBS', 'vCBS'),
            'IBS UF' => $this->tributoIbsCbs($this->node('gIBSUF', $gIBSCBS), 'pIBSUF', 'vIBSUF'),
            'IBS MUN' => $this->tributoIbsCbs($this->node('gIBSMun', $gIBSCBS), 'pIBSMun', 'vIBSMun'),
            'IS' => $this->tributo($this->node('IS', $imposto), 'vBCIS', 'pIS', 'vIS'),
            'IPI' => $this->tributo($this->node('IPI/IPITrib', $imposto), 'vBC', 'pIPI', 'vIPI'),
        ];
    }

    public function descricaoCrt(): string
    {
        $crt = $this->value('CRT', $this->emit);
        if ($crt === null) {
            return '';
        }
        return isset(self::CRT[$crt]) ? $crt . ' - ' . self::CRT[$crt] : $crt;
    }

    /** NT 2026.010 4.3: com gRed no grupo do tributo, vale a aliquota efetiva desse grupo. */
    private function aliquota(DOMNode $tributo, string $tagAliquota): ?string
    {
        $efetiva = $this->value('gRed/pAliqEfet', $tributo);
        return $efetiva !== null ? $efetiva : $this->value($tagAliquota, $tributo);
    }

    private function money(?string $valor): string
    {
        return number_format((float) $valor, 2, ',', '.');
    }

    /** 2 a 4 casas decimais, sem zeros a direita alem da segunda: 0.1000 -> 0,10%; 0.0350 -> 0,035%. */
    private function percent(?string $valor): string
    {
        list($inteiro, $decimais) = explode(',', number_format((float) $valor, 4, ',', '.'));
        return $inteiro . ',' . str_pad(rtrim($decimais, '0'), 2, '0') . '%';
    }

    private function value(string $caminho, ?DOMNode $contexto): ?string
    {
        $node = $this->node($caminho, $contexto);
        return $node === null ? null : trim($node->nodeValue);
    }

    private function node(string $caminho, ?DOMNode $contexto): ?DOMNode
    {
        if ($contexto === null) {
            return null;
        }
        $passos = array_map(function ($tag) {
            return '*[local-name()="' . $tag . '"]';
        }, explode('/', $caminho));
        return $this->first(implode('/', $passos), $contexto);
    }

    private function first(string $expressao, ?DOMNode $contexto = null): ?DOMNode
    {
        $lista = $this->xpath->query($expressao, $contexto);
        return ($lista !== false && $lista->length > 0) ? $lista->item(0) : null;
    }

    /** CST (regime normal) ou CSOSN (Simples), precedido da origem, como na coluna O/CST do DANFE. */
    private function situacaoIcms(?DOMNode $icms): ?array
    {
        if ($icms === null) {
            return null;
        }
        $origem = (string) $this->value('orig', $icms);
        $cst = $this->value('CST', $icms);
        if ($cst !== null) {
            return ['CST', $origem . $cst];
        }
        $csosn = $this->value('CSOSN', $icms);
        return $csosn === null ? null : ['CSOSN', $origem . $csosn];
    }

    private function tributo(?DOMNode $grupo, ?string $base, ?string $aliquota, ?string $valor): ?array
    {
        if ($grupo === null) {
            return null;
        }
        return [
            'base' => $base === null ? null : $this->dinheiroOuNulo($this->value($base, $grupo)),
            'aliquota' => $aliquota === null ? null : $this->percentualOuNulo($this->value($aliquota, $grupo)),
            'valor' => $valor === null ? null : $this->dinheiroOuNulo($this->value($valor, $grupo)),
        ];
    }

    /** IBS UF, IBS Mun e CBS: aliquota efetiva do proprio grupo quando houver gRed (NT 2026.010 4.3). */
    private function tributoIbsCbs(?DOMNode $grupo, string $tagAliquota, string $tagValor): ?array
    {
        if ($grupo === null) {
            return null;
        }
        return [
            'base' => null,
            'aliquota' => $this->percentualOuNulo($this->aliquota($grupo, $tagAliquota)),
            'valor' => $this->dinheiroOuNulo($this->value($tagValor, $grupo)),
        ];
    }

    private function dinheiroOuNulo(?string $valor): ?string
    {
        return $valor === null ? null : $this->money($valor);
    }

    private function percentualOuNulo(?string $valor): ?string
    {
        return $valor === null ? null : $this->percent($valor);
    }

    private function primeiroFilho(?DOMNode $grupo): ?DOMNode
    {
        return $grupo === null ? null : $this->first('*[1]', $grupo);
    }
}

<?php

namespace NFePHP\DA\NFSe;

use NFePHP\DA\Common\DaCommon;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use Com\Tecnick\Barcode\Barcode;

class Danfse extends DaCommon
{
    // Constantes de coordenadas (mm, conforme especificação NT-008 seção 2.4.5)
    private const X_L   =   2.0;
    private const X_C2  =  53.5;
    private const X_C3  = 105.0;
    private const X_C4  = 156.5;
    private const X_QR  = 174.0;
    private const X_QR2 = 156.5;
    private const X_DIV =   4.0;  // X_L + 2mm de margem interna
    private const W_DIV = 202.0;  // W_FULL - 4mm (2mm de cada lado)

    private const W_FULL = 206.0;
    private const W_C1   =  51.5;
    private const W_C    =  51.5;
    private const W_WIDE = 103.0;
    private const W_QR   =  15.2;

    private const H_ROW      = 6.3;
    private const H_ROW7     = 6.7;
    private const H_MIN_SUPR = 3.2;
    private const H_COD_SERV = 3.8; // linha sem label da descrição do código (NT-008 §2.4.5)

    private const F_BLOCO_TIT  = ['font' => 'arial', 'size' => 7, 'style' => 'B'];
    private const F_CAMPO_TIT  = ['font' => 'arial', 'size' => 6, 'style' => 'B'];
    private const F_CAMPO_ID   = ['font' => 'arial', 'size' => 7, 'style' => 'B'];
    private const F_CONTEUDO   = ['font' => 'arial', 'size' => 7, 'style' => ''];
    private const F_HEADER_CTR = ['font' => 'arial', 'size' => 9, 'style' => 'B'];
    private const F_HEADER_RGT = ['font' => 'arial', 'size' => 8, 'style' => ''];
    private const F_QR_COMPL   = ['font' => 'arial', 'size' => 6, 'style' => ''];
    private const F_HOMOLOG    = ['font' => 'arial', 'size' => 9, 'style' => 'B'];
    private const F_WATERMARK  = ['font' => 'arial', 'size' => 50, 'style' => ''];

    private const LBL_CNPJ_CPF_NIF    = 'CNPJ / CPF / NIF';
    private const LBL_INSC_MUN        = 'INDICADOR MUNICIPAL (INSC.)';
    private const LBL_NOME_EMPRESARIAL = 'NOME / NOME EMPRESARIAL';
    private const LBL_MUNICIPIO_UF    = 'MUNICÍPIO / SIGLA UF';
    private const LBL_CODIGO_IBGE_CEP = 'CÓDIGO IBGE / CEP';
    private const LBL_ENDERECO        = 'Endereço';

    private Dom $dom;
    private \DOMElement $infNFSe;
    private ?\DOMElement $infDPS      = null;
    private ?\DOMElement $emit        = null;
    private ?\DOMElement $prest       = null;
    private ?\DOMElement $toma        = null;
    private ?\DOMElement $dest        = null;
    private ?\DOMElement $interm      = null;
    private ?\DOMElement $serv        = null;
    private ?\DOMElement $valores     = null;
    private ?\DOMElement $ibscbs      = null;
    private ?\DOMElement $totCIBS     = null;

    private bool $hasTomador       = false;
    private bool $hasDestinatario  = false;
    private bool $destEhTomador    = false;
    private bool $hasIntermediario = false;
    private bool $hasISSQN         = false;
    private bool $hasTribFederal   = false;
    private bool $showCanhoto      = false;

    private float $hCabecalho     = 11.6;
    private float $hDadosNfse     = 28.4;
    private float $hPrestador     = 25.8;
    private float $hTomador       = 19.4;
    private float $hDestinatario  = 19.4;
    private float $hIntermediario = 19.4;
    private float $hServico       = 16.4; // H_ROW + H_COD_SERV + H_ROW (mínimo)
    private float $hISSQN         = 25.9;
    private float $hTribFederal   = 13.0;
    private float $hIBSCBS        = 25.8;
    private float $hValorTotal    = 13.7;
    private float $hInfoCompl     = 58.3;
    private float $hCanhoto       =  6.7;

    public function __construct(string $xml)
    {
        $this->orientacao = 'P';
        $this->papel      = 'A4';
        $this->margsup    = 2.0;
        $this->margesq    = 2.0;
        $this->marginf    = 2.0;
        $this->maxW       = 210.0;
        $this->maxH       = 297.0;
        $this->wPrint     = $this->maxW - $this->margesq * 2;
        $this->hPrint     = $this->maxH - $this->margsup - $this->marginf;

        $this->dom = new Dom();
        $this->dom->loadXML($xml);

        $this->infNFSe = $this->dom->getElementsByTagName('infNFSe')->item(0);
        if (empty($this->infNFSe)) {
            throw new \InvalidArgumentException('XML inválido: tag infNFSe não encontrada.');
        }

        $this->emit    = $this->infNFSe->getElementsByTagName('emit')->item(0);

        $dps           = $this->dom->getElementsByTagName('DPS')->item(0);
        $this->infDPS  = $dps ? $dps->getElementsByTagName('infDPS')->item(0) : null;
        $this->prest   = $this->infDPS ? $this->infDPS->getElementsByTagName('prest')->item(0)   : null;
        $this->toma    = $this->infDPS ? $this->infDPS->getElementsByTagName('toma')->item(0)    : null;
        $this->interm  = $this->infDPS ? $this->infDPS->getElementsByTagName('interm')->item(0)  : null;
        $this->serv    = $this->infDPS ? $this->infDPS->getElementsByTagName('serv')->item(0)    : null;
        $this->valores = $this->infDPS ? $this->infDPS->getElementsByTagName('valores')->item(0) : null;

        $ibscbsEl     = $this->infDPS ? $this->infDPS->getElementsByTagName('IBSCBS')->item(0) : null;
        $this->ibscbs = $ibscbsEl;
        $this->dest   = $ibscbsEl ? $ibscbsEl->getElementsByTagName('dest')->item(0) : null;
        $this->totCIBS = $this->infNFSe->getElementsByTagName('totCIBS')->item(0);
    }

    public function setCanhoto(bool $show = true): void
    {
        $this->showCanhoto = $show;
    }

    protected function monta(): void
    {
        $this->calculaLayout();

        $this->pdf = new Pdf('P', 'mm', 'A4');
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->setMargins($this->margesq, $this->margsup);
        $this->pdf->addPage();
        $this->calculaAlturaServico();
        $this->calculaAlturaInfoCompl();
        $this->ajustaAlturaTotal();

        $totalDocH = $this->hCabecalho + $this->hDadosNfse + $this->hPrestador
            + $this->hTomador + $this->hDestinatario + $this->hIntermediario
            + $this->hServico + $this->hISSQN + $this->hTribFederal
            + $this->hIBSCBS + $this->hValorTotal + $this->hInfoCompl + $this->hCanhoto;
        $this->pdf->SetLineWidth(0.18);

        $y = (float) $this->margsup;
        $y = $this->blocoCabecalho($y);
        $y = $this->blocoDadosNfse($y);
        $y = $this->blocoPrestador($y);
        $y = $this->blocoTomador($y);
        $y = $this->blocoDestinatario($y);
        $y = $this->blocoIntermediario($y);
        $y = $this->blocoServico($y);
        $y = $this->blocoISSQN($y);
        if ($this->hTribFederal > 0) {
            $y = $this->blocoTribFederal($y);
        }
        $y = $this->blocoIBSCBS($y);
        $y = $this->blocoValorTotal($y);
        $y = $this->blocoInfoCompl($y);
        if ($this->hCanhoto > 0) {
            $this->blocoCanhoto($y);
        }

        $this->watermark();

        // Borda externa desenhada por último para não ser coberta pelos fills
        $this->pdf->SetLineWidth(0.35);
        $this->pdf->Rect(self::X_L, $this->margsup, self::W_FULL, $totalDocH, 'D');
        $this->pdf->SetLineWidth(0.18);
    }

    private function calculaLayout(): void
    {
        $this->hasTomador       = !empty($this->toma);
        $this->hasDestinatario  = !empty($this->dest);
        $this->hasIntermediario = !empty($this->interm);

        if ($this->hasDestinatario && $this->hasTomador) {
            $cnpjDest = $this->getTagValue($this->dest, 'CNPJ')
                     ?: $this->getTagValue($this->dest, 'CPF');
            $cnpjToma = $this->getTagValue($this->toma, 'CNPJ')
                     ?: $this->getTagValue($this->toma, 'CPF');
            $this->destEhTomador = (!empty($cnpjDest) && $cnpjDest === $cnpjToma);
        }

        $tribMun        = $this->valores
            ? $this->valores->getElementsByTagName('tribMun')->item(0)
            : null;
        $tpRetISSQN     = $tribMun ? $this->getTagValue($tribMun, 'tpRetISSQN') : null;
        $this->hasISSQN = !empty($tribMun) && $tpRetISSQN !== '1';

        $dCompet = $this->getTagValue($this->infDPS, 'dCompet');
        if (!empty($dCompet)) {
            $year = (int) substr($dCompet, 0, 4);
            $this->hasTribFederal = ($year <= 2026);
        }

        $this->hInfoCompl += $this->ajustaAlturasSuprimidas();
    }

    private function ajustaAlturasSuprimidas(): float
    {
        $freed = 0.0;
        if (!$this->hasTomador) {
            $freed              += $this->hTomador - self::H_MIN_SUPR;
            $this->hTomador      = self::H_MIN_SUPR;
        }
        if (!$this->hasDestinatario || $this->destEhTomador) {
            $freed               += $this->hDestinatario - self::H_MIN_SUPR;
            $this->hDestinatario  = self::H_MIN_SUPR;
        }
        if (!$this->hasIntermediario) {
            $freed                += $this->hIntermediario - self::H_MIN_SUPR;
            $this->hIntermediario  = self::H_MIN_SUPR;
        }
        if (!$this->hasISSQN) {
            $freed        += $this->hISSQN - self::H_MIN_SUPR;
            $this->hISSQN  = self::H_MIN_SUPR;
        }
        if (!$this->hasTribFederal) {
            $freed              += $this->hTribFederal;
            $this->hTribFederal  = 0.0;
        }
        if (!$this->showCanhoto) {
            $freed         += $this->hCanhoto;
            $this->hCanhoto = 0.0;
        }
        return $freed;
    }

    private function calculaAlturaServico(): void
    {
        $s           = $this->serv;
        $rawDescServ = $s ? $this->getTagValue($s, 'xDescServ') : null;
        $xDescServ   = $rawDescServ ?: '';

        $this->pdf->SetFont(
            self::F_CONTEUDO['font'],
            self::F_CONTEUDO['style'],
            self::F_CONTEUDO['size']
        );
        $lineH     = $this->pdf->fontSize;
        $textWidth = self::W_FULL - 1.0;

        if (!empty($xDescServ)) {
            $text   = html_entity_decode(utf8_decode($xDescServ));
            $nLines = $this->pdf->wordWrap($text, $textWidth);
        } else {
            $nLines = 1;
        }

        // área do label (3.2mm) + nLinhas × lineH + padding inferior (0.4mm)
        $descH = max(self::H_ROW, $nLines * $lineH + 3.6);
        $this->hServico = self::H_ROW + self::H_COD_SERV + $descH;
    }

    private function calculaAlturaInfoCompl(): void
    {
        $ic = $this->infDPS
            ? $this->infDPS->getElementsByTagName('infoCompl')->item(0)
            : null;
        $xInfComp = $this->getTagValue($ic, 'xInfComp') ?: '';

        $this->pdf->SetFont(
            self::F_CONTEUDO['font'],
            self::F_CONTEUDO['style'],
            self::F_CONTEUDO['size']
        );
        $lineH     = $this->pdf->fontSize;
        $textWidth = self::W_FULL - 1.0;

        if (!empty($xInfComp)) {
            $text   = html_entity_decode(utf8_decode($xInfComp));
            $nLines = $this->pdf->wordWrap($text, $textWidth);
        } else {
            $nLines = 1;
        }

        $needed = self::H_ROW + ($nLines * $lineH) + 2.0;
        if ($needed > $this->hInfoCompl) {
            $this->hInfoCompl = $needed;
        }
    }

    private function ajustaAlturaTotal(): void
    {
        $outros = $this->hCabecalho + $this->hDadosNfse + $this->hPrestador
                + $this->hTomador + $this->hDestinatario + $this->hIntermediario
                + $this->hServico + $this->hISSQN + $this->hTribFederal
                + $this->hIBSCBS + $this->hValorTotal + $this->hCanhoto;

        $maxInfoCompl = $this->hPrint - $outros;
        $minInfoCompl = self::H_ROW + $this->pdf->fontSize + 2.0;

        $this->hInfoCompl = max(min($this->hInfoCompl, $maxInfoCompl), $minInfoCompl);
    }

    // ── Bloco 1: Cabeçalho ────────────────────────────────────────────────────
    private function blocoCabecalho(float $y): float
    {
        $h = $this->hCabecalho;

        $this->pdf->SetFillColor(242, 242, 242);
        $this->pdf->Rect(self::X_L, $y, self::W_FULL, $h, 'F');
        $this->pdf->SetFillColor(255, 255, 255);

        $xCenter = 44.9;
        $wCenter = 114.2;
        $tpAmb   = $this->getTagValue($this->infDPS, 'tpAmb');

        $this->pdf->textBox($xCenter, $y, $wCenter, $h / 2.0, 'DANFSe v2.0',
            self::F_HEADER_CTR, 'B', 'C', false, '');
        $this->pdf->textBox($xCenter, $y + $h / 2.0, $wCenter, $h / 2.0,
            'Documento Auxiliar da NFS-e', self::F_HEADER_CTR, 'T', 'C', false, '');

        if ($tpAmb === '2') {
            $this->pdf->SetTextColor(255, 0, 0);
            $this->pdf->textBox($xCenter, $y + $h * 0.68, $wCenter, 3.5,
                'NFS-e SEM VALIDADE JURÍDICA', self::F_HOMOLOG, 'C', 'C', false, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }

        $xRight = self::X_C4 + 1.0;
        $wRight = (self::X_L + self::W_FULL) - $xRight - 0.5;

        $xLocEmi    = $this->getTagValue($this->infNFSe, 'xLocEmi');
        $uf         = $this->getTagValue($this->infNFSe, 'UF');
        $munFallback = $xLocEmi ?: '-';
        $mun         = $xLocEmi && $uf ? "Município: {$xLocEmi} / {$uf}" : $munFallback;
        $ambGer     = $this->getTagValue($this->infNFSe, 'ambGer');
        $tpAmbLabel = ($tpAmb === '1') ? 'Produção' : 'Homologação';

        $this->pdf->textBox($xRight, $y + 0.5, $wRight, 3.8, $mun,
            self::F_HEADER_RGT, 'T', 'L', false, '');
        $this->pdf->textBox($xRight, $y + 4.8, $wRight, 2.5, 'Ambiente Gerador:',
            self::F_CAMPO_TIT, 'T', 'L', false, '');
        $this->pdf->textBox($xRight, $y + 4.8, $wRight, 2.5, (string)$ambGer,
            self::F_QR_COMPL, 'T', 'R', false, '');
        $this->pdf->textBox($xRight, $y + 7.6, $wRight, 2.5, 'Tipo de Ambiente:',
            self::F_CAMPO_TIT, 'T', 'L', false, '');
        $this->pdf->textBox($xRight, $y + 7.6, $wRight, 2.5, $tpAmbLabel,
            self::F_QR_COMPL, 'T', 'R', false, '');

        $logoPath = dirname(__DIR__, 2) . '/docs/logo-nfs-e-horizontal.png';
        $logoW    = 38.0;
        $logoH    = round($logoW * (389 / 1920), 2);
        $logoX    = self::X_L + (42.0 - $logoW) / 2;
        $logoY    = $y + ($h - $logoH) / 2;
        if (file_exists($logoPath)) {
            $src = imagecreatefrompng($logoPath);
            $flat = imagecreatetruecolor(imagesx($src), imagesy($src));
            imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $src, 0, 0, 0, 0, imagesx($src), imagesy($src));
            imagedestroy($src);
            ob_start();
            imagepng($flat);
            $pngData = ob_get_clean();
            imagedestroy($flat);
            $dataUri = 'data://text/plain;base64,' . base64_encode($pngData);
            $this->pdf->Image($dataUri, $logoX, $logoY, $logoW, $logoH, 'PNG');
        } else {
            $this->pdf->textBox(self::X_L + 1.0, $y + 2.0, 40.0, 8.0,
                'NFS-e', self::F_HEADER_CTR, 'C', 'C', false, '');
        }

        $this->pdf->Line(self::X_DIV, $y + $h, self::X_DIV + self::W_DIV, $y + $h);

        return $y + $h;
    }

    // ── Bloco 2: Dados NFS-e + QR Code ───────────────────────────────────────
    private function blocoDadosNfse(float $y): float
    {
        $h = $this->hDadosNfse;

        $chaveRaw = $this->infNFSe->getAttribute('Id');
        $chave    = (string) preg_replace('/^NFS/', '', $chaveRaw);

        $nNFSe   = $this->getTagValue($this->infNFSe, 'nNFSe') ?: '-';
        $dCompet = $this->formatDate($this->getTagValue($this->infDPS, 'dCompet'));
        $dhProc  = $this->formatDatetime($this->getTagValue($this->infNFSe, 'dhProc'));
        $nDPS    = $this->getTagValue($this->infDPS,  'nDPS') ?: '-';
        $serie   = $this->getTagValue($this->infDPS,  'serie') ?: '-';
        $dhEmi   = $this->formatDatetime($this->getTagValue($this->infDPS, 'dhEmi'));
        $tpEmit  = $this->getTpEmitLabel($this->getTagValue($this->infDPS, 'tpEmit'));
        $cStat   = $this->getCStatLabel($this->getTagValue($this->infNFSe, 'cStat'));
        $finNFSe = $this->getFinNFSeLabel($this->getTagValue($this->infDPS, 'finNFSe'));

        $qrUrl   = 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=' . $chave;
        $qrX    = self::X_QR;
        $qrY    = $y + 3.7;
        $qrSize = self::W_QR;

        // Linha 0: Chave de acesso (largura total)
        $r0h = self::H_ROW7;
        $this->drawField(self::X_L, $y, self::W_FULL, $r0h, 'CHAVE DE ACESSO DA NFS-E', $chave, false, self::F_CAMPO_ID);

        $r1y = $y + $r0h;

        // Linha 1: Número | Competência | Data/Hora (3 colunas iguais + área QR)
        $this->drawField(self::X_L,  $r1y, self::W_C1, self::H_ROW7, 'NÚMERO DA NFS-E',               $nNFSe,   false, self::F_CAMPO_ID);
        $this->drawField(self::X_C2, $r1y, self::W_C,  self::H_ROW7, 'COMPETÊNCIA DA NFS-E',           $dCompet, false, self::F_CAMPO_ID);
        $this->drawField(self::X_C3, $r1y, self::W_C,  self::H_ROW7, 'DATA E HORA DA EMISSÃO DA NFS-E', $dhProc,  false, self::F_CAMPO_ID);

        // Linha 2: Número DPS | Série | Data/Hora DPS
        $r2y = $r1y + self::H_ROW7;
        $this->drawField(self::X_L,  $r2y, self::W_C1, self::H_ROW7, 'NÚMERO DO DPS',                 $nDPS,  false, self::F_CAMPO_ID);
        $this->drawField(self::X_C2, $r2y, self::W_C,  self::H_ROW7, 'SÉRIE DA DPS',                  $serie, false, self::F_CAMPO_ID);
        $this->drawField(self::X_C3, $r2y, self::W_C,  self::H_ROW7, 'DATA E HORA DA EMISSÃO DA DPS', $dhEmi, false, self::F_CAMPO_ID);

        // Linha 3: Emitente (cinza obrigatório conforme NT-008 §2.2.3) | Situação | Finalidade
        $r3y = $r2y + self::H_ROW7;
        $this->drawField(self::X_L,  $r3y, self::W_C1, self::H_ROW7, 'EMITENTE DA NFS-E', $tpEmit, true,  self::F_CAMPO_ID);
        $this->drawField(self::X_C2, $r3y, self::W_C,  self::H_ROW7, 'SITUAÇÃO DA NFS-E', $cStat,  false, self::F_CAMPO_ID);
        $this->drawField(self::X_C3, $r3y, self::W_C,  self::H_ROW7, 'FINALIDADE',         $finNFSe, false, self::F_CAMPO_ID);

        // Área do QR Code abrange as linhas 1 a 3
        $this->drawQrCode($qrX + 0.5, $qrY, $qrSize, $qrUrl);

        $qrComplX = self::X_QR2;
        $qrComplY = $qrY + $qrSize + 0.5;
        $qrComplW = (self::X_L + self::W_FULL) - self::X_QR2 - 0.5;
        $qrComplH = ($y + $h) - $qrComplY - 0.5;
        $qrCompl  = 'A autenticidade desta NFS-e pode ser verificada pela leitura deste '
                  . 'código QR ou pela consulta da chave de acesso no portal nacional da NFS-e';
        $this->pdf->textBox($qrComplX, $qrComplY, $qrComplW, $qrComplH,
            $qrCompl, ['font' => 'arial', 'size' => 6, 'style' => ''], 'T', 'L', false, '', false, 0);

        return $y + $h;
    }

    // ── Bloco 3: Prestador/Fornecedor ─────────────────────────────────────────
    private function blocoPrestador(float $y): float
    {
        $h = $this->hPrestador;
        $p = $this->prest;
        $e = $this->emit;

        $this->drawBlocoHeader($y, self::H_ROW, 'PRESTADOR / FORNECEDOR');

        $cnpjCpfNif = $this->formatCnpjCpfNif(
            $this->getTagValue($p, 'CNPJ') ?: $this->getTagValue($e, 'CNPJ'),
            $this->getTagValue($p, 'CPF')  ?: $this->getTagValue($e, 'CPF'),
            $this->getTagValue($p, 'NIF')  ?: $this->getTagValue($e, 'NIF')
        );
        $im    = $this->getTagValue($p, 'IM')    ?: $this->getTagValue($e, 'IM');
        $fone  = $this->getTagValue($p, 'fone')  ?: $this->getTagValue($e, 'fone');
        $xNome = $this->getTagValue($p, 'xNome') ?: $this->getTagValue($e, 'xNome');
        $email = $this->getTagValue($p, 'email') ?: $this->getTagValue($e, 'email');

        $this->drawField(self::X_C2, $y, self::W_C,  self::H_ROW, self::LBL_CNPJ_CPF_NIF,    $cnpjCpfNif);
        $this->drawField(self::X_C3, $y, self::W_C,  self::H_ROW, self::LBL_INSC_MUN,        $im);
        $this->drawField(self::X_C4, $y, self::W_C,  self::H_ROW, 'TELEFONE',                 $fone);

        $r1y = $y + self::H_ROW;
        $this->drawField(self::X_L,  $r1y, self::W_WIDE, self::H_ROW, self::LBL_NOME_EMPRESARIAL, $xNome);
        $this->drawField(self::X_C3, $r1y, self::W_C,    self::H_ROW, self::LBL_MUNICIPIO_UF,    $this->getMunicipioUF($e ?? $p));
        $this->drawField(self::X_C4, $r1y, self::W_C,    self::H_ROW, self::LBL_CODIGO_IBGE_CEP, $this->getCodigoIbgeCep($e ?? $p));

        $r2y = $r1y + self::H_ROW;
        $this->drawField(self::X_L,  $r2y, self::W_WIDE, self::H_ROW, self::LBL_ENDERECO, $this->getEndereco($e ?? $p));
        $this->drawField(self::X_C3, $r2y, self::W_C,    self::H_ROW, ' ',        ' ');
        $this->drawField(self::X_C4, $r2y, self::W_C,    self::H_ROW, 'E-mail',   $email);

        $r3y     = $r2y + self::H_ROW;
        $regTrib = $p ? $p->getElementsByTagName('regTrib')->item(0) : null;
        $simpNac = $this->getOpSimpNacLabel($this->getTagValue($regTrib, 'opSimpNac'));
        $regApTrib = $this->getRegApTribSNLabel($this->getTagValue($regTrib, 'regApTribSN'));
        $this->drawField(self::X_L,  $r3y, self::W_WIDE, self::H_ROW, 'SIMPLES NACIONAL NA DATA DA COMPETÊNCIA', $simpNac);
        $this->drawField(self::X_C3, $r3y, self::W_WIDE, self::H_ROW, 'REGIME DE APURAÇÃO TRIBUTÁRIA PELO SN',   $regApTrib);

        return $y + $h;
    }

    // ── Bloco 4: Tomador/Adquirente ───────────────────────────────────────────
    private function blocoTomador(float $y): float
    {
        if (!$this->hasTomador) {
            $this->drawSuppressedBlock($y, $this->hTomador,
                'TOMADOR/ADQUIRENTE DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e');
            return $y + $this->hTomador;
        }

        $h = $this->hTomador;
        $t = $this->toma;

        $this->drawBlocoHeader($y, self::H_ROW, 'TOMADOR / ADQUIRENTE');

        $cnpjCpfNif = $this->formatCnpjCpfNif(
            $this->getTagValue($t, 'CNPJ'),
            $this->getTagValue($t, 'CPF'),
            $this->getTagValue($t, 'NIF')
        );

        $this->drawField(self::X_C2, $y, self::W_C, self::H_ROW, self::LBL_CNPJ_CPF_NIF,    $cnpjCpfNif);
        $this->drawField(self::X_C3, $y, self::W_C, self::H_ROW, self::LBL_INSC_MUN,        $this->getTagValue($t, 'IM'));
        $this->drawField(self::X_C4, $y, self::W_C, self::H_ROW, 'TELEFONE',                 $this->getTagValue($t, 'fone'));

        $r1y = $y + self::H_ROW;
        $this->drawField(self::X_L,  $r1y, self::W_WIDE, self::H_ROW, self::LBL_NOME_EMPRESARIAL, $this->getTagValue($t, 'xNome'));
        $this->drawField(self::X_C3, $r1y, self::W_C,    self::H_ROW, self::LBL_MUNICIPIO_UF,    $this->getMunicipioUF($t));
        $this->drawField(self::X_C4, $r1y, self::W_C,    self::H_ROW, self::LBL_CODIGO_IBGE_CEP, $this->getCodigoIbgeCep($t));

        $r2y = $r1y + self::H_ROW;
        $this->drawField(self::X_L,  $r2y, self::W_WIDE, self::H_ROW, self::LBL_ENDERECO, $this->getEndereco($t));
        $this->drawField(self::X_C3, $r2y, self::W_C,    self::H_ROW, ' ',        ' ');
        $this->drawField(self::X_C4, $r2y, self::W_C,    self::H_ROW, 'E-mail',   $this->getTagValue($t, 'email'));

        return $y + $h;
    }

    // ── Bloco 5: Destinatário da Operação ─────────────────────────────────────
    private function blocoDestinatario(float $y): float
    {
        if ($this->destEhTomador) {
            $this->drawSuppressedBlock($y, $this->hDestinatario,
                'O DESTINATÁRIO É O PRÓPRIO TOMADOR/ADQUIRENTE DA OPERAÇÃO');
            return $y + $this->hDestinatario;
        }
        if (!$this->hasDestinatario) {
            $this->drawSuppressedBlock($y, $this->hDestinatario,
                'DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e');
            return $y + $this->hDestinatario;
        }

        $h = $this->hDestinatario;
        $d = $this->dest;

        $this->drawBlocoHeader($y, self::H_ROW, 'DESTINATÁRIO DA OPERAÇÃO');

        $cnpjCpfNif = $this->formatCnpjCpfNif(
            $this->getTagValue($d, 'CNPJ'),
            $this->getTagValue($d, 'CPF'),
            $this->getTagValue($d, 'NIF')
        );

        $this->drawField(self::X_C2, $y, self::W_WIDE, self::H_ROW, self::LBL_CNPJ_CPF_NIF, $cnpjCpfNif);
        $this->drawField(self::X_C4, $y, self::W_C,    self::H_ROW, 'TELEFONE',              $this->getTagValue($d, 'fone'));

        $r1y = $y + self::H_ROW;
        $this->drawField(self::X_L,  $r1y, self::W_WIDE, self::H_ROW, self::LBL_NOME_EMPRESARIAL, $this->getTagValue($d, 'xNome'));
        $this->drawField(self::X_C3, $r1y, self::W_C,    self::H_ROW, self::LBL_MUNICIPIO_UF,    $this->getMunicipioUF($d));
        $this->drawField(self::X_C4, $r1y, self::W_C,    self::H_ROW, self::LBL_CODIGO_IBGE_CEP, $this->getCodigoIbgeCep($d));

        $r2y = $r1y + self::H_ROW;
        $this->drawField(self::X_L,  $r2y, self::W_WIDE, self::H_ROW, self::LBL_ENDERECO, $this->getEndereco($d));
        $this->drawField(self::X_C3, $r2y, self::W_C,    self::H_ROW, ' ',        ' ');
        $this->drawField(self::X_C4, $r2y, self::W_C,    self::H_ROW, 'E-mail',   $this->getTagValue($d, 'email'));

        return $y + $h;
    }

    // ── Bloco 6: Intermediário da Operação ────────────────────────────────────
    private function blocoIntermediario(float $y): float
    {
        if (!$this->hasIntermediario) {
            $this->drawSuppressedBlock($y, $this->hIntermediario,
                'INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e');
            return $y + $this->hIntermediario;
        }

        $h = $this->hIntermediario;
        $i = $this->interm;

        $this->drawBlocoHeader($y, self::H_ROW, 'INTERMEDIÁRIO DA OPERAÇÃO');

        $cnpjCpfNif = $this->formatCnpjCpfNif(
            $this->getTagValue($i, 'CNPJ'),
            $this->getTagValue($i, 'CPF'),
            $this->getTagValue($i, 'NIF')
        );

        $this->drawField(self::X_C2, $y, self::W_C, self::H_ROW, self::LBL_CNPJ_CPF_NIF,    $cnpjCpfNif);
        $this->drawField(self::X_C3, $y, self::W_C, self::H_ROW, self::LBL_INSC_MUN,        $this->getTagValue($i, 'IM'));
        $this->drawField(self::X_C4, $y, self::W_C, self::H_ROW, 'TELEFONE',                 $this->getTagValue($i, 'fone'));

        $r1y = $y + self::H_ROW;
        $this->drawField(self::X_L,  $r1y, self::W_WIDE, self::H_ROW, self::LBL_NOME_EMPRESARIAL, $this->getTagValue($i, 'xNome'));
        $this->drawField(self::X_C3, $r1y, self::W_C,    self::H_ROW, self::LBL_MUNICIPIO_UF,    $this->getMunicipioUF($i));
        $this->drawField(self::X_C4, $r1y, self::W_C,    self::H_ROW, self::LBL_CODIGO_IBGE_CEP, $this->getCodigoIbgeCep($i));

        $r2y = $r1y + self::H_ROW;
        $this->drawField(self::X_L,  $r2y, self::W_WIDE, self::H_ROW, self::LBL_ENDERECO, $this->getEndereco($i));
        $this->drawField(self::X_C3, $r2y, self::W_C,    self::H_ROW, ' ',        ' ');
        $this->drawField(self::X_C4, $r2y, self::W_C,    self::H_ROW, 'E-mail',   $this->getTagValue($i, 'email'));

        return $y + $h;
    }

    // ── Bloco 7: Serviço Prestado ─────────────────────────────────────────────
    private function blocoServico(float $y): float
    {
        $h    = $this->hServico;
        $s    = $this->serv;
        $cServ = $s ? $s->getElementsByTagName('cServ')->item(0) : null;

        $cTribNac = $this->getTagValue($cServ, 'cTribNac') ?: '-';
        $cTribMun = $this->getTagValue($cServ, 'cTribMun') ?: '-';
        $codTrib  = "{$cTribNac} / {$cTribMun}";
        $cNBS     = $this->getTagValue($s, 'cNBS') ?: '-';

        $locPres   = $s ? $s->getElementsByTagName('locPres')->item(0) : null;
        $localPres = $this->getTagValue($locPres, 'cLocPres') ?: '-';

        // Descrição do código: xTribMun ?? xTribNac direto do infNFSe (§2.4.5 NT-008)
        $xDescCodRaw = $this->getTagValue($this->infNFSe, 'xTribMun')
                    ?: $this->getTagValue($this->infNFSe, 'xTribNac')
                    ?: '';
        $xDescCod = iconv_strlen($xDescCodRaw, 'UTF-8') > 167
            ? iconv_substr($xDescCodRaw, 0, 167, 'UTF-8') . '...'
            : $xDescCodRaw;
        $xDescServ = $this->getTagValue($s, 'xDescServ') ?: '-';

        $this->drawBlocoHeader($y, self::H_ROW, 'SERVIÇO PRESTADO');
        $this->drawField(self::X_C2, $y, self::W_C,  self::H_ROW, 'CÓDIGO DE TRIBUTAÇÃO NAC./MUN.', $codTrib);
        $this->drawField(self::X_C3, $y, self::W_C,  self::H_ROW, 'CÓDIGO DA NBS',                  $cNBS);
        $this->drawField(self::X_C4, $y, self::W_C,  self::H_ROW, 'LOCAL DA PRESTAÇÃO',             $localPres);

        // Linha sem label (NT-008 §2.4.5: "Não há título (label) deste campo no DANFSe")
        $r1y = $y + self::H_ROW;
        $this->pdf->textBox(self::X_L + 0.5, $r1y + 0.5, self::W_FULL - 1.0, self::H_COD_SERV - 0.5,
            $xDescCod, self::F_CONTEUDO, 'T', 'L', false, '', false, 0);
        $this->pdf->Line(self::X_DIV, $r1y + self::H_COD_SERV, self::X_DIV + self::W_DIV, $r1y + self::H_COD_SERV);

        $descY = $r1y + self::H_COD_SERV;
        $descH = $h - self::H_ROW - self::H_COD_SERV;
        $this->drawField(self::X_L, $descY, self::W_FULL, $descH, 'Descrição do Serviço', $xDescServ);

        return $y + $h;
    }

    // ── Bloco 8: Tributação Municipal (ISSQN) ─────────────────────────────────
    private function blocoISSQN(float $y): float
    {
        if (!$this->hasISSQN) {
            $this->drawSuppressedBlock($y, $this->hISSQN,
                'TRIBUTAÇÃO MUNICIPAL (ISSQN) - OPERAÇÃO NÃO SUJEITA AO ISSQN');
            return $y + $this->hISSQN;
        }

        $h        = $this->hISSQN;
        $tribNode = $this->valores ? $this->valores->getElementsByTagName('trib')->item(0) : null;
        $tribMun  = $tribNode ? $tribNode->getElementsByTagName('tribMun')->item(0) : null;
        $v        = $this->getISSQNValues($tribMun);

        $this->drawBlocoHeader($y, self::H_ROW, 'TRIBUTAÇÃO MUNICIPAL (ISSQN)');
        $this->drawField(self::X_C2, $y, self::W_C,    self::H_ROW, 'TIPO DE TRIBUTAÇÃO DO ISSQN',         $v['tTrib']);
        $this->drawField(self::X_C3, $y, self::W_WIDE, self::H_ROW, 'MUN./UF/PAÍS DA INCIDÊNCIA DO ISSQN', $v['cMunFG']);

        $r1y = $y + self::H_ROW;
        $this->drawField(self::X_L,  $r1y, self::W_C, self::H_ROW, 'Regime Especial de Tributação', $v['regEsp']);
        $this->drawField(self::X_C2, $r1y, self::W_C, self::H_ROW, 'Tipo de Imunidade',             $v['tpImun']);
        $this->drawField(self::X_C3, $r1y, self::W_C, self::H_ROW, 'Suspensão da Exigibilidade',    $v['tpSusp']);
        $this->drawField(self::X_C4, $r1y, self::W_C, self::H_ROW, 'Número Processo Suspensão',     $v['nProc']);

        $r2y = $r1y + self::H_ROW;
        $this->drawField(self::X_L,  $r2y, self::W_C, self::H_ROW, 'Benefício Municipal',    $v['tpBM']);
        $this->drawField(self::X_C2, $r2y, self::W_C, self::H_ROW, 'Cálculo do BM',           $v['vBM']);
        $this->drawField(self::X_C3, $r2y, self::W_C, self::H_ROW, 'Total Deduções/Reduções', $v['vDed']);
        $this->drawField(self::X_C4, $r2y, self::W_C, self::H_ROW, 'Desconto Incondicionado', $v['vDI']);

        $r3y = $r2y + self::H_ROW;
        $this->drawField(self::X_L,  $r3y, self::W_C, self::H_ROW, 'BC ISSQN',          $v['vBC']);
        $this->drawField(self::X_C2, $r3y, self::W_C, self::H_ROW, 'ALÍQUOTA APLICADA', $v['pAliq']);
        $this->drawField(self::X_C3, $r3y, self::W_C, self::H_ROW, 'RETENÇÃO DO ISSQN', $v['tpRet']);
        $this->drawField(self::X_C4, $r3y, self::W_C, self::H_ROW, 'ISSQN APURADO',     $v['vISSQN']);

        return $y + $h;
    }

    private function getISSQNValues(?\DOMElement $tribMun): array
    {
        $vBMRaw  = $this->getTagValue($tribMun, 'vCalcBM') ?: $this->getTagValue($tribMun, 'vRedBCM');
        $vDedRaw = $this->getTagValue($tribMun, 'vDR')     ?: $this->getTagValue($tribMun, 'vCalcDR');
        return [
            'tTrib'  => $this->getTTribMunLabel($this->getTagValue($tribMun, 'tTribMun')),
            'cMunFG' => $this->getTagValue($tribMun, 'cMunFG')      ?: '-',
            'regEsp' => $this->getTagValue($tribMun, 'regEspTrib')  ?: '-',
            'tpImun' => $this->getTagValue($tribMun, 'tpImunidade') ?: '-',
            'tpSusp' => $this->getTagValue($tribMun, 'tpSusp')      ?: '-',
            'nProc'  => $this->getTagValue($tribMun, 'nProcess')    ?: '-',
            'tpBM'   => $this->getTagValue($tribMun, 'tpBM')        ?: '-',
            'vBM'    => $vBMRaw  ?: '-',
            'vDed'   => $vDedRaw ?: '-',
            'vDI'    => $this->getTagValue($tribMun, 'vDescIncond') ?: '-',
            'vBC'    => $this->getTagValue($tribMun, 'vBC')         ?: '-',
            'pAliq'  => $this->getTagValue($tribMun, 'pAliqAplic')  ?: '-',
            'tpRet'  => $this->getTpRetISSQNLabel($this->getTagValue($tribMun, 'tpRetISSQN')),
            'vISSQN' => $this->getTagValue($tribMun, 'vISSQN')      ?: '-',
        ];
    }

    // ── Bloco 9: Tributação Federal ───────────────────────────────────────────
    private function blocoTribFederal(float $y): float
    {
        $h        = $this->hTribFederal;
        $tribNode = $this->valores ? $this->valores->getElementsByTagName('trib')->item(0) : null;
        $tribFed  = $tribNode ? $tribNode->getElementsByTagName('tribFed')->item(0) : null;

        $this->drawBlocoHeader($y, self::H_ROW, 'TRIBUTAÇÃO FEDERAL (EXCETO CBS)');
        $vIRRF = $this->getTagValue($tribFed, 'vRetIRRF')  ?: '-';
        $vCP   = $this->getTagValue($tribFed, 'vRetCP')    ?: '-';
        $vCSLL = $this->getTagValue($tribFed, 'vRetCSLL')  ?: '-';
        $this->drawField(self::X_C2, $y, self::W_C, self::H_ROW, 'IRRF',                             $vIRRF);
        $this->drawField(self::X_C3, $y, self::W_C, self::H_ROW, 'CONTRIB. PREVIDENCIÁRIA - RETIDA', $vCP);
        $this->drawField(self::X_C4, $y, self::W_C, self::H_ROW, 'CONTRIBUIÇÕES SOCIAIS - RETIDAS',  $vCSLL);

        $r1y    = $y + self::H_ROW;
        $vPIS   = $this->getTagValue($tribFed, 'vPIS')            ?: '-';
        $vCofins = $this->getTagValue($tribFed, 'vCofins')        ?: '-';
        $tpRet  = $this->getTagValue($tribFed, 'tpRetPisCofins')  ?: '-';
        $this->drawField(self::X_L,  $r1y, self::W_C,    self::H_ROW, 'PIS - Déb. Apuração Própria',    $vPIS);
        $this->drawField(self::X_C2, $r1y, self::W_C,    self::H_ROW, 'COFINS - Déb. Apuração Própria', $vCofins);
        $this->drawField(self::X_C3, $r1y, self::W_WIDE, self::H_ROW, 'Descrição Contrib. Sociais',     $tpRet);

        return $y + $h;
    }

    // ── Bloco 10: Tributação IBS/CBS ──────────────────────────────────────────
    private function blocoIBSCBS(float $y): float
    {
        $h     = $this->hIBSCBS;
        $valEl = $this->ibscbs ? $this->ibscbs->getElementsByTagName('values')->item(0) : null;
        $trib  = $valEl ? $valEl->getElementsByTagName('trib')->item(0) : null;
        $v     = $this->getIBSCBSValues($trib);

        $this->drawBlocoHeader($y, self::H_ROW, 'TRIBUTAÇÃO IBS / CBS');
        $this->drawField(self::X_C2, $y, self::W_C,    self::H_ROW, 'CST / cClassTrib',                       $v['cst']);
        $this->drawField(self::X_C3, $y, self::W_WIDE, self::H_ROW, 'INDICADOR OP./CÓD. IBGE/MUN. INCID./UF', "{$v['cIndOp']} / {$v['cLocal']}");

        $r1y = $y + self::H_ROW;
        $this->drawField(self::X_L,  $r1y, self::W_C, self::H_ROW, 'EXCLUSÕES E RED. DA BASE DE CÁLCULO', $v['vExcl']);
        $this->drawField(self::X_C2, $r1y, self::W_C, self::H_ROW, 'BASE CÁLCULO APÓS EXCL. E RED.',       $v['vBC']);
        $this->drawField(self::X_C3, $r1y, self::W_C, self::H_ROW, 'RED. ALÍQ. IBS / RED. ALÍQ. CBS',      "{$v['pRedIBS']} / {$v['pRedCBS']}");
        $this->drawField(self::X_C4, $r1y, self::W_C, self::H_ROW, 'ALÍQUOTA IBS UF / IBS MUN',            "{$v['pIBSUF']} / {$v['pIBSMun']}");

        $r2y = $r1y + self::H_ROW;
        $this->drawField(self::X_L,  $r2y, self::W_C, self::H_ROW, 'ALÍQ. EFETIVA MUNICIPAL - IBS', $v['pEfMun']);
        $this->drawField(self::X_C2, $r2y, self::W_C, self::H_ROW, 'VALOR APURADO MUNICIPAL - IBS',  $v['vIBSMun']);
        $this->drawField(self::X_C3, $r2y, self::W_C, self::H_ROW, 'ALÍQ. EFETIVA ESTADUAL - IBS',  $v['pEfUF']);
        $this->drawField(self::X_C4, $r2y, self::W_C, self::H_ROW, 'VALOR APURADO ESTADUAL - IBS',  $v['vIBSUF']);

        $r3y = $r2y + self::H_ROW;
        $this->drawField(self::X_L,  $r3y, self::W_C, self::H_ROW, 'VALOR TOTAL APURADO - IBS', $v['vIBSTot']);
        $this->drawField(self::X_C2, $r3y, self::W_C, self::H_ROW, 'ALÍQUOTA - CBS',              $v['pCBS']);
        $this->drawField(self::X_C3, $r3y, self::W_C, self::H_ROW, 'ALÍQ. EFETIVA - CBS',         $v['pEfCBS']);
        $this->drawField(self::X_C4, $r3y, self::W_C, self::H_ROW, 'VALOR TOTAL APURADO - CBS',   $v['vCBS']);

        return $y + $h;
    }

    private function getIBSCBSValues(?\DOMElement $trib): array
    {
        return [
            'cst'     => ($this->getTagValue($trib, 'CST') || $this->getTagValue($trib, 'cClassTrib'))
                ? ($this->getTagValue($trib, 'CST') ?: '-') . ' / ' . ($this->getTagValue($trib, 'cClassTrib') ?: '-')
                : '-',
            'cIndOp'  => $this->getTagValue($trib, 'cIndOp')       ?: '-',
            'cLocal'  => $this->getTagValue($trib, 'cLocalidade')  ?: '-',
            'vExcl'   => $this->getTagValue($trib, 'vDescIncond')  ?: '0.00',
            'vBC'     => $this->getTagValue($trib, 'vBC')           ?: '-',
            'pRedIBS' => $this->getTagValue($trib, 'pRedAliqIBS')  ?: '0',
            'pRedCBS' => $this->getTagValue($trib, 'pRedAliqCBS')  ?: '0',
            'pIBSUF'  => $this->getTagValue($trib, 'pIBSUF')       ?: '0',
            'pIBSMun' => $this->getTagValue($trib, 'pIBSMun')      ?: '0',
            'pEfMun'  => $this->getTagValue($trib, 'pAliqEfetMun') ?: '0.00',
            'vIBSMun' => $this->getTagValue($trib, 'vIBSMun')      ?: '0.00',
            'pEfUF'   => $this->getTagValue($trib, 'pAliqEfetUF')  ?: '0.00',
            'vIBSUF'  => $this->getTagValue($trib, 'vIBSUF')       ?: '0.00',
            'vIBSTot' => $this->getTagValue($trib, 'vIBSTot')      ?: '0.00',
            'pCBS'    => $this->getTagValue($trib, 'pCBS')          ?: '0.00',
            'pEfCBS'  => $this->getTagValue($trib, 'pAliqEfetCBS') ?: '0.00',
            'vCBS'    => $this->getTagValue($trib, 'vCBS')          ?: '0.00',
        ];
    }

    // ── Bloco 11: Valor Total da NFS-e ────────────────────────────────────────
    private function blocoValorTotal(float $y): float
    {
        $h  = $this->hValorTotal;
        $v  = $this->valores;
        $ci = $this->totCIBS;

        $vServ   = $this->getTagValue($v, 'vServ')      ?: '-';
        $vDescI  = $this->getTagValue($v, 'vDescIncond') ?: '-';
        $vDescC  = $this->getTagValue($v, 'vDescCond')   ?: '-';
        $vTotRet = $this->getTagValue($v, 'vTotalRet')   ?: '-';
        $vLiq    = $this->getTagValue($v, 'vLiq')        ?: '-';

        $ibsMunNode = $ci ? $ci->getElementsByTagName('totIBSMunTot')->item(0) : null;
        $cbsNode    = $ci ? $ci->getElementsByTagName('gCBS')->item(0) : null;
        $rawIBS      = $ibsMunNode ? $this->getTagValue($ibsMunNode, 'vIBSTot') : null;
        $vIBSTot     = $rawIBS ?: '0.00';
        $rawCBS      = $cbsNode ? $this->getTagValue($cbsNode, 'vCBS') : null;
        $vCBSTot     = $rawCBS ?: '0.00';
        $totalIBSCBS = number_format(
            (float) str_replace(',', '.', $vIBSTot) + (float) str_replace(',', '.', $vCBSTot),
            2, '.', ''
        );
        $rawVTotNF = $ci ? $this->getTagValue($ci, 'vTotNF') : null;
        $vTotNF    = $rawVTotNF ?: '-';

        $this->drawBlocoHeader($y, self::H_ROW7, 'VALOR TOTAL DA NFS-E');
        $this->drawField(self::X_C2, $y, self::W_C, self::H_ROW7, 'VALOR DA OPERAÇÃO / SERVIÇO', $vServ);
        $this->drawField(self::X_C3, $y, self::W_C, self::H_ROW7, 'DESCONTO INCONDICIONADO',      $vDescI);
        $this->drawField(self::X_C4, $y, self::W_C, self::H_ROW7, 'DESCONTO CONDICIONADO',        $vDescC);

        $r1y = $y + self::H_ROW7;
        $this->drawField(self::X_L,  $r1y, self::W_C, self::H_ROW7, 'TOTAL DAS RETENÇÕES (ISSQN/FEDERAIS)', $vTotRet);
        $this->drawField(self::X_C2, $r1y, self::W_C, self::H_ROW7, 'VALOR LÍQUIDO DA NFS-e',               $vLiq);
        $this->drawField(self::X_C3, $r1y, self::W_C, self::H_ROW7, 'TOTAL DO IBS/CBS',                     $totalIBSCBS);
        $this->drawField(self::X_C4, $r1y, self::W_C, self::H_ROW7, 'VALOR LÍQUIDO DA NFS-e + IBS/CBS',     $vTotNF, true);

        return $y + $h;
    }

    // ── Bloco 12: Informações Complementares ──────────────────────────────────
    private function blocoInfoCompl(float $y): float
    {
        $h  = $this->hInfoCompl;
        $ic = $this->infDPS
            ? $this->infDPS->getElementsByTagName('infoCompl')->item(0)
            : null;

        $xInfComp = $this->getTagValue($ic, 'xInfComp') ?: '';

        $hdr = self::H_ROW;
        $this->drawBlocoHeader($y, $hdr, 'INFORMAÇÕES COMPLEMENTARES');

        $textY = $y + $hdr;
        $textH = $h - $hdr;
        $this->pdf->textBox(
            self::X_L + 0.5, $textY + 0.5,
            self::W_FULL - 1.0, $textH - 1.0,
            $xInfComp ?: '-', self::F_CONTEUDO, 'T', 'L', false, ''
        );

        return $y + $h;
    }

    // ── Bloco 13: Canhoto (opcional) ──────────────────────────────────────────
    private function blocoCanhoto(float $y): float
    {
        $h = $this->hCanhoto;

        $chaveRaw  = $this->infNFSe->getAttribute('Id');
        $chave     = (string) preg_replace('/^NFSe/', '', $chaveRaw);
        $nNFSe     = $this->getTagValue($this->infNFSe, 'nNFSe') ?: '-';
        $nfseChave = "{$nNFSe} / {$chave}";

        $this->drawBlocoHeader($y, $h, ' ');

        $this->drawField(self::X_L,  $y, self::W_C1,   $h, 'DATA CIENTIFICAÇÃO',         ' ');
        $this->drawField(self::X_C2, $y, self::W_C,    $h, 'IDENTIFICAÇÃO E ASSINATURA', ' ');
        $this->drawField(self::X_C3, $y, self::W_WIDE, $h, 'Nº NFS-E / CHAVE NFS-E',    $nfseChave);

        return $y + $h;
    }

    // ── Marcas d'água ─────────────────────────────────────────────────────────
    private function watermark(): void
    {
        $cStat = $this->getTagValue($this->infNFSe, 'cStat');

        $message = match ($cStat) {
            '2'     => 'CANCELADA',
            '3'     => 'SUBSTITUÍDA',
            default => '',
        };

        if (empty($message)) {
            return;
        }

        $this->pdf->SetTextColor(166, 166, 166);
        $cx = $this->maxW / 2.0;
        $cy = $this->maxH / 2.0;

        $this->pdf->rotate(-45, $cx, $cy);
        $this->pdf->textBox(
            $cx - 80, $cy - 15, 160, 30,
            $message, self::F_WATERMARK, 'C', 'C', false, ''
        );
        $this->pdf->rotate(0);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    // ── Auxiliares de desenho ─────────────────────────────────────────────────

    private function drawBlocoHeader(float $y, float $h, string $label): void
    {
        $this->pdf->SetFillColor(242, 242, 242);
        $this->pdf->Rect(self::X_L, $y, self::W_C1, $h, 'F');
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Line(self::X_DIV, $y, self::X_DIV + self::W_DIV, $y);
        $this->pdf->textBox(self::X_L + 0.5, $y, self::W_C1 - 1.0, $h, strtoupper($label),
            self::F_BLOCO_TIT, 'C', 'L', false, '');
    }

    private function drawField(
        float $x, float $y, float $w, float $h,
        string $label, string $value,
        bool $shade = false,
        array $labelFont = self::F_CAMPO_TIT
    ): void {
        if ($shade) {
            $this->pdf->SetFillColor(242, 242, 242);
            $this->pdf->Rect($x, $y, $w, $h, 'F');
            $this->pdf->SetFillColor(255, 255, 255);
        }
        $displayLabel = ($labelFont['size'] === 6) ? $this->toTitleCase($label) : $label;
        $this->pdf->textBox($x + 0.5, $y + 0.4, $w - 1.0, 2.8, $displayLabel,
            $labelFont, 'T', 'L', false, '');
        $this->pdf->textBox($x + 0.5, $y + 3.2, $w - 1.0, $h - 3.6, $value ?: '-',
            self::F_CONTEUDO, 'T', 'L', false, '');
    }

    private function drawSuppressedBlock(float $y, float $h, string $message): void
    {
        $this->pdf->Line(self::X_DIV, $y, self::X_DIV + self::W_DIV, $y);
        $this->pdf->Line(self::X_DIV, $y + $h, self::X_DIV + self::W_DIV, $y + $h);
        $this->pdf->textBox(self::X_L + 0.5, $y, self::W_FULL - 1.0, $h, strtoupper($message),
            self::F_BLOCO_TIT, 'C', 'C', false, '');
    }

    private function drawQrCode(float $x, float $y, float $size, string $url): void
    {
        $barcode = new Barcode();
        $bobj    = $barcode->getBarcodeObj('QRCODE', $url, -4, -4, 'black', [0, 0, 0, 0]);
        $pngData = $bobj->getPngData();
        $qrImage = 'data://text/plain;base64,' . base64_encode($pngData);
        $this->pdf->Image($qrImage, $x, $y, $size, $size, 'PNG');
    }

    private function formatCnpjCpfNif(string $cnpj, string $cpf, string $nif): string
    {
        if (!empty($cnpj) && strlen($cnpj) === 14) {
            return (string) preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        }
        if (!empty($cpf) && strlen($cpf) === 11) {
            return (string) preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }
        return !empty($nif) ? $nif : '-';
    }

    private function formatDate(string $date): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            return "{$m[3]}/{$m[2]}/{$m[1]}";
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $date, $m)) {
            return "01/{$m[2]}/{$m[1]}";
        }
        return $date ?: '-';
    }

    private function formatDatetime(string $dt): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}:\d{2}:\d{2})/', $dt, $m)) {
            return "{$m[3]}/{$m[2]}/{$m[1]} {$m[4]}";
        }
        return $dt ?: '-';
    }

    private function resolveAddrNode(\DOMElement $el): ?\DOMElement
    {
        $addrNode = $el->getElementsByTagName('endNac')->item(0)
                 ?: $el->getElementsByTagName('enderNac')->item(0);
        if ($addrNode) {
            return $addrNode;
        }
        $end = $el->getElementsByTagName('end')->item(0);
        if (!$end) {
            return null;
        }
        return $end->getElementsByTagName('endNac')->item(0)
            ?: $end->getElementsByTagName('enderNac')->item(0);
    }

    private function getMunicipioUF(?\DOMElement $el): string
    {
        if (empty($el)) {
            return '-';
        }
        $addrNode = $this->resolveAddrNode($el);
        if ($addrNode) {
            return $this->formatMunicipioUFFromAddr($addrNode);
        }
        $endExt  = $el->getElementsByTagName('endExt')->item(0);
        $xCidade = $endExt ? $this->getTagValue($endExt, 'xCidade') : null;
        $cPais   = $endExt ? $this->getTagValue($endExt, 'cPais') : null;
        $fallback = $xCidade ?: '-';
        return $xCidade && $cPais ? "{$xCidade} / {$cPais}" : $fallback;
    }

    private function formatMunicipioUFFromAddr(\DOMElement $addrNode): string
    {
        $xMun              = $this->getTagValue($addrNode, 'xMun');
        $uf                = $this->getTagValue($addrNode, 'UF');
        $cMun              = $this->getTagValue($addrNode, 'cMun');
        [$nomeIbge, $ufIbge] = $this->lookupMunicipio($cMun);
        $nome = $xMun ?: $nomeIbge;
        $uf   = $uf   ?: $ufIbge;
        if ($nome && $uf) {
            return "{$nome} / {$uf}";
        }
        return $nome ?: ($uf ?: '-');
    }

    private function lookupMunicipio(string $cMun): array
    {
        static $municipios = null;
        if ($municipios === null) {
            $municipios = require __DIR__ . '/ibge-municipios.php';
        }
        return $municipios[$cMun] ?? [$cMun, ''];
    }

    private function getCodigoIbgeCep(?\DOMElement $el): string
    {
        if (empty($el)) {
            return '-';
        }
        $addrNode = $this->resolveAddrNode($el);
        if (!$addrNode) {
            return '-';
        }
        return $this->formatCodigoIbgeCep($addrNode);
    }

    private function formatCodigoIbgeCep(\DOMElement $addrNode): string
    {
        $cMun = $this->getTagValue($addrNode, 'cMun');
        $cep  = $this->getTagValue($addrNode, 'CEP');
        if (!$cMun && !$cep) {
            return '-';
        }
        $cepFmt  = $cep ? (string) preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep) : '';
        $fallback = $cMun ?: ($cepFmt ?: '-');
        return $cMun && $cepFmt ? "{$cMun} / {$cepFmt}" : $fallback;
    }

    private function toTitleCase(string $text): string
    {
        $lower = str_replace(
            ['Á','É','Í','Ó','Ú','Â','Ê','Î','Ô','Û','À','Ã','Õ','Ç'],
            ['á','é','í','ó','ú','â','ê','î','ô','û','à','ã','õ','ç'],
            strtolower($text)
        );
        return ucwords($lower);
    }

    private function getEndereco(?\DOMElement $el): string
    {
        if (empty($el)) {
            return '';
        }
        // Caso 1: endNac/enderNac diretamente sob el e xLgr dentro dele (estrutura padrão ou emit)
        $addrNode = $el->getElementsByTagName('endNac')->item(0)
                 ?: $el->getElementsByTagName('enderNac')->item(0);
        if ($addrNode && $this->getTagValue($addrNode, 'xLgr')) {
            $parts = array_filter([
                $this->getTagValue($addrNode, 'xLgr'),
                $this->getTagValue($addrNode, 'nro'),
                $this->getTagValue($addrNode, 'xCpl'),
                $this->getTagValue($addrNode, 'xBairro'),
            ]);
            return implode(', ', $parts) ?: '';
        }
        // Caso 2: wrapper <end> com xLgr como filho direto (estrutura toma em XMLs reais de NFS-e)
        $end = $el->getElementsByTagName('end')->item(0);
        if ($end) {
            $parts = array_filter([
                $this->getTagValue($end, 'xLgr'),
                $this->getTagValue($end, 'nro'),
                $this->getTagValue($end, 'xCpl'),
                $this->getTagValue($end, 'xBairro'),
            ]);
            if ($parts) {
                return implode(', ', $parts);
            }
        }
        // Caso 3: xLgr como filho direto de el (fallback)
        $parts = array_filter([
            $this->getTagValue($el, 'xLgr'),
            $this->getTagValue($el, 'nro'),
            $this->getTagValue($el, 'xCpl'),
            $this->getTagValue($el, 'xBairro'),
        ]);
        return implode(', ', $parts) ?: '';
    }

    private function getTpEmitLabel(string $code): string
    {
        return match ($code) {
            '1'     => 'Prestador',
            '2'     => 'Tomador',
            '3'     => 'Intermediário',
            default => $code,
        };
    }

    private function getCStatLabel(string $code): string
    {
        $map = [
            '100' => 'NFS-e Gerada',
            '101' => 'NFS-e de Substituição Gerada',
            '102' => 'NFS-e de Decisão Judicial',
            '103' => 'NFS-e Avulsa',
        ];
        return $map[$code] ?? $code;
    }

    private function getFinNFSeLabel(string $code): string
    {
        return match ($code) {
            '1'     => 'NFS-e regular',
            '2'     => 'NFS-e complementar',
            '3'     => 'NFS-e de ajuste',
            '4'     => 'NFS-e substituta',
            default => $code,
        };
    }

    private function getRegApTribSNLabel(string $code): string
    {
        $label = match ($code) {
            '1'     => 'Regime de apuração dos tributos federais e municipal pelo SN',
            '2'     => 'Regime de apuração dos tributos federais pelo SN e o ISSQN pela NFS-e conforme respectiva legislação municipal do tributo',
            '3'     => 'Regime de apuração dos tributos federais e municipal pela NFS-e conforme respectivas legilações federal e municipal de cada tributo',
            default => $code ?: '-',
        };
        return mb_strlen($label) > 77 ? mb_substr($label, 0, 74) . '...' : $label;
    }

    private function getOpSimpNacLabel(string $code): string
    {
        return match ($code) {
            '1'     => 'Não Optante',
            '2'     => 'Optante',
            '3'     => 'Optante - Excesso de Receita',
            default => $code,
        };
    }

    private function getTTribMunLabel(string $code): string
    {
        return match ($code) {
            '1'     => 'Operação Tributável',
            '2'     => 'Operação Isenta ou Não Tributável',
            '3'     => 'Exportação de Serviços',
            '4'     => 'Imune',
            default => $code,
        };
    }

    private function getTpRetISSQNLabel(string $code): string
    {
        return match ($code) {
            '1'     => 'Não Retido',
            '2'     => 'Retido pelo Tomador',
            '3'     => 'Retido pelo Intermediário',
            default => $code,
        };
    }
}

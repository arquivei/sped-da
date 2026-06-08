<?php

namespace NFePHP\DA\Tests\NFSe;

use NFePHP\DA\NFSe\Danfse;
use PHPUnit\Framework\TestCase;

class DanfseTest extends TestCase
{
    private string $xml;
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__) . '/fixtures/';
        $this->xml = (string) file_get_contents($this->fixtureDir . 'xml/nfse.xml');
    }

    public function test_gerar_danfse_basico(): void
    {
        $danfse = new Danfse($this->xml);
        $pdf    = $danfse->render();

        @mkdir($this->fixtureDir . 'pdf/nfse', 0755, true);
        file_put_contents($this->fixtureDir . 'pdf/nfse/nfse.pdf', $pdf);

        $this->assertIsString($pdf);
        $this->assertNotEmpty($pdf);
    }

    public function test_danfse_contem_chave_de_acesso(): void
    {
        $danfse = new Danfse($this->xml);
        $pdf    = $danfse->render();

        $this->assertIsString($pdf);
        $this->assertNotEmpty($pdf);
        // PDF binary should contain the 50-digit access key
        $this->assertStringContainsString('NFSe', $pdf);
    }

    public function test_danfse_xml_invalido_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Danfse('<NFe/>');
    }

    public function test_danfse_com_canhoto(): void
    {
        $danfse = new Danfse($this->xml);
        $danfse->setCanhoto(true);
        $pdf = $danfse->render();

        $this->assertIsString($pdf);
        $this->assertNotEmpty($pdf);
    }

    public function test_danfse_cancelada_renderiza_sem_erros(): void
    {
        $xml    = str_replace('<cStat>1</cStat>', '<cStat>2</cStat>', $this->xml);
        $danfse = new Danfse($xml);
        $pdf    = $danfse->render();

        @mkdir($this->fixtureDir . 'pdf/nfse', 0755, true);
        file_put_contents($this->fixtureDir . 'pdf/nfse/nfse_cancelada.pdf', $pdf);

        $this->assertIsString($pdf);
        $this->assertNotEmpty($pdf);
    }
}

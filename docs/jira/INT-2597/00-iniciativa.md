# INT-2597 — Adequação ao PDF NFS-e Nacional

> Rascunho de conteúdo para a Iniciativa [INT-2597](https://arquivei.atlassian.net/browse/INT-2597), elaborado a partir da análise técnica registrada em [INT-2581](https://arquivei.atlassian.net/browse/INT-2581) e da inspeção do código atual em `src/NFSe/Danfse.php` (branch `arquivei-releases`) comparado à reescrita "v2" do upstream `nfephp-org/sped-da` (branch `master`).

## Resumo

### O que & Por quê

O renderer do DANFSe (Documento Auxiliar da NFS-e Nacional), implementado em `src/NFSe/Danfse.php` e mergeado na branch `arquivei-releases` via PRs #35 e #36 (10–11/06/2026), já cobre o layout básico da NT-008 (identificação, prestador/tomador/destinatário/intermediário, serviço, ISSQN, tributação federal, IBS/CBS, valor total, canhoto e marca d'água).

O upstream (`nfephp-org/sped-da`) publicou em paralelo uma reescrita própria do mesmo documento ("DanfSe v2", ~1188 linhas). Comparando as duas implementações, a versão do fork tem lacunas concretas em relação a exigências legais e a comportamentos que o próprio upstream já trata. O objetivo desta iniciativa é fechar essas lacunas — **não** substituir a implementação do fork pela do upstream (as estruturas de dados/DOM não são intercambiáveis e a reescrita seria um esforço de risco desproporcional ao ganho), mas usá-la como referência para adequar o DANFSe da Arquivei ao que é esperado de um documento fiscal nacional.

### Como & critérios de aceitação

* Todas as lacunas abaixo (detalhadas nas tarefas de refinamento técnico) são corrigidas sem regressão visual no restante do documento.
* O layout permanece compatível com os XMLs de NFS-e Nacional já em uso em produção (fixture `tests/fixtures/xml/nfse.xml` e amostras reais anonimizadas).
* Nenhuma das correções depende de dados que não estejam presentes no XML padrão da NFS-e Nacional (schema `infNFSe`/`infDPS`).
* Testes unitários cobrindo cada bloco corrigido/adicionado são criados ou atualizados.
* A geração do PDF é validada visualmente (revisão manual do PDF gerado) para pelo menos: NFS-e autorizada, cancelada, substituída e com/sem retenções federais.

### Definição de pronto (DoD)

- [ ] Código revisado e aprovado em PR na branch `arquivei-releases`.
- [ ] Testes unitários novos/atualizados passando (`vendor/bin/phpunit`).
- [ ] PDF de exemplo (`examples/nfse/danfse.php`) gerado e revisado visualmente para os cenários de aceite.
- [ ] Nenhuma regressão nos demais blocos do DANFSe (comparação visual com o PDF gerado antes da mudança).
- [ ] CHANGELOG/registro de versão atualizado, se o projeto mantiver um.
- [ ] Sem pendências de linha (SonarQube/PSR-12), seguindo o padrão já aplicado nos commits `refactor(danfse): resolve issues apontados pelo SonarQube`.

## Refinamento técnico

### O que deve ser feito

O trabalho foi quebrado em 6 tarefas independentes, cada uma com sua própria descrição, critérios de aceitação e restrições (ver arquivos `01-tarefa-*.md` a `06-tarefa-*.md` nesta pasta):

| # | Tarefa | Tipo | Risco |
|---|--------|------|-------|
| 1 | Corrigir mapeamento de `cStat` na marca d'água (CANCELADA/SUBSTITUÍDA) | Bug | Baixo |
| 2 | Implementar rodapé de créditos do integrador (`creditsIntegratorFooter`) | Bug (no-op) | Baixo |
| 3 | Implementar bloco "Totais Aproximados dos Tributos" (Lei nº 12.741/2012) | Gap de compliance | Baixo |
| 4 | Completar campos das Informações Complementares | Gap funcional | Baixo/Médio |
| 5 | Suspender bloco "Tributação Federal (Exceto CBS)" a partir da competência 2027 | Gap regulatório (Reforma Tributária) | Médio |
| 6 | Revisar extração dos campos de alíquota/valor do bloco IBS/CBS | Validação técnica | Médio |

Nenhuma dessas tarefas exige a refatoração estrutural do arquivo (`Danfse.php` inteiro tem 1165 linhas em um único arquivo, sem traits) — todas são alterações cirúrgicas em métodos já existentes (`watermark()`, `blocoTribFederal()`, `blocoIBSCBS()`, `blocoInfoCompl()`) ou pequenas adições de método.

### Dependências e links

* Análise de origem: [INT-2581 — Análise de sincronia do fork sped-da com o upstream](https://arquivei.atlassian.net/browse/INT-2581)
* Implementação atual: `src/NFSe/Danfse.php` na branch `arquivei-releases` (PRs [#35](https://github.com/arquivei/sped-da/pull/35) e [#36](https://github.com/arquivei/sped-da/pull/36))
* Referência de implementação do upstream: [`nfephp-org/sped-da` — `src/NFSe/Danfse.php`](https://github.com/nfephp-org/sped-da/blob/master/src/NFSe/Danfse.php) (usada apenas como referência de comportamento, não para merge direto)
* Fixture de teste: `tests/fixtures/xml/nfse.xml`
* Exemplo de uso: `examples/nfse/danfse.php`
* Lei nº 12.741/2012 (Lei da Transparência Fiscal — obrigatoriedade de discriminação da carga tributária aproximada em documentos fiscais ao consumidor)
* Depende de confirmação, junto ao time de produto/fiscal da Arquivei, da tabela oficial vigente de códigos `cStat` para a NFS-e Nacional (ver Tarefa 1) e da estrutura vigente do bloco IBS/CBS no schema da NFS-e Nacional (ver Tarefa 6), já que ambos os temas fazem parte de uma especificação (ADN/NT-008 e Reforma Tributária) ainda em evolução.

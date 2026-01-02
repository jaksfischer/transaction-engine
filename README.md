# Transaction Engine

## 1. Visão Geral da Solução

Este projeto implementa um **motor de processamento de transações financeiras** utilizando **Laravel**, projetado para lidar com alto volume de requisições de forma performática e escalável.

A criação das transações ocorre de forma **rápida e síncrona**, enquanto todo o **processamento financeiro é feito obrigatoriamente de forma assíncrona via filas**, garantindo desacoplamento, escalabilidade horizontal e consistência dos dados.

---

## 2. Arquitetura Geral

- **API REST** em Laravel
- **Fila assíncrona** utilizando Laravel Queues
- **Worker dedicado** para processamento financeiro
- **Banco de dados relacional**
- **Containerização com Docker e Docker Compose**
- **Swagger (OpenAPI)** para documentação

Separação clara de responsabilidades:
- Controller: valida dados, cria transação PENDING e despacha Job
- Job: executa validações financeiras, atualiza saldos e status
- Banco de dados: garante atomicidade e concorrência

---

## 3. Fluxo da Transação

1. Cliente chama `POST /api/transactions`
2. API valida os dados básicos
3. Transação é persistida com status **PENDING**
4. A transação é publicada em uma fila
5. Um worker consome a fila
6. O Job processa a transação:
    - Valida saldo (quando aplicável)
    - Atualiza os saldos das contas
    - Atualiza o status para **PROCESSED** ou **FAILED**

Nenhuma lógica financeira ocorre no momento da requisição HTTP.

---

## 4. Estratégia de Filas

Foi utilizado **Laravel Queues** como mecanismo de processamento assíncrono.

### Justificativa:
- Integração nativa com o framework
- Suporte a retry e backoff
- Facilidade de escalar múltiplos workers
- Simplicidade operacional

O processamento financeiro nunca ocorre de forma síncrona.

---

## 5. Consistência Financeira

A consistência dos dados é garantida através de:

- **Transações de banco (`DB::transaction`)**
- **Locks de linha (`lockForUpdate`)**
- Processamento idempotente baseado no status da transação
- Atualização de saldo apenas no Job

Isso evita:
- Condições de corrida
- Processamento duplicado
- Inconsistência de saldo

---

## 6. Idempotência e Concorrência

- Apenas transações com status **PENDING** podem ser processadas
- Reexecuções do Job não geram efeitos colaterais
- Locks garantem exclusividade durante o processamento

---

## 7. Falhas, Retry e Dead Letter Queue (DLQ)

- Jobs possuem retry automático com backoff
- Falhas definitivas são registradas na tabela `failed_jobs`
- A tabela `failed_jobs` funciona como uma **Dead Letter Queue (DLQ)**
- É possível reprocessar Jobs manualmente

---

## 8. Logs e Observabilidade

O Job registra logs estruturados para:
- Início do processamento
- Falhas de negócio
- Conclusão bem-sucedida

O sistema foi projetado para fácil integração com ferramentas de observabilidade.

---

## 9. Métricas (Conceitual)

Métricas que seriam expostas via Prometheus:

- Transações criadas
- Transações processadas
- Transações com falha
- Tempo de processamento
- Tamanho da fila

Essas métricas permitiriam:
- Monitoramento de performance
- Detecção de gargalos
- Decisão de escalabilidade

---

## 10. Teste de Carga (Conceitual)

O endpoint `POST /api/transactions` seria testado com alto volume de requisições utilizando ferramentas como **k6**.

### Objetivos:
- Validar baixa latência da API
- Confirmar absorção de picos via fila
- Garantir consistência financeira sob carga

---

## 11. Endpoints Disponíveis

### Criar Transação
`POST /api/transactions`

Campos:
- source_account_id
- destination_account_id (opcional)
- amount
- type (credit, debit, transfer)

---

### Consultar Saldo
`GET /api/accounts/{id}/balance`

---

## 12. OpenAPI / Swagger

A API é documentada utilizando **Swagger (OpenAPI)** através do pacote **L5 Swagger**.

A documentação pode ser acessada em:
```
/api/documentation
```

---

## 13. Testes Automatizados

Foi implementado um **teste de integração** que valida o fluxo completo:
- Criação da transação
- Processamento via fila
- Atualização correta de saldo
- Atualização do status da transação

---

## 14. Subindo o Projeto

Com Docker e Docker Compose:

```bash
docker-compose up
```

O projeto sobe com um único comando.

---

## 15. Trade-offs

- Laravel Queues foi escolhido pela simplicidade e integração nativa
- Locks garantem consistência, com pequeno custo de paralelismo
- DLQ baseada em banco é suficiente para este escopo
- Métricas e testes de carga são conceituais para evitar overengineering

---

## 16. Considerações Finais

As decisões deste projeto priorizam:
- Clareza arquitetural
- Consistência financeira
- Escalabilidade
- Simplicidade operacional
- 

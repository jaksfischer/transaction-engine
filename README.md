# Transactions Engine – Laravel

## Visão Geral da Solução

Este projeto implementa um motor de processamento de transações financeiras utilizando Laravel, com foco em **processamento assíncrono**, **consistência financeira** e **escalabilidade horizontal**.

A API é responsável apenas pela **entrada rápida das transações**, enquanto todo o processamento financeiro ocorre de forma **assíncrona via filas**, conforme solicitado no desafio.

---

## Fluxo da Transação

### 1. Criação da Transação (API – Síncrono)

Endpoint:
```
POST /api/transactions
```

Fluxo:
1. Validação dos dados básicos da transação
2. Persistência da transação com status `PENDING`
3. Publicação de um Job na fila
4. Retorno imediato `202 Accepted`

❗ Nenhuma atualização de saldo ocorre nesta etapa.

---

### 2. Processamento da Transação (Worker – Assíncrono)

O processamento é realizado por um Job (`ProcessTransactionJob`) consumido por um worker de fila.

Durante o processamento:
- A transação é carregada com **lock pessimista**
- É validada a idempotência (status `PENDING`)
- O saldo é validado para operações de débito e transferência
- Os saldos das contas são atualizados
- O status da transação é atualizado para `PROCESSED`
- Em caso de falha, o status é atualizado para `FAILED`

Todas as operações financeiras ocorrem dentro de uma **transação de banco de dados**, garantindo atomicidade.

---

## Consulta de Saldo

Endpoint:
```
GET /api/accounts/{id}/balance
```

Retorna o saldo atual da conta informada.

---

## Estratégia de Filas

Foi utilizado **Laravel Queues com Redis** como driver.

### Justificativa da escolha:
- Redis é leve e de alta performance
- Integração nativa com Laravel
- Facilidade de escalabilidade horizontal
- Adequado para alto volume de mensagens
- Simplicidade operacional em ambientes containerizados

O processamento das transações **não ocorre de forma síncrona em nenhum momento**.

---

## Consistência Financeira

Para garantir consistência:
- Uso de `DB::transaction`
- Lock pessimista (`SELECT ... FOR UPDATE`)
- Atualizações de saldo e status realizadas de forma atômica
- Nenhuma transação é processada mais de uma vez

---

## Concorrência

- Locks de banco evitam condições de corrida
- Previne double spend
- Processamento seguro mesmo com múltiplos workers

---

## Idempotência

- Cada transação possui uma `idempotency_key`
- Transações já processadas não são executadas novamente
- Garante segurança em cenários de retry ou reprocessamento

---

## Falhas, Retry e Reprocessamento

- Jobs possuem retry automático
- Backoff configurado
- Em caso de falha definitiva, a transação é marcada como `FAILED`
- Laravel oferece suporte nativo a `failed_jobs`, permitindo análise e reprocessamento

---

## Performance e Escalabilidade

- API desacoplada do processamento financeiro
- Workers podem ser escalados horizontalmente
- Redis permite alto throughput
- Arquitetura orientada a eventos

---

## Testes Automatizados

Foi implementado um **teste de integração** cobrindo o fluxo completo de transação:

- Criação da transação via API
- Persistência com status `PENDING`
- Execução do Job de processamento
- Atualização correta dos saldos
- Atualização do status para `PROCESSED`

A escolha por teste de integração reflete o principal risco do domínio: **consistência financeira ao longo do fluxo assíncrono**, e não apenas métodos isolados.

---

## Containerização

A aplicação está totalmente containerizada utilizando:
- Docker
- Docker Compose

Para subir o ambiente:

```bash
docker-compose up
```

Com isso são iniciados:
- Aplicação Laravel
- Worker de filas
- MySQL
- Redis
- Nginx

---

## Documentação da API (Swagger)

A documentação OpenAPI está disponível em:

http://localhost:8000/api/documentation

Ela descreve os endpoints de criação de transações e consulta de saldo.

---

## Trade-offs Considerados

- Redis foi escolhido em vez de SQS/RabbitMQ pela simplicidade e integração nativa
- Consistência financeira foi priorizada em relação a throughput máximo
- Locks pessimistas garantem segurança ao custo de menor paralelismo extremo
- DLQ customizada não foi implementada pois o Laravel já oferece suporte nativo

---

## Considerações Finais

Não existe uma única solução correta para este problema.  
As decisões tomadas priorizam clareza, consistência e aderência aos requisitos do desafio, mantendo o código simples, testável e escalável.

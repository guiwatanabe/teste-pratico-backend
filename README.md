## Requisitos

- Docker
- GNU make (Windows: Git Bash ou WSL)

## Portas

- *api* - **8000**
- *mysql* - **3306**
- *gateways* - **3001**, **3002**

## Rodando o projeto

```bash
git clone https://github.com/guiwatanabe/teste-pratico-backend.git
cd teste-pratico-backend
cp .env.example .env
make setup
```

> #### IMPORTANTE: Preencha as variáveis de configuração dos gateways no `.env`:
>
> ```env
> GATEWAY_1_AUTH_EMAIL=dev@betalent.tech
> GATEWAY_1_AUTH_TOKEN=FEC9BB078BF338F464F96B48089EB498
> GATEWAY_2_HEADER_TOKEN=tk_f2198cc671b5289fa856
> GATEWAY_2_HEADER_SECRET=3d15e8ed6131446ea7e3456728b1211f
> ```
> 
> *Nota: Essas variáveis estão aqui pois já estão disponíveis publicamente no README original, mas não estão configuradas previamente no arquivo `.env.example`.* 
> 
>[📖 README_original.md](/README_original.md)

#### Usuários padrão (seed):
| Role    | Email               | Senha    |
| ---     | ---                 | ---      |
| ADMIN   | admin@example.com   | password |
| MANAGER | manager@example.com | password |
| FINANCE | finance@example.com | password |

## Rodando testes:
```bash
make test
```

## Outros comandos
| Comando       | Descrição                             | 
| ---           | ---                                   |
| `make up`     | Iniciar containers                    |
| `make down`   | Parar containers                      | 
| `make fresh`  | Recriar banco e rodar seeders         |
| `make remove` | Remover containers, imagens e volumes |

> Consulte o arquivo [Makefile](/Makefile) para ver todos os comandos.

---

## Etapas do Desenvolvimento

## Modelagem dos dados 
Como foi dada a especificação, vou começar criando as tabelas, models e relationships.

| Tabela               | Dados                   |
| ---:                 | ---                     |
| users                | Usuários e roles        |
| gateways             | Gateways e configuração |
| clients              | Clientes (compradores)  |
| products             | Produtos                |
| transaction_products | Produtos por transação  |
| transactions         | Transações              |

## Gateways

O sistema de gateways foi estruturado da seguinte forma:

1. `GatewayInterface` - Todo driver deverá implementar este contrato:
```php
<?php
interface GatewayInterface {
    public function charge(array $payload): array;
    public function refund(array $payload): array;
    public function listTransactions(): array;
}
```

2. `AbstractGatewayDriver` - Classe base que todos os drivers estendem. Fornece:
   - `log()` - registra toda comunicação HTTP com o gateway na tabela `gateway_logs`, incluindo método, URL, headers, body (request e response) e status code.
   - `sanitizeFields()` - mascara campos sensíveis antes de persistir no log.`.
   - `loadConfiguration()` - método abstrato que cada driver implementa para carregar e validar suas próprias variáveis de configuração a partir de `config/gateways.php`.

3. Configuração do driver - `config/gateways.php` - mapeia um driver a uma classe e carrega as configurações necessárias.

4. Tabela **`gateways`** - controla status (`is_active`), e prioridade (`priority`). O serviço de pagamentos acessa a configuração, ordena por prioridade e tenta processar o pagamento em cada gateway ativo, até obter sucesso, ou eventualmente todos falharem.

---

### Adicionando um novo gateway
> Exemplo: gateway_3
1. Criar uma classe (`app/Services/Gateways/Gateway3Driver.php`) que estende `AbstractGatewayDriver`, implementando:
   - `driverName(): string` - retorna o identificador do driver (ex: `'gateway_3'`).
   - `loadConfiguration(): void` - carrega e valida as variáveis de `config/gateways.php`.
   - `charge(array $payload): array` - processa o pagamento.
   - `refund(array $payload): array` - processa o reembolso.
   - `listTransactions(): array` - lista as transações.
2. Criar a chave de configuração (ex: `gateway_3`) (`config/gateways.php`), configurar a classe criada e variáveis necessárias.
3. Definir as variáveis de configuração no `.env`.
4. Inserir um registro na tabela `gateways` (`driver = gateway_3`), e configurar prioridade.


## TDD

Escolhi seguir a abordagem do TDD para desenvolvimento do projeto, e segui o ciclo clássico: **Red > Green > Refactor**.

1. **Mapeamento** - antes de começar, segui a especificação do readme e listei os comportamentos.
2. **Red** - escrevi os testes, que falham por não ter implementação.
3. **Green** - implementação mínima para fazer os testes passarem.
4. **Refactor** - ajustes de código e legibilidade mantendo comportamento.

Grupos de testes:

1. **Feature** - testes de integração por endpoint (auth, CRUDs, purchase, refund, gateways) cobrindo status HTTP, payloads, autorização por roles e dados persistidos.
2. **Unit** - lógica isolada como cálculo de valor, fallback entre gateways e comportamento de drivers.

> Resultado: 100% de cobertura, todos os testes OK.
>
> Informações dos testes - [📖 TESTS.md](/TESTS.md).

## Autenticação e Autorização
O projeto utilizará a biblioteca padrão do Laravel para *autenticação* - **Laravel Sanctum**. 

Para autorização (RBAC), vou utilizar *Policies*, que irão controlar o acesso através do campo **role** do usuário autenticado através do token.

## Rotas - API

### Auth

| Método | Endpoint      | Auth   | Descrição                          |
| ---    | ---           | ---    | ---                                |
| POST   | `/auth/login` | -      | Obter token                        |
| GET    | `/auth/user`  | Bearer | Informações do usuário autenticado |

**POST /auth/login**
```json
{
  "email": "test@example.com",
  "password": "password"
}
```
**Response:**
```json
{
  "token": "1|abc123..."
}
```

---

### Purchase

> ### Importante:
>
> Idealmente, esse endpoint implementaria idempotência, para prevenir requests/cobranças duplicadas que podem vir a acontecer. Não implementei essa funcionalidade, mas é necessária a implementação para sistemas em produção.

| Método | Endpoint    | Auth | Descrição          |
| ---    | ---         | ---  | ---                |
| POST   | `/purchase` | -    | Efetuar uma compra |

**POST /purchase**
```json
{
  "buyer": {
    "name": "Cliente Teste",
    "email": "test@example.com"
  },
  "card": {
    "number": "111222333444",
    "expiry": "12/26",
    "cvv": "123"
  },
  "products": [{ "id": 1, "quantity": 2 }]
}
```

---

### Users

| Método | Endpoint      | Auth   | Descrição                |
| ---    | ---           | ---    | ---                      |
| GET    | `/users`      | Bearer | Listar todos os usuários |
| GET    | `/users/{id}` | Bearer | Detalhes de um usuário   |
| POST   | `/users`      | Bearer | Criar usuário            |
| PATCH  | `/users/{id}` | Bearer | Editar usuário           |
| DELETE | `/users/{id}` | Bearer | Excluir usuário          |

---

### Products

| Método | Endpoint         | Auth   | Descrição                |
| ---    | ---              | ---    | ---                      |
| GET    | `/products`      | Bearer | Listar todos os produtos |
| GET    | `/products/{id}` | Bearer | Detalhes de um produto   |
| POST   | `/products`      | Bearer | Criar produto            |
| PATCH  | `/products/{id}` | Bearer | Editar produto           |
| DELETE | `/products/{id}` | Bearer | Excluir produto          |

---

### Clients

| Método | Endpoint        | Auth   | Descrição                        |
| ---    | ---             | ---    | ---                              |
| GET    | `/clients`      | Bearer | Listar todos os clientes         |
| GET    | `/clients/{id}` | Bearer | Detalhes de um cliente + compras |

---

### Transactions

| Método | Endpoint                    | Auth   | Descrição                  |
| ---    | ---                         | ---    | ---                        |
| GET    | `/transactions`             | Bearer | Listar todas as transações |
| GET    | `/transactions/{id}`        | Bearer | Detalhes de uma transação  |
| POST   | `/transactions/{id}/refund` | Bearer | Reembolso de uma transação |

---

### Gateways

| Método | Endpoint         | Auth   | Descrição                              |
| ---    | ---              | ---    | ---                                    |
| PATCH  | `/gateways/{id}` | Bearer | Trocar status ou prioridade do gateway |

**PATCH /gateways/{id}**
```json
{
  "is_active": true/false,
  "priority": 99
}
```

> Também pode ser visualizado e testado pela collection do postman [🔗 API.postman_collection.json](/API.postman_collection.json).

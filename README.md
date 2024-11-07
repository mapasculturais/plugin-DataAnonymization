# plugin-DataAnonymization

Plugin de Anonimização de Dados

## ⚠️ **ATENÇÃO** ⚠️

### **Este plugin NÃO deve ser utilizado em ambientes de produção!**

O `plugin-DataAnonymization` foi desenvolvido para anonimizar dados pessoais na base de dados, substituindo-os por informações aleatórias. Ele é ideal para ambientes de desenvolvimento e testes, onde é essencial garantir a privacidade dos dados sem comprometer sua estrutura.

### **Importante:**

- **Risco de perda de dados:** Ao utilizar este plugin, todos os dados reais serão substituídos permanentemente por valores fictícios, o que pode resultar em perda de informações caso seja usado incorretamente.
- **Apenas para ambientes de teste:** Este plugin foi criado exclusivamente para ambientes de desenvolvimento e teste. **Não o utilize em produção**, pois isso comprometerá a integridade dos dados reais.

Use com cautela para evitar a perda de dados importantes.

## Requisitos Mínimos

- Mapas Culturais
- Composer

## Configuração Básica

### Configurações Necessárias para o Ambiente de Desenvolvimento

1. Mapeie o plugin no arquivo `docker-compose` de desenvolvimento conforme o exemplo abaixo:

   ```yaml
   version: '2'
   services:
     mapas:
       ...
       ports:
         - "80:80"

       volumes:
         ...

         # themes and plugins
         ...
         - ../plugins/DataAnonymization:/var/www/src/plugins/DataAnonymization
   ```

- No arquivo `dev/config.d/plugins.php`, adicione 'DataAnonymization' para ativar o plugin:

  ```php
  <?php

  return [
      'plugins' => [
          'MultipleLocalAuth' => [ 'namespace' => 'MultipleLocalAuth' ],
          'SamplePlugin' => ['namespace' => 'SamplePlugin'],
          "DataAnonymization",
      ]
  ];

- Após concluir a configuração, navegue até a pasta raiz do plugin e execute o comando `composer install`.




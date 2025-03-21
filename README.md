# CFP Manager
Ferramenta de gestão de Call-For-Papers para Comunidades.

## Tecnologias Utilizadas
- PHP 8
- MySQL
- Slim
- Eloquent
- JWT

## Fases Concluídas

- Backend
  - User
    - POST /user
    - PUT /user
    - PATCH /user/password
    - GET /user
  - Auth
    - POST /auth/login
    - POST /auth/refresh-token
  - Community
    - POST /community
    - GET /community (with search and paginate)
    - GET /community/my/details
    - GET /community/{id}
    - PUT /community/{id}
  - Criar
    - Community
      - GET /community/{id}/event
    - Event
      - POST /event
      - GET /event/community/{id}
      - GET /event/{id}
      - GET /event/{id}/cfp
      - PUT /event/{id}
      - DELETE /event/{id}
    - CFP
      - POST /cfp
      - PUT /cfp/{id}
      - DELETE /cfp/{id}
      - PUT /cfp/{id}/approve
      - PUT /cfp/{id}/reprove
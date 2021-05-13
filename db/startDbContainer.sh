docker run \
  -d \
  --name postgresql-container \
  -p 5432:5432 \
  -e POSTGRES_PASSWORD=someSecretPassword \
  -v $PWD/pgdata:/var/lib/postgresql/data \
  postgres

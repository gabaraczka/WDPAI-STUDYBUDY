# WDPAI - Lab1

Project for WDPAI Laboratory 1 classes

## Installation and commissioning

1. Open a terminal.
2. Go to the project directory.
3. Run Docker Compose with the command:

```bash
docker compose up
```
or in a separate terminal (background):
```bash
docker compose up -d
```
- Rebuilding the image:
```bash
docker compose build
```

## Configuration

- Default port '8080' (can be changed in the [YAML Compose](./docker-compose.yaml) file)
- Port inside the container: '80'.
- Changing the PHP version by editing the [Dockerfile](./docker/php/Dockerfile) file.
# Build Dolibarr application container

## Build Docker container with default Dolibarr installation
`sudo docker build -t "maestrano:dolibarr-3.7.0" .`

## Start Docker container
`sudo docker run -t -i --name=dolibarr_container maestrano:dolibarr-3.7.0`

You can add extra hosts entry to your cotnainers with the option `--add-host hostname:IP`:

`sudo docker run -t -i --add-host application.maestrano.io:172.17.42.1 --add-host connec.maestrano.io:172.17.42.1 --name=dolibarr_container maestrano:dolibarr-3.7.0`

## Retrieve container details (IP address...)
`sudo docker inspect dolibarr_container`

And then access the container with http://[IP_ADDRESS] to check vTiger is running

## Activate Maestrano customisation on start (SSO and Connec! data sharing)
This is achieved by specifying environment variables

```bash
docker run -it \
  -e "MNO_SSO_ENABLED=true" \
  -e "MNO_CONNEC_ENABLED=true" \
  -e "MNO_MAESTRANO_ENVIRONMENT=local" \
  -e "MNO_SERVER_HOSTNAME=dolibarr.app.dev.maestrano.io" \
  -e "MNO_API_KEY=e876260b50146136ec393b662edc6d91e453a0dbae1facad335b33fb763ead99" \
  -e "MNO_API_SECRET=9309cffc-2cb2-4423-92ea-e1ff64894241" \
  --add-host application.maestrano.io:172.17.42.1 \
  --add-host connec.maestrano.io:172.17.42.1 \
  --name=mcube-bbb bchauvet/dolibarr
 ```

# Docker cheat-sheet

## List docker containers
`sudo docker ps`

## Stop and remove all containers
sudo docker stop $(docker ps -a -q)
sudo docker rm -v $(docker ps -a -q)

## Remove untagged images
sudo docker images -q --filter "dangling=true" | xargs docker rmi

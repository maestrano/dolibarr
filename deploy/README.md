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

## Apply the maestrano patch (SSO and Connec! data sharing)
ansible-playbook /etc/ansible/playbooks/configure_dolibarr.yml -c local --extra-vars='{"sso_enabled": "true", "connec_enabled": "true", "maestrano_environment": "local", "server_hostname": "dolibarr.app.dev.maestrano.io", "api_key": "7a25167c7a960d48e10ada7721a9820f599925e790c3e94a1d82e04fb645c292", "api_secret": "d657da53-9f32-4efd-8cfe-0b0f4ac12214"}'

### Maestrano configuration variables:
 - sso_enabled
 - connec_enabled
 - maestrano_environment (production, uat, local)
 - server_hostname (cube uid)
 - api_key
 - api_secret
 - innodb_additional_mem_pool_size (4M, 8M, 16M) based on container allocate PU
 - innodb_buffer_pool_size (64M, 128M, 256M) based on container allocate PU
 - php_memory_limit (64M, 128M, 256M) based on container allocate PU

## TODO
Map container mysql data and dolibarr directory as volumes and do backups:
-v /path/in/host:/var/lib/mysql -v /path/in/host:/var/lib/dolibarr/webapp


# Docker cheat-sheet

## List docker containers
`sudo docker ps`

## Stop and remove all containers
sudo docker stop $(docker ps -a -q)
sudo docker rm -v $(docker ps -a -q)

## Remove untagged images
sudo docker images -q --filter "dangling=true" | xargs docker rmi

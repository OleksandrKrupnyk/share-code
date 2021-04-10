# Доставка кода в контейнере


Следующая структура папок

```
L data
|   L index.php
L docker-compose.yml
L Dockerfile
```

Содержимое файла _index.php_
```html
<html lang="en">
    <head>
    <title>
        Hello page
    </title>
    </head>
    <body>
        <h1>Hello world2!</h1>
        <div>
            <ul>
                <!-- language: php -->
                <?php for($i=1;$i<=10;$i++):?>
                <li> <?=$i?> This is a test page. It can be a script on php language</li>
                <?php endfor;?>
            </ul>
        </div>
        <?php phpinfo(); ?>
</body>
</html>
```

Содержимое _Dockerfile_

```dockerfile
# Базовый докер образ с минимальным размером
FROM alpine:latest
# Копируем код из папки ./data в папку докер образа /data
# Если ее в докер образе нет то она будет создана
COPY ["./data", "/data"]
```

## С использованием *docker-compose.yml*

Содержимое _docker-compose.yml_

```yaml
# Версия файла для сборки
version: "3"
# Раздел сервисов (докер контейнеров)
services:
    #  Контейнер с кодом
    code:
        # Собираем с докер файла
        build:
            context: .
            args:
                # Имя докер-образа
                tag: lussy
        # имя докер контейнера с кодом        
        container_name: data_code
        # Задаем монтирование файлов докер обаза в том на хосте
        # Если такой том до этого не существовал то он будет создан и код из папки  /data 
        # докер образа будет скопировани в папку тома
        # Важно! Если такой том существовал то содержимое тома "data_code" заменит 
        # содержимое папки  /data 
        volumes:
            - data_code:/data
    # Контейнер с сервером
    nginx:
        # Докер образ внутри которого же установлен nginx и php
        image: wyveo/nginx-php-fpm:php74
        # имя контейнера при создани
        container_name: webserver
        # отркываем порты
        ports:
            - 8080:80
        # монтируем содержимое тома "data_code" в папку докера которая является корнем web каталога
        volumes:
            - data_code:/usr/share/nginx/html:ro
        # указываем зависимости    
        depends_on:
            - code
        # связываем докер контейнер nginx с code
        links:
            - code
# Указываем какие имена томов буду создани 
# В результате создается том с имененем <имя-католога-с-docker-compose-файлом>_data_code
volumes:
    data_code:
```

Для запуска выполняем команду[1]
```cmd
> docker-compose up -d --build
```
Опиция `--build` сообщает `docker-compose` всегда пересобирать докер образ из файлов `Dockerfile`

Для остановки
```cmd
> docker-compose down
```
После остановки докер контейнеров том _data_code_ остается в системе docker и не удаляется. То есть если запустить еще раз [1], то произойдет сборка докер-образа из докер-файла, но по скольку _data_code_ существует, то код из этого тома будет скопирован в докер-контейнер _code_, а не наоборот.

Если, внесли изменения в файл _index.php_, и выполныть [1] изменения не будут видны. 
```html
<html lang="en">
    <head>
    <title>
        Hello page2
    </title>
    </head>
    <body>
        <h1>Hello world2!</h1>
        <div>
            <ul>
                <?php for($i=1;$i<=10;$i++):?>
                <li> <?=$i?> This is a test page. It can be a script on php language</li>
                <?php endfor;?>
            </ul>
        </div>
        <?php phpinfo(); ?>
</body>
</html>
```

Поскольку существует том. Поэтому перед поторним запуском [1] следует выполнять
```cmd
> docker volume rm <имя-католога-с-docker-compose-файлом>_data_code
```
Список томов докер можно узанть по команде `> docker volume ls`.

---

## С использованием *Dockerfile*
C использование команд создания и запуску докера-контейнера

Сначала создаем докер-образ с кодом и задаем ему имя `lussy:v1` [2].

```cmd
> docker build . -t lussy:v1
```

Создаем и запускаем докер-контейнер. Задаем ему имя `lussy-code`. Указывем опции подключения тома `-v  data_code:/data`. В системе том с именем на момент запуска не существует `data_code`.

```cmd
> docker run  -d --name=lussy-code  -v  data_code:/data lussy:v1 
```
После запуска будет создан именований том с именем `data_code` без префикса как это происходт в _docker-compose_.

Создаем и запускаем докер-контейнер с веб сервером. Для этого виполняем команду [4].

```
> docker run -d --name=webserver -v  data_code:/usr/share/nginx/html:ro -p 8080:80 wyveo/nginx-php-fpm:php74
```
- `--name=webserver` имя докер контейнера
- `-v  data_code:/usr/share/nginx/html:ro` подключаем том к папке корня веб-сервера в режиме только чтения
- `-p 8080:80`  открываем порты
- `wyveo/nginx-php-fpm:php74` имя докер-образа

Останавливаем работу докер-контейнера на котором запущен веб серврер
```cmd
> docker stop webserver
```

Меняем файл _index.php_.
```html
<html lang="en">
    <head>
    <title>
        Hello page 3
    </title>
    </head>
    <body>
        <h1>Hello world3!</h1>
        <div>
            <ul>
                <?php for($i=1;$i<=10;$i++):?>
                <li> <?=random_int(10,20)?> This is a test page.</li>
                <?php endfor;?>
            </ul>
        </div>
        <?php phpinfo(); ?>
</body>
</html>
```

Удаляем уже не нужный докер-контейнер `lussy-code` и докер-образ `lussy:v1`.
```cmd
> docker rm lussy-code
> docker image rm lussy
```
При попытке выполнить команду удаления тома
```cmd
> docker volume rm -f data_code
```
Полумчаем ощибку: `Error response from daemon: remove data_code: volume is in use - [a0...23]`. docker сообщает нам, что том используется. Он используется докер-контейнером хоть он и остановлен. Удалить том возможно при удалении и всех докер-контейнеров к которым он подключен. Поетому удаялем и докер-контейнер `webserver`, а затем удаляем и том  `data_code`
```cmd
> docker rm webserver
> docker volume rm -f data_code
```
Создаем докер-образ с кодом - выполнив команду [2].
Создаем докер-контейнер с кодом - выполнив команду [3].
Создаем докер-контейнер с веб сервером - выполнив команду [4].

```cmd
> docker build . -t lussy:v1
> docker run  -d --name=lussy-code  -v  data_code:/data lussy:v1 
> docker run -d --name=webserver -v  data_code:/usr/share/nginx/html:ro -p 8080:80 wyveo/nginx-php-fpm:php74
```
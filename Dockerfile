# Базовый докер образ с минимальным размером
FROM alpine:latest
# Копируем код из папки ./data в папку докер образа /data
# Если ее в докер образе нет то она будет создана
COPY ["./data", "/data"]

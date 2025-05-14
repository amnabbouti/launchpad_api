#!/bin/bash

ENV=$1
shift

CMD="$@"

if [ "$ENV" != "local" ] && [ "$ENV" != "production" ]; then
    echo "Error: Environment must be either 'local' or 'production'"
    exit 1
fi

if [ "$ENV" == "local" ]; then
    ddev artisan env:switch local "$CMD"
else
    php artisan env:switch production "$CMD"
fi

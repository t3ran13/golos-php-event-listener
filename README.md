# golos-event-listener
PHP event listener for STEEM/GOLOS blockchains




## RedisManager

DB structure:
- DB0
    - app:listeners:last_id
    - app:listeners:{id}:event
    - app:listeners:{id}:handler
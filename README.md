# golos-event-listener
PHP event listener for STEEM/GOLOS blockchains




## RedisManager

DB structure:
- DB0
    - app:processes:last_id
    - app:processes:{id}:last_update_datetime
    - app:processes:{id}:status
    - app:processes:{id}:pid
    - app:processes:{id}:handler
    - app:processes:{id}:data:last_block
    
    - app:listeners:last_id
    - app:listeners:{id}:handler
    - app:listeners:{id}:conditions:{n}:key
    - app:listeners:{id}:conditions:{n}:value
    
    - app:events:{listener_id}:{block_n}:{trx_n_in_block}
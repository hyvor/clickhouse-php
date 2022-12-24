<?php

use Hyvor\Clickhouse\Clickhouse;

it('connection', function () {

    $client = new Clickhouse();

    //$client->query("ALTER TABLE comments UPDATE status = 'spam' WHERE id = {id:String}", ['id' => 13]);

});

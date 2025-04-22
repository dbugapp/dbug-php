<?php

use DbugApp\Dbug;

test('it serializes and sends the payload', function () {
    $port = random_int(54000, 54999);
    $address = "tcp://127.0.0.1:$port";
    $socket = stream_socket_server($address, $errno, $errstr);

    expect($socket)->not->toBeFalse("Failed to bind socket: $errstr");

    $received = null;

    $pid = pcntl_fork();
    if ($pid === 0) {
        $conn = stream_socket_accept($socket);
        $request = fread($conn, 1024);

        if (preg_match('/\r\n\r\n(.+)/s', $request, $matches)) {
            file_put_contents(__DIR__ . '/__payload.txt', $matches[1]);
        }

        fwrite($conn, "HTTP/1.1 200 OK\r\nContent-Length: 0\r\n\r\n");
        fclose($conn);
        exit(0);
    }

    usleep(100_000); // wait 100ms for server

    Dbug::setEndpoint("http://127.0.0.1:$port");
    Dbug::send([
        'message' => 'hello',
        'value' => 42,
    ]);

    usleep(100_000); // wait for response

    $payload = file_get_contents(__DIR__ . '/__payload.txt');
    unlink(__DIR__ . '/__payload.txt');

    expect($payload)->not->toBeNull();

    $data = json_decode($payload, true);
    expect($data)->toMatchArray([
        'message' => 'hello',
        'value' => 42,
    ]);
});

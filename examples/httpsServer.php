<?php

use Legionth\React\Http\HttpServer;
use Legionth\React\Http\HttpBodyStream;
use React\Socket\Server as Socket;
use RingCentral\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use React\Stream\ReadableStream;
use React\Socket\SecureServer;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$callback = function(RequestInterface $request) use ($loop){
    $stream = new ReadableStream();

    $periodicTimer = $loop->addPeriodicTimer(0.5, function () use ($stream) {
        $stream->emit('data', array("world\n"));
    });

        $loop->addTimer(3, function () use ($stream, $periodicTimer) {
            $periodicTimer->cancel();
            $stream->emit('end', array('end'));
        });

            $body = new HttpBodyStream($stream);

            return new Response(
                200,
                array(),
                $body
            );
};

$socket = new Socket($loop);
$socket = new SecureServer($socket, $loop,
    array(
        'local_cert' => 'server.pem',
        'local_pk' => 'server.key'
    )
);
$socket->listen(10000, 'localhost');
$socket->on('error', function (Exception $e) {
    echo 'Error' . $e->getMessage() . PHP_EOL;
});

$server = new HttpServer($socket, $callback);

$loop->run();

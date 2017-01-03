<?php
namespace Legionth\React\Http;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;

/**
 * Wraps any readable stream which will emit the data as HTTP chunks
 */
class ChunkedEncoderStream extends EventEmitter implements ReadableStreamInterface
{
    private $input;
    private $closed;

    public function __construct(ReadableStreamInterface $input)
    {
        $this->input = $input;

        $this->input->on('data', array($this, 'handleData'));
        $this->input->on('end', array($this, 'handleEnd'));
        $this->input->on('error', array($this, 'handleError'));
        $this->input->on('close', array($this, 'close'));
    }

    /**
     * Will emit the given string in a chunked encoded string
     * @param string $data - string to be tranfsformed into an HTTP chunked encoded string
     */
    public function handleData($data)
    {
        $completeChunk = $this->createChunk($data);

        $this->emit('data', array($completeChunk));
    }

    /**
     * Handles the an occuring exception on the stream
     * @param \Exception $e
     */
    public function handleError(\Exception $e)
    {
        $this->emit('error', array($e));
        $this->close();
    }

    /**
     * Ends the stream
     * @param string $data - data that should be written on the stream,
     *                       before it closes
     */
    public function handleEnd()
    {
        $this->emit('data', array("0\r\n\r\n"));

        if (!$this->closed) {
            $this->emit('end');
            $this->close();
        }
    }

    /**
     * @param string $data - string to be transformed in an valid
     *                       HTTP encoded chunk string
     * @return string
     */
    private function createChunk($data)
    {
        $byteSize = strlen($data);
        $chunkBeginning = $byteSize . "\r\n";

        return $chunkBeginning . $data . "\r\n";
    }

    public function isReadable()
    {
        return !$this->closed && $this->input->isReadable();
    }

    public function pause()
    {
        $this->input->pause();
    }

    public function resume()
    {
        $this->input->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        $this->readable = false;

        $this->emit('end', array());
        $this->emit('close', array());

        $this->removeAllListeners();
    }
}

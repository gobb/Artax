<?php

namespace Artax\Http;

use RuntimeException,
    InvalidArgumentException;

abstract class StdMessage implements Message {

    /**
     * @var array
     */
    protected $headers = array();
    
    /**
     * @var mixed
     */
    protected $body;
    
    /**
     * @var string
     */
    protected $cachedBodyFromStream;

    /**
     * @var string
     */
    protected $httpVersion = '1.1';

    /**
     * @param string $headerName
     * @return bool
     */
    public function hasHeader($headerName) {
        // Headers are case-insensitive:
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
        $capsHeader = strtoupper($headerName);
        return array_key_exists($capsHeader, $this->headers);
    }

    /**
     * @param string $headerName
     * @return string
     * @throws RuntimeException
     * @todo Figure out the best exception to throw. Perhaps one isn't needed.
     */
    public function getHeader($headerName) {
        if (!$this->hasHeader($headerName)) {
            throw new RuntimeException();
        }
        
        // Headers are case-insensitive:
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
        $capsHeader = strtoupper($headerName);
        return $this->headers[$capsHeader];
    }

    /**
     * @return array
     */
    public function getAllHeaders() {
        return $this->headers;
    }
    
    /**
     * Access the entity body
     * 
     * If a resource stream is assigned to the body property, its entire contents will be read into
     * memory and returned as a string. To access the stream resource directly without buffering
     * its contents, use Message::getBodyStream().
     * 
     * @return string
     */
    public function getBody() {
        if (!is_resource($this->body)) {
            return (string) $this->body;
        } elseif (!is_null($this->cachedBodyFromStream)) {
            return $this->cachedBodyFromStream;
        } else {
            $this->cachedBodyFromStream = stream_get_contents($this->body);
            rewind($this->body);
            return $this->cachedBodyFromStream;
        }
    }
    
    /**
     * Access the entity body resource stream directly without buffering its contents
     * 
     * If the assigned entity body is not a stream, null is returned.
     * 
     * @return resource
     */
    public function getBodyStream() {
        return is_resource($this->body) ? $this->body : null;
    }
    
    /**
     * @return string The HTTP version number (not prefixed by `HTTP/`)
     */
    public function getHttpVersion() {
        return $this->httpVersion;
    }
    
    /**
     * @param string $headerName
     * @param string $value
     * @return void
     */
    protected function assignHeader($headerName, $value) {
        // Headers are case-insensitive as per the HTTP spec:
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
        $normalizedHeader = rtrim(strtoupper($headerName), ': ');
        $this->headers[$normalizedHeader] = $value;
    }
    
    /**
     * @param mixed $iterable
     * @return void
     * @throws InvalidArgumentException
     */
    protected function assignAllHeaders($iterable) {
        if (!($iterable instanceof Traversable
            || $iterable instanceof StdClass
            || is_array($iterable)
        )) {
            $type = is_object($iterable) ? get_class($iterable) : gettype($iterable);
            throw new InvalidArgumentException(
                'Only an array, StdClass or Traversable object may be used to assign headers: ' .
                "$type specified"
            );
        }
        foreach ($iterable as $headerName => $value) {
            $this->assignHeader($headerName, $value);
        }
    }
}